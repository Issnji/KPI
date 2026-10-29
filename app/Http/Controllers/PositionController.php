<?php // app/Http/Controllers/PositionController.php

namespace App\Http\Controllers;

use App\Http\Resources\PositionKpiResource;
use App\Models\Position;
use App\Services\KpiCatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

class PositionController extends Controller
{
    public function __construct(private readonly KpiCatalogService $service) {}

    public function kpis(Position $position): AnonymousResourceCollection
    {
        return PositionKpiResource::collection($this->service->forPosition($position->id));
    }

    public function index(): JsonResponse
    {
        return response()->json(['data' => Position::orderBy('name')->get(['id', 'name'])]);
    }
}