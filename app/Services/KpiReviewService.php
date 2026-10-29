<?php // app/Services/KpiReviewService.php

namespace App\Services;

use App\Enums\KpiSubmissionStatus;
use App\Models\KpiReview;
use App\Models\KpiSubmission;
use App\Models\User;
use App\Support\KpiScore;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KpiReviewService
{
    // ------------------------------------------------------------------
    // ANTRIAN PER LAPORAN (alur utama)
    // ------------------------------------------------------------------

    /**
     * Daftar laporan menunggu keputusan. 1 baris = 1 karyawan + 1 tanggal.
     *
     * @param array<string, mixed> $f
     */
    public function reportQueue(User $reviewer, array $f, int $perPage = 15): LengthAwarePaginator
    {
        $page = KpiSubmission::query()
            ->select(
                'user_id',
                'report_date',
                DB::raw('COUNT(*) as total_indicators'),
                DB::raw('MAX(submitted_at) as submitted_at'),
            )
            ->where('status', KpiSubmissionStatus::Submitted->value)
            ->where('user_id', '!=', $reviewer->id)
            ->when(! $reviewer->hasRole('admin'), fn ($q) => $q->whereHas(
                'user.role', fn ($r) => $r->whereRaw('LOWER(name) = ?', ['karyawan'])
            ))
            ->when($f['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($f['position_id'] ?? null, fn ($q, $v) => $q->whereHas('user', fn ($u) => $u->where('position_id', $v)))
            ->when($f['date_from'] ?? null, fn ($q, $v) => $q->whereDate('report_date', '>=', $v))
            ->when($f['date_to'] ?? null, fn ($q, $v) => $q->whereDate('report_date', '<=', $v))
            ->groupBy('user_id', 'report_date')
            ->orderBy('report_date')
            ->orderBy('user_id')
            ->paginate($perPage);

        $users = User::with(['role:id,name', 'position:id,name'])
            ->whereIn('id', $page->pluck('user_id'))
            ->get()
            ->keyBy('id');

        $page->getCollection()->transform(fn ($r) => [
            'user' => [
                'id'       => $r->user_id,
                'name'     => $users[$r->user_id]->name,
                'role'     => $users[$r->user_id]->role?->name,
                'position' => $users[$r->user_id]->position?->name,
            ],
            'report_date'      => $r->report_date->toDateString(),
            'total_indicators' => (int) $r->total_indicators,
            'submitted_at'     => $r->submitted_at,
        ]);

        return $page;
    }

    /** Seluruh indikator dalam satu laporan, untuk ditinjau sebelum memutuskan. */
    public function reportDetail(User $reviewer, User $employee, string $date): Collection
    {
        $items = KpiSubmission::query()
            ->with([
                'user.role:id,name',
                'user.position:id,name',
                'positionKpi.kpiIndicator',
                'evidences',
            ])
            ->where('user_id', $employee->id)
            ->whereDate('report_date', $date)
            ->where('status', KpiSubmissionStatus::Submitted->value)
            ->orderBy('id')
            ->get();

        abort_if($items->isEmpty(), 404, 'Tidak ada laporan SUBMITTED pada tanggal tersebut.');

        foreach ($items as $s) {
            Gate::forUser($reviewer)->authorize('review', $s);
        }

        return $items;
    }

    /** Putuskan satu laporan utuh (user + tanggal). */
    public function decideReport(
        User $reviewer,
        User $employee,
        string $date,
        KpiSubmissionStatus $decision,
        ?string $notes,
    ): Collection {
        $ids = KpiSubmission::query()
            ->where('user_id', $employee->id)
            ->whereDate('report_date', $date)
            ->where('status', KpiSubmissionStatus::Submitted->value)
            ->pluck('id')
            ->all();

        abort_if(empty($ids), 404, 'Tidak ada laporan SUBMITTED pada tanggal tersebut.');

        return $this->decide($reviewer, $ids, $decision, $notes);
    }

    // ------------------------------------------------------------------
    // ANTRIAN PER INDIKATOR (opsional)
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $f */
    public function queue(User $reviewer, array $f, int $perPage = 15): LengthAwarePaginator
    {
        return KpiSubmission::query()
            ->with([
                'user:id,name,role_id,position_id',
                'user.role:id,name',
                'user.position:id,name',
                'positionKpi:id,position_id,kpi_indicator_id,target,weight,calculation_type',
                'positionKpi.kpiIndicator:id,name,unit,assessment_type',
            ])
            ->withCount('evidences')
            ->where('status', KpiSubmissionStatus::Submitted->value)
            ->where('user_id', '!=', $reviewer->id)
            ->when(! $reviewer->hasRole('admin'), fn ($q) => $q->whereHas(
                'user.role', fn ($r) => $r->whereRaw('LOWER(name) = ?', ['karyawan'])
            ))
            ->when($f['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($f['position_id'] ?? null, fn ($q, $v) => $q->whereHas('user', fn ($u) => $u->where('position_id', $v)))
            ->when($f['date_from'] ?? null, fn ($q, $v) => $q->whereDate('report_date', '>=', $v))
            ->when($f['date_to'] ?? null, fn ($q, $v) => $q->whereDate('report_date', '<=', $v))
            ->orderBy('report_date')
            ->orderBy('id')
            ->paginate($perPage);
    }

    // ------------------------------------------------------------------
    // INTI KEPUTUSAN
    // ------------------------------------------------------------------

    /**
     * Putuskan capaian yang dipilih; putusan otomatis berlaku untuk SELURUH laporan
     * (semua SUBMITTED milik user + report_date yang sama). Atomik: all-or-nothing.
     *
     * @param  array<int, int|string> $ids
     * @return Collection<int, KpiSubmission>
     */
    public function decide(User $reviewer, array $ids, KpiSubmissionStatus $decision, ?string $notes): Collection
    {
        abort_unless(
            in_array($decision, [KpiSubmissionStatus::Approved, KpiSubmissionStatus::Rejected], true),
            422,
            'Keputusan harus APPROVED atau REJECTED.'
        );

        $ids = array_values(array_unique(array_map('intval', $ids)));

        return DB::transaction(function () use ($reviewer, $ids, $decision, $notes): Collection {
            // 1) Capaian yang dipilih (pemicu)
            $selected = KpiSubmission::query()
                ->with('positionKpi.kpiIndicator:id,name')
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get();

            if ($selected->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    'submission_ids' => 'Ada capaian yang tidak ditemukan.',
                ]);
            }

            foreach ($selected as $s) {
                if ($s->status !== KpiSubmissionStatus::Submitted) {
                    throw ValidationException::withMessages([
                        'submission_ids' => "Capaian #{$s->id} berstatus {$s->status->value}; "
                            . 'hanya yang SUBMITTED bisa direview.',
                    ]);
                }
            }

            // 2) Perluas ke seluruh laporan: user + report_date yang sama
            $reports = $selected->unique(
                fn (KpiSubmission $s) => $s->user_id . '|' . $s->report_date->toDateString()
            );

            $batch = KpiSubmission::query()
                ->with(['user.role', 'user.position', 'positionKpi.kpiIndicator'])
                ->where('status', KpiSubmissionStatus::Submitted->value)
                ->where(function ($q) use ($reports): void {
                    foreach ($reports as $r) {
                        $q->orWhere(fn ($w) => $w
                            ->where('user_id', $r->user_id)
                            ->whereDate('report_date', $r->report_date->toDateString()));
                    }
                })
                ->lockForUpdate()
                ->get();

            // 3) Reviewer harus berhak atas SEMUA capaian dalam laporan
            foreach ($batch as $submission) {
                Gate::forUser($reviewer)->authorize('review', $submission);
            }

            // 4) Terapkan putusan ke seluruh laporan
            $approved   = $decision === KpiSubmissionStatus::Approved;
            $triggerIds = $selected->pluck('id');
            $firstName  = $selected->first()->positionKpi->kpiIndicator->name;

            foreach ($batch as $submission) {
                $isTrigger = $triggerIds->contains($submission->id);

                $reviewNotes = ($approved || $isTrigger)
                    ? $notes
                    : Str::limit(
                        "Ikut ditolak: indikator '{$firstName}' dalam laporan ini ditolak. Alasan: {$notes}",
                        255,
                        ''
                    );

                $submission->update(['status' => $decision]);

                KpiReview::updateOrCreate(
                    ['submission_id' => $submission->id],
                    [
                        'reviewer_id' => $reviewer->id,
                        'review_type' => $isTrigger ? 'MANUAL' : 'CASCADE',
                        'status'      => $decision->value,
                        'score'       => $approved
                            ? KpiScore::achievement($submission, $submission->positionKpi)
                            : null,
                        'notes'       => $reviewNotes,
                        'reviewed_at' => now(),
                    ]
                );
            }

            return $batch->load('review');
        });
    }
}