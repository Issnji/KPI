<?php

namespace App\Http\Controllers;

use App\Enums\KpiSubmissionStatus;
use App\Http\Requests\AdminSubmissionIndexRequest;
use App\Http\Requests\ReportDecisionRequest;
use App\Http\Resources\AdminKpiSubmissionResource;
use App\Models\User;
use App\Services\KpiFinalReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class KpiFinalReviewController extends Controller
{
    public function __construct(private readonly KpiFinalReviewService $service) {}

    public function index(AdminSubmissionIndexRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->queue($request->user(), $request->validated(), (int) $request->input('per_page', 15))
        );
    }

    public function show(User $user, string $date): AnonymousResourceCollection
    {
        return AdminKpiSubmissionResource::collection($this->service->detail($user, $date));
    }

    public function decide(ReportDecisionRequest $request, User $user, string $date): AnonymousResourceCollection
    {
        $decision = KpiSubmissionStatus::from($request->validated('decision'));

        $saved = $this->service->decide($request->user(), $user, $date, $decision, $request->validated('notes'));

        return AdminKpiSubmissionResource::collection($saved)->additional([
            'affected_count' => $saved->count(),
            'message'        => $decision === KpiSubmissionStatus::Approved
                ? "Laporan {$user->name} tanggal {$date} disetujui final."
                : "Laporan {$user->name} tanggal {$date} dikembalikan ke karyawan.",
        ]);
    }
}