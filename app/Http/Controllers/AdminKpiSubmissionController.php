<?php // app/Http/Controllers/AdminKpiSubmissionController.php

namespace App\Http\Controllers;

use App\Http\Requests\AdminSubmissionIndexRequest;
use App\Http\Resources\AdminKpiSubmissionResource;
use App\Models\KpiSubmission;
use App\Services\AdminKpiSubmissionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminKpiSubmissionController extends Controller
{
    public function __construct(private readonly AdminKpiSubmissionService $service) {}

    public function index(AdminSubmissionIndexRequest $request): AnonymousResourceCollection
    {
        return AdminKpiSubmissionResource::collection(
            $this->service->paginate($request->validated(), (int) $request->input('per_page', 15))
        );
    }

    public function show(KpiSubmission $kpiSubmission): AdminKpiSubmissionResource
    {
        return new AdminKpiSubmissionResource($this->service->detail($kpiSubmission));
    }
}