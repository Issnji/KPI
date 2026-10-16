<?php // app/Http/Controllers/MyKpiController.php

namespace App\Http\Controllers;

use App\Http\Requests\MyKpiIndexRequest;
use App\Http\Resources\PositionKpiResource;
use App\Services\KpiCatalogService;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MyKpiController extends Controller
{
    public function __construct(private readonly KpiCatalogService $service) {}

    public function index(MyKpiIndexRequest $request): AnonymousResourceCollection
    {
        $date = $request->filled('date') ? Carbon::parse($request->input('date')) : null;

        return PositionKpiResource::collection(
            $this->service->forUser($request->user(), $date)
        );
    }
}