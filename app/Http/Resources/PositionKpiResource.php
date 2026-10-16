<?php // app/Http/Resources/PositionKpiResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionKpiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'target'           => (float) $this->target,
            'weight'           => (float) $this->weight,
            'frequency'        => $this->frequency,          // DAILY | WEEKLY | MONTHLY
            'calculation_type' => $this->calculation_type,
            'input_type'       => $this->kpiIndicator?->assessment_type, // FE: tampilkan toggle ya/tidak atau input angka
            'indicator'        => new KpiIndicatorResource($this->whenLoaded('kpiIndicator')),

            // hanya ada di /my-kpis
            'current_period'   => $this->when(isset($this->resource->current_period), fn () => $this->current_period),
            'submission'       => $this->whenLoaded('currentSubmission', fn () => $this->currentSubmission ? [
                'id'            => $this->currentSubmission->id,
                'status'        => $this->currentSubmission->status,
                'answer_yes_no' => $this->currentSubmission->answer_yes_no,
                'numeric_value' => $this->currentSubmission->numeric_value !== null
                    ? (float) $this->currentSubmission->numeric_value : null,
                'submitted_at'  => $this->currentSubmission->submitted_at,
            ] : null),
        ];
    }
}