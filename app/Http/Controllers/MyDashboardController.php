<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardDateRequest;
use App\Services\MyDashboardService;
use Illuminate\Http\Resources\Json\JsonResource;

class MyDashboardController extends Controller
{
    public function __construct(private readonly MyDashboardService $service) {}

    public function index(DashboardDateRequest $request): JsonResource
    {
        return new JsonResource($this->service->build($request->user(), $request->targetDate()));
    }
}