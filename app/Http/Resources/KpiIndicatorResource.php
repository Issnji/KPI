<?php // app/Http/Resources/KpiIndicatorResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KpiIndicatorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'description'      => $this->description,
            'unit'             => $this->unit,
            'assessment_type'  => $this->assessment_type,   // NUMERIC | YES_NO
            'calculation_type' => $this->calculation_type,  // HIGHER_IS_BETTER | LOWER_IS_BETTER
            'is_active'        => $this->is_active,
            'category'         => new KpiCategoryResource($this->whenLoaded('kpiCategory')),
            'requires_evidence' => $this->requires_evidence,
        ];
    }
}