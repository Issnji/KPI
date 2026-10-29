<?php // app/Http/Resources/AdminKpiSubmissionResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdminKpiSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'report_date'   => $this->report_date?->toDateString(),
            'period_start'  => $this->period_start?->toDateString(),
            'period_end'    => $this->period_end?->toDateString(),
            'status'        => $this->status->value,
            'answer_yes_no' => $this->answer_yes_no,
            'numeric_value' => $this->numeric_value !== null ? (float) $this->numeric_value : null,
            'submitted_at'  => $this->submitted_at,

            'user' => $this->whenLoaded('user', fn () => [
                'id'       => $this->user->id,
                'name'     => $this->user->name,
                'role'     => $this->user->role?->name,
                'position' => $this->user->position?->name,
            ]),

            'indicator' => $this->whenLoaded('positionKpi', fn () => [
                'position_kpi_id'  => $this->positionKpi->id,
                'name'             => $this->positionKpi->kpiIndicator?->name,
                'unit'             => $this->positionKpi->kpiIndicator?->unit,
                'input_type'       => $this->positionKpi->kpiIndicator?->assessment_type,
                'calculation_type' => $this->positionKpi->calculation_type,
                'target'           => (float) $this->positionKpi->target,
                'weight'           => (float) $this->positionKpi->weight,
            ]),

            'evidences_count' => $this->whenCounted('evidences'),
            'evidences'       => $this->whenLoaded('evidences', fn () => $this->evidences->map(fn ($e) => [
                'id'        => $e->id,
                'file_name' => $e->file_name,
                'url'       => Storage::disk('public')->url($e->file_path),
            ])),

            'review' => $this->whenLoaded('review', fn () => $this->review ? [
                'status'      => $this->review->status,
                'review_type' => $this->review->review_type,
                'score'       => $this->review->score !== null ? (float) $this->review->score : null,
                'notes'       => $this->review->notes,
                'reviewed_at' => $this->review->reviewed_at,
            ] : null),
        ];
    }
}