<?php
// app/Http/Resources/KpiHistoryIndicatorResource.php

namespace App\Http\Resources;

use App\Enums\KpiSubmissionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\KpiSubmission */
class KpiHistoryIndicatorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $indicator = $this->positionKpi->kpiIndicator;

        // Review lama bisa tertinggal saat karyawan kirim ulang -> tampilkan hanya jika sudah diputuskan
        $review = in_array($this->status, [KpiSubmissionStatus::Approved, KpiSubmissionStatus::Rejected], true)
            ? $this->review
            : null;

        return [
            'id'              => $this->id,
            'position_kpi_id' => $this->position_kpi_id,
            'name'            => $indicator->name,
            'unit'            => $indicator->unit,
            'input_type'      => $indicator->assessment_type,
            'target'          => (float) $this->positionKpi->target,
            'weight'          => (float) $this->positionKpi->weight,
            'answer_yes_no'   => $this->answer_yes_no,
            'numeric_value'   => $this->numeric_value !== null ? (float) $this->numeric_value : null,
            'status'          => $this->status->value,
            'is_editable'     => $this->status->isEditable(),
            'evidences'       => $this->evidences->map(fn ($e) => [
                'id'        => $e->id,
                'file_name' => $e->file_name,
                'url'       => Storage::disk('public')->url($e->file_path),
            ])->values(),
            'review'          => $review ? [
                'status'      => $review->status,
                'review_type' => $review->review_type, // MANUAL | CASCADE
                'score'       => $review->score !== null ? (float) $review->score : null,
                'notes'       => $review->notes,
                'reviewed_at' => $review->reviewed_at?->toIso8601String(),
            ] : null,
        ];
    }
}