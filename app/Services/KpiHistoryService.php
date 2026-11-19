<?php

namespace App\Services;

use App\Enums\KpiReportStatus;
use App\Enums\KpiSubmissionStatus;
use App\Http\Resources\KpiHistoryIndicatorResource;
use App\Models\KpiSubmission;
use App\Models\PositionKpi;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KpiHistoryService
{
    /**
     * Daftar riwayat: 1 baris = 1 laporan (user login + report_date).
     *
     * @param  array<string, mixed> $f
     * @return array{summary: array<string,int>, data: Collection, meta: array<string,int>}
     */
    public function list(User $user, array $f, int $perPage = 15): array
    {
        $totalExpected = $this->assignedPerCategory($user)->sum();

        // 1 query agregat; per user jumlah laporan kecil (maks ±1 baris/hari)
        $all = KpiSubmission::query()
            ->select(
                'report_date',
                DB::raw('COUNT(*) as filled_count'),
                DB::raw("SUM(CASE WHEN status = 'SUBMITTED' THEN 1 ELSE 0 END) as submitted_count"),
                DB::raw("SUM(CASE WHEN status = 'REJECTED' THEN 1 ELSE 0 END) as rejected_count"),
                DB::raw('MAX(submitted_at) as submitted_at'),
            )
            ->where('user_id', $user->id)
            ->whereNotNull('report_date')
            ->where('status', '!=', KpiSubmissionStatus::Draft->value)
            ->when($f['date_from'] ?? null, fn ($q, $v) => $q->whereDate('report_date', '>=', $v))
            ->when($f['date_to'] ?? null, fn ($q, $v) => $q->whereDate('report_date', '<=', $v))
            ->groupBy('report_date')
            ->orderByDesc('report_date')
            ->get()
            ->map(function ($r) use ($totalExpected): array {
                $status = KpiReportStatus::fromCounts((int) $r->submitted_count, (int) $r->rejected_count);
                $filled = (int) $r->filled_count;

                return [
                    'report_date'  => $r->report_date->toDateString(),
                    'status'       => $status,
                    'filled_count' => $filled,
                    'total_count'  => max($totalExpected, $filled),
                    'submitted_at' => $r->submitted_at
                        ? \Carbon\Carbon::parse($r->submitted_at)->toIso8601String()
                        : null,
                ];
            });

        // Ringkasan chip (Semua / Disetujui / Menunggu / Revisi), tidak terpengaruh filter status
        $counts  = $all->countBy(fn (array $r) => $r['status']->value);
        $summary = [
            'all'      => $all->count(),
            'approved' => $counts[KpiReportStatus::Approved->value] ?? 0,
            'pending'  => $counts[KpiReportStatus::Pending->value] ?? 0,
            'revision' => $counts[KpiReportStatus::Revision->value] ?? 0,
        ];

        $filtered = $all
            ->when($f['status'] ?? null, fn (Collection $c, string $s) => $c->filter(
                fn (array $r) => $r['status']->value === $s
            ))
            ->values();

        $page = max(1, (int) request('page', 1));

        $data = $filtered->forPage($page, $perPage)->map(fn (array $r) => [
            ...$r,
            'status'       => $r['status']->value,
            'status_label' => $r['status']->label(),
        ])->values();

        return [
            'summary' => $summary,
            'data'    => $data,
            'meta'    => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $filtered->count(),
                'last_page'    => max(1, (int) ceil($filtered->count() / $perPage)),
            ],
        ];
    }

    /**
     * Detail satu laporan, dikelompokkan per kategori + catatan revisi.
     *
     * @return array<string, mixed>
     */
    public function detail(User $user, string $date): array
    {
        $items = KpiSubmission::query()
            ->with([
                'positionKpi.kpiIndicator.kpiCategory:id,name',
                'evidences',
                'review.reviewer:id,name,position_id',
                'review.reviewer.position:id,name',
            ])
            ->where('user_id', $user->id)
            ->whereDate('report_date', $date)
            ->where('status', '!=', KpiSubmissionStatus::Draft->value)
            ->orderBy('id')
            ->get();

        abort_if($items->isEmpty(), 404, 'Tidak ada riwayat pada tanggal tersebut.');

        $is = fn (KpiSubmission $s, KpiSubmissionStatus $st): bool => $s->status === $st;

        $status = KpiReportStatus::fromCounts(
            $items->filter(fn ($s) => $is($s, KpiSubmissionStatus::Submitted))->count(),
            $items->filter(fn ($s) => $is($s, KpiSubmissionStatus::Rejected))->count(),
        );

        // Indikator yang memicu penolakan (MANUAL), lawan dari yang ikut ditolak (CASCADE)
        $flagged = fn (KpiSubmission $s): bool => $is($s, KpiSubmissionStatus::Rejected)
            && $s->review?->review_type === 'MANUAL';

        $trigger = $items->first($flagged)
            ?? $items->first(fn ($s) => $is($s, KpiSubmissionStatus::Rejected));

        $revision = ($status === KpiReportStatus::Revision && $trigger?->review) ? [
            'requested_by' => [
                'name'     => $trigger->review->reviewer?->name,
                'position' => $trigger->review->reviewer?->position?->name,
            ],
            'reviewed_at'  => $trigger->review->reviewed_at?->toIso8601String(),
            'notes'        => $trigger->review->notes,
        ] : null;

        $assigned = $this->assignedPerCategory($user);

        $categories = $items
            ->groupBy(fn (KpiSubmission $s) => $s->positionKpi->kpiIndicator->category_id)
            ->map(function (Collection $group, int|string $categoryId) use ($assigned, $flagged): array {
                $category   = $group->first()->positionKpi->kpiIndicator->kpiCategory;
                $needsFix   = $group->contains($flagged);
                $flaggedOne = $group->first($flagged);

                return [
                    'category'       => ['id' => $category->id, 'name' => $category->name],
                    'filled_count'   => $group->count(),
                    'total_count'    => max((int) ($assigned[$categoryId] ?? 0), $group->count()),
                    'needs_revision' => $needsFix,
                    'revision_note'  => $flaggedOne?->review?->notes,
                    'indicators'     => KpiHistoryIndicatorResource::collection($group)->resolve(),
                ];
            })
            ->values();

        return [
            'report_date'  => $date,
            'status'       => $status->value,
            'status_label' => $status->label(),
            'submitted_at' => $items->max('submitted_at')?->toIso8601String(),
            'can_fix'      => $status === KpiReportStatus::Revision,
            'revision'     => $revision,
            'categories'   => $categories,
        ];
    }

    /** @return Collection<int, int> category_id => jumlah KPI aktif jabatan user */
    private function assignedPerCategory(User $user): Collection
    {
        if (! $user->position_id) {
            return collect();
        }

        return PositionKpi::query()
            ->with('kpiIndicator:id,category_id')
            ->where('position_id', $user->position_id)
            ->where('is_active', true)
            ->get()
            ->groupBy(fn (PositionKpi $pk) => $pk->kpiIndicator->category_id)
            ->map->count();
    }
}