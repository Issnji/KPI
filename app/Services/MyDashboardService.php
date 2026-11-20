<?php

namespace App\Services;

use App\Enums\IndicatorState;
use App\Enums\KpiSubmissionStatus as S;
use App\Models\PositionKpi;
use App\Models\User;
use App\Support\KpiScore;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class MyDashboardService
{
    public function __construct(private readonly KpiCatalogService $catalog) {}

    /** @return array<string, mixed> */
    public function build(User $user, CarbonInterface $date): array
    {
        $user->loadMissing('position:id,name');

        $items     = $this->catalog->forUser($user, $date);   // 1 query KPI + 1 query submission
        $threshold = (float) config('kpi.achieved_threshold', 100.0);
        $rows      = $items->map(fn (PositionKpi $pk) => $this->row($pk, $threshold));

        $total  = $rows->count();
        $filled = $rows->where('counted', true)->count();
        $weight = (float) $items->sum('weight');
        $count  = fn (IndicatorState $s): int => $rows->filter(fn (array $r) => $r['state'] === $s)->count();

        $statuses = $items->map(fn (PositionKpi $pk) => $pk->currentSubmission?->status)->filter()->values();
        $has      = fn (S $s): bool => $statuses->contains($s);

        $status = match (true) {
            $statuses->isEmpty() => 'NOT_REPORTED',
            $has(S::Rejected)    => 'REVISION',
            $has(S::Submitted)   => 'PENDING',
            $has(S::Draft)       => 'DRAFT',
            $filled === $total   => 'APPROVED',
            default              => 'IN_PROGRESS',
        };

        $order = array_values(array_unique(array_values(config('kpi.dashboard_groups', []))));

        $groups = $rows->groupBy('group')
            ->sortBy(fn ($g, $label) => ($i = array_search($label, $order, true)) === false ? PHP_INT_MAX : $i)
            ->map(fn (Collection $g, string $label) => [
                'label'      => $label,
                'indicators' => $g->pluck('data')->values()->all(),
            ])
            ->values()
            ->all();

        $submittedAt = $items->map(fn (PositionKpi $pk) => $pk->currentSubmission?->submitted_at)->filter()->max();

        return [
            'date' => $date->toDateString(),
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'position' => $user->position?->name,
            ],
            'report' => [
                'status'            => $status,
                'submitted_at'      => $submittedAt?->toIso8601String(),
                'estimated_minutes' => config('kpi.estimated_minutes'),
            ],
            'progress' => [
                'filled'  => $filled,
                'total'   => $total,
                'percent' => $total > 0 ? round($filled / $total * 100, 1) : 0.0,
            ],
            // rata-rata tertimbang pencapaian (cap 120% per indikator); belum diisi dihitung 0
            'score'   => $filled > 0 && $weight > 0 ? round($rows->sum('weighted') / $weight, 2) : null,
            'summary' => [
                'achieved'     => $count(IndicatorState::Achieved),
                'not_achieved' => $count(IndicatorState::NotAchieved),
                'unfilled'     => $count(IndicatorState::Unfilled),
            ],
            'groups' => $groups,
        ];
    }

    /** @return array{group: string, state: IndicatorState, counted: bool, weighted: float, data: array<string, mixed>} */
    private function row(PositionKpi $pk, float $threshold): array
    {
        $ind = $pk->kpiIndicator;
        $sub = $pk->currentSubmission;
        $st  = $sub?->status;

        // Hanya yang sudah dikirim/disetujui yang dihitung; Draft & Ditolak = belum selesai
        $counted     = in_array($st, [S::Submitted, S::Approved], true);
        $achievement = null;

        if ($counted) {
            $achievement = $st === S::Approved && $sub->review?->score !== null
                ? (float) $sub->review->score
                : KpiScore::achievement($sub, $pk);
        }

        $state = match (true) {
            $achievement === null       => IndicatorState::Unfilled,
            $achievement >= $threshold  => IndicatorState::Achieved,
            default                     => IndicatorState::NotAchieved,
        };

        $category = $ind->kpiCategory?->name ?? 'Lainnya';

        return [
            'group'    => config("kpi.dashboard_groups.$category", $category),
            'state'    => $state,
            'counted'  => $counted,
            'weighted' => $achievement !== null ? $achievement * (float) $pk->weight : 0.0,
            'data'     => [
                'position_kpi_id'   => $pk->id,
                'code'              => $ind->code,
                'name'              => $ind->name,
                'unit'              => $ind->unit,
                'input_type'        => $ind->assessment_type,
                'frequency'         => $pk->frequency,
                'target'            => (float) $pk->target,
                'weight'            => (float) $pk->weight,
                'requires_evidence' => (bool) $ind->requires_evidence,
                'state'             => $state->value,
                'status'            => $st?->value,
                'achievement'       => $achievement,
                'is_editable'       => $sub ? $sub->status->isEditable() : true,
                'evidences_count'   => $sub->evidences_count ?? 0,
            ],
        ];
    }
}