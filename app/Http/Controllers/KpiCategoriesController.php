<?php // app/Http/Controllers/KpiCategoriesController.php

namespace App\Http\Controllers;

use App\Http\Resources\KpiCategoryResource;
use App\Services\KpiCatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class KpiCategoriesController extends Controller
{
    public function __construct(private readonly KpiCatalogService $service) {}

    public function index(): AnonymousResourceCollection
    {
        return KpiCategoryResource::collection($this->service->categoriesWithIndicators());
    }
}