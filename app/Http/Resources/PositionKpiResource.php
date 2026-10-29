<?php // app/Http/Resources/PositionKpiResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionKpiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'requires_evidence' => (bool) $this->kpiIndicator?->requires_evidence,
            'id'               => $this->id,
            'target'           => (float) $this->target,
            'weight'           => (float) $this->weight,
            'frequency'        => $this->frequency,
            'calculation_type' => $this->calculation_type,
            'input_type'       => $this->kpiIndicator?->assessment_type,
            'indicator'        => new KpiIndicatorResource($this->whenLoaded('kpiIndicator')),

            // hanya ada di /my-kpis
            'current_period'   => $this->when(isset($this->resource->current_period), fn () => $this->current_period),
            'submission'       => $this->whenLoaded('currentSubmission', fn () => $this->currentSubmission ? [
                'evidences_count' => $this->currentSubmission->evidences_count ?? 0,
                'id'            => $this->currentSubmission->id,
                'status'        => $this->currentSubmission->status,
                'report_date'   => $this->currentSubmission->report_date?->toDateString(),
                'answer_yes_no' => $this->currentSubmission->answer_yes_no,
                'numeric_value' => $this->currentSubmission->numeric_value !== null
                    ? (float) $this->currentSubmission->numeric_value : null,
                'submitted_at'  => $this->currentSubmission->submitted_at,
                'review'        => $this->currentSubmission->review ? [
                    'status'      => $this->currentSubmission->review->status,
                    'score'       => $this->currentSubmission->review->score !== null
                        ? (float) $this->currentSubmission->review->score : null,
                    'notes'       => $this->currentSubmission->review->notes,
                    'reviewed_at' => $this->currentSubmission->review->reviewed_at,
                ] : null,
            ] : null),
        ];
    }
}