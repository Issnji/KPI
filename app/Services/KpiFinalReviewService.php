<?php

namespace App\Services;

use App\Enums\KpiSubmissionStatus;
use App\Models\KpiSubmission;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class KpiFinalReviewService
{
    /** Laporan yang sudah di-approve manager, menunggu keputusan akhir Admin. 1 baris = 1 karyawan + 1 tanggal. */
    public function queue(User $admin, array $f, int $perPage = 15): LengthAwarePaginator
    {
        $page = KpiSubmission::query()
            ->select(
                'user_id',
                'report_date',
                DB::raw('COUNT(*) as total_indicators'),
                DB::raw('MAX(submitted_at) as submitted_at'),
            )
            ->where('status', KpiSubmissionStatus::Approved->value)
            ->where('user_id', '!=', $admin->id)
            ->whereHas('review', fn ($r) => $r->whereNull('finalized_at'))
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

    public function detail(User $employee, string $date): Collection
    {
        $items = $this->pending($employee, $date)
            ->with([
                'user.role:id,name',
                'user.position:id,name',
                'positionKpi.kpiIndicator',
                'evidences',
                'review.reviewer:id,name',
            ])
            ->orderBy('id')
            ->get();

        abort_if($items->isEmpty(), 404, 'Tidak ada laporan yang menunggu persetujuan akhir pada tanggal tersebut.');

        return $items;
    }

    public function decide(
        User $admin,
        User $employee,
        string $date,
        KpiSubmissionStatus $decision,
        ?string $notes,
    ): Collection {
        abort_unless($admin->hasRole('admin'), 403, 'Anda tidak memiliki akses.');
        abort_if($employee->is($admin), 422, 'Tidak bisa memutuskan laporan sendiri.');
        abort_unless(
            in_array($decision, [KpiSubmissionStatus::Approved, KpiSubmissionStatus::Rejected], true),
            422,
            'Keputusan harus APPROVED atau REJECTED.'
        );

        return DB::transaction(function () use ($admin, $employee, $date, $decision, $notes): Collection {
            $batch = $this->pending($employee, $date)
                ->with(['review', 'positionKpi.kpiIndicator'])
                ->lockForUpdate()
                ->get();

            abort_if($batch->isEmpty(), 404, 'Tidak ada laporan yang menunggu persetujuan akhir pada tanggal tersebut.');

            foreach ($batch as $submission) {
                if ($decision === KpiSubmissionStatus::Approved) {
                    $submission->review->update([
                        'finalized_by' => $admin->id,
                        'finalized_at' => now(),
                        'final_notes'  => $notes,
                    ]);

                    continue;
                }

                // Ditolak Admin: kembali ke karyawan sebagai revisi (semua indikator laporan itu)
                $submission->update(['status' => KpiSubmissionStatus::Rejected]);
                $submission->review->update([
                    'reviewer_id'  => $admin->id,
                    'review_type'  => 'MANUAL',
                    'status'       => KpiSubmissionStatus::Rejected->value,
                    'score'        => null,
                    'notes'        => $notes,
                    'reviewed_at'  => now(),
                    'finalized_by' => null,
                    'finalized_at' => null,
                    'final_notes'  => null,
                ]);
            }

            return $batch->load(['user.role', 'user.position', 'evidences', 'review']);
        });
    }

    private function pending(User $employee, string $date)
    {
        return KpiSubmission::query()
            ->where('user_id', $employee->id)
            ->whereDate('report_date', $date)
            ->where('status', KpiSubmissionStatus::Approved->value)
            ->whereHas('review', fn ($r) => $r->whereNull('finalized_at'));
    }
}