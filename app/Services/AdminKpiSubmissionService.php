<?php // app/Services/AdminKpiSubmissionService.php

namespace App\Services;

use App\Models\KpiSubmission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminKpiSubmissionService
{
    /** @param array<string, mixed> $f */
    public function paginate(array $f, int $perPage = 15): LengthAwarePaginator
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
            ->when($f['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($f['role_id'] ?? null, fn ($q, $v) => $q->whereHas('user', fn ($u) => $u->where('role_id', $v)))
            ->when($f['position_id'] ?? null, fn ($q, $v) => $q->whereHas('user', fn ($u) => $u->where('position_id', $v)))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['date_from'] ?? null, fn ($q, $v) => $q->whereDate('period_start', '>=', $v))
            ->when($f['date_to'] ?? null, fn ($q, $v) => $q->whereDate('period_end', '<=', $v))
            ->orderByDesc('period_start')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function detail(KpiSubmission $submission): KpiSubmission
    {
        return $submission->load([
            'user.role:id,name',
            'user.position:id,name',
            'positionKpi.kpiIndicator',
            'evidences',
            'review.reviewer:id,name',
        ]);
    }
}