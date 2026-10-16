<?php // app/Http/Resources/KpiCategoryResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KpiCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'indicators'  => KpiIndicatorResource::collection($this->whenLoaded('kpiIndicators')),
        ];
    }
}