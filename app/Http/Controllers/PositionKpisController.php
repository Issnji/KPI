<?php // app/Http/Controllers/PositionKpisController.php

namespace App\Http\Controllers;

use App\Http\Requests\SyncPositionKpisRequest;
use App\Http\Resources\PositionKpiResource;
use App\Models\Position;
use App\Services\PositionKpiService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PositionKpisController extends Controller
{
    public function __construct(private readonly PositionKpiService $service) {}

    public function sync(SyncPositionKpisRequest $request, Position $position): AnonymousResourceCollection
    {
        return PositionKpiResource::collection(
            $this->service->sync($position, $request->validated('items'))
        )->additional(['message' => "KPI jabatan {$position->name} berhasil diperbarui."]);
    }
}