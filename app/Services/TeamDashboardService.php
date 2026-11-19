<?php

namespace App\Services;

use App\Enums\TeamReportStatus;
use App\Models\KpiSubmission;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeamDashboardService
{
    /** Anggota tim = karyawan aktif (sesuai KpiSubmissionPolicy: SM hanya me-review role karyawan). */
    public function members(User $reviewer): EloquentCollection
    {
        return User::query()
            ->with('position:id,name,description')
            ->where('is_active', true)
            ->where('id', '!=', $reviewer->id)
            ->whereHas('role', fn ($q) => $q->whereRaw('LOWER(name) = ?', ['karyawan']))
            ->get(['id', 'name', 'position_id']);
    }

    /**
     * @param array{status?: string|null, sort?: string|null} $filters
     * @return array<string, mixed>
     */
    public function build(User $reviewer, CarbonInterface $date, array $filters = []): array
    {
        $members   = $this->members($reviewer);
        $statusMap = $this->statusMap($members->pluck('id'), $date);   // 1 query untuk semua anggota

        $rows = $members->map(fn (User $u) => $this->row($u, $statusMap->get($u->id), $date));

        $summary = $this->summary($rows);

        $rows = $rows
            ->when($filters['status'] ?? null, fn (Collection $c, string $v) => $c->filter(
                fn (array $r) => $r['status']->value === $v
            ));

        $name = fn (array $r): string => mb_strtolower($r['data']['user']['name']);

        $rows = ($filters['sort'] ?? 'priority') === 'name'
            ? $rows->sortBy($name)
            : $rows->sortBy(fn (array $r) => $r['status']->priority() . '|' . $name($r));

        return [
            'date'          => $date->toDateString(),
            'summary'       => $summary,
            'pending_count' => $summary['pending'],       // banner "3 Karyawan Menunggu Persetujuan"
            'members_count' => $summary['total'],         // "8 Anggota"
            'members'       => $rows->pluck('data')->values()->all(),
        ];
    }

    /** Untuk badge tab "KPI Tim": jumlah karyawan yang laporannya menunggu pada tanggal tsb. */
    public function pendingCount(User $reviewer, CarbonInterface $date): int
    {
        return KpiSubmission::query()
            ->where('status', 'SUBMITTED')
            ->whereDate('report_date', $date->toDateString())
            ->whereIn('user_id', $this->members($reviewer)->pluck('id'))
            ->distinct()
            ->count('user_id');
    }

    /** @return Collection<int, Collection> user_id => baris agregat per status */
    private function statusMap(Collection $userIds, CarbonInterface $date): Collection
    {
        return KpiSubmission::query()
            ->select(
                'user_id',
                'status',
                DB::raw('COUNT(*) as total'),
                DB::raw('MAX(submitted_at) as submitted_at'),
            )
            ->whereIn('user_id', $userIds)
            ->whereDate('report_date', $date->toDateString())
            ->groupBy('user_id', 'status')
            ->get()
            ->groupBy('user_id');
    }

    /** @return array{status: TeamReportStatus, data: array<string, mixed>} */
    private function row(User $user, ?Collection $agg, CarbonInterface $date): array
    {
        $agg ??= collect();
        $by   = $agg->mapWithKeys(fn ($r) => [$r->status->value => (int) $r->total]);

        // Hanya DRAFT / tidak ada submission = belum lapor
        $status = match (true) {
            $by->get('REJECTED', 0) > 0  => TeamReportStatus::Rejected,
            $by->get('SUBMITTED', 0) > 0 => TeamReportStatus::Pending,
            $by->get('APPROVED', 0) > 0  => TeamReportStatus::Approved,
            default                      => TeamReportStatus::NotReported,
        };

        $reported = $status !== TeamReportStatus::NotReported;

        return [
            'status' => $status,
            'data'   => [
                'user' => [
                    'id'              => $user->id,
                    'name'            => $user->name,
                    'position'        => $user->position?->name,
                    'position_detail' => $user->position?->description,  // "Kasir & Display"
                ],
                'report_date'      => $date->toDateString(),
                'status'           => $status->value,
                'indicators_count' => $reported
                    ? (int) $agg->reject(fn ($r) => $r->status->value === 'DRAFT')->sum('total')
                    : 0,
                'submitted_at'     => $reported ? $agg->max('submitted_at') : null,
                'can_review'       => $status === TeamReportStatus::Pending,
            ],
        ];
    }

    /** @param Collection<int, array{status: TeamReportStatus, data: array}> $rows */
    private function summary(Collection $rows): array
    {
        $count = fn (TeamReportStatus $s): int => $rows->filter(fn (array $r) => $r['status'] === $s)->count();

        $total    = $rows->count();
        $reported = $total - $count(TeamReportStatus::NotReported);

        return [
            'total'            => $total,
            'reported'         => $reported,                                   // "5 dari 8 sudah lapor"
            'reported_percent' => $total > 0 ? round($reported / $total * 100, 1) : 0.0,  // 62.5%
            'pending'          => $count(TeamReportStatus::Pending),
            'approved'         => $count(TeamReportStatus::Approved),
            'rejected'         => $count(TeamReportStatus::Rejected),
            'not_reported'     => $count(TeamReportStatus::NotReported),
        ];
    }
}