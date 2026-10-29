<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KpiSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'position_kpi_id' => $this->position_kpi_id,
            'period_start'    => $this->period_start?->toDateString(),
            'period_end'      => $this->period_end?->toDateString(),
            'status'          => $this->status->value,
            'answer_yes_no'   => $this->answer_yes_no,
            'numeric_value'   => $this->numeric_value !== null ? (float) $this->numeric_value : null,
            'submitted_at'    => $this->submitted_at,
            'evidences' => $this->whenLoaded('evidences', fn () => $this->evidences->map(fn ($e) => [
                'id'        => $e->id,
                'file_name' => $e->file_name,
                'url'       => \Storage::disk('public')->url($e->file_path),
            ])),
        ];
    }
}