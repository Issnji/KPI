<?php // app/Http/Controllers/KpiReviewController.php

namespace App\Http\Controllers;

use App\Enums\KpiSubmissionStatus;
use App\Http\Requests\AdminSubmissionIndexRequest;
use App\Http\Requests\ReportDecisionRequest;
use App\Http\Requests\ReviewDecisionRequest;
use App\Http\Resources\AdminKpiSubmissionResource;
use App\Models\KpiSubmission;
use App\Models\User;
use App\Services\AdminKpiSubmissionService;
use App\Services\KpiReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class KpiReviewController extends Controller
{
    public function __construct(
        private readonly KpiReviewService $service,
        private readonly AdminKpiSubmissionService $detailService,
    ) {}

    // ---------------- Alur utama: per laporan karyawan ----------------

    /** Daftar laporan yang menunggu keputusan. */
    public function reports(AdminSubmissionIndexRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->reportQueue(
                $request->user(),
                $request->validated(),
                (int) $request->input('per_page', 15),
            )
        );
    }

    /** Isi laporan (semua indikator + bukti foto) sebelum diputuskan. */
    public function reportShow(Request $request, User $user, string $date): AnonymousResourceCollection
    {
        return AdminKpiSubmissionResource::collection(
            $this->service->reportDetail($request->user(), $user, $date)
        );
    }

    /** Approve / reject satu laporan utuh. */
    public function reportDecide(ReportDecisionRequest $request, User $user, string $date): AnonymousResourceCollection
    {
        $decision = KpiSubmissionStatus::from($request->validated('decision'));

        $saved = $this->service->decideReport(
            $request->user(), $user, $date, $decision, $request->validated('notes'),
        );

        return AdminKpiSubmissionResource::collection($saved)->additional([
            'affected_count' => $saved->count(),
            'message'        => $decision === KpiSubmissionStatus::Approved
                ? "Laporan {$user->name} tanggal {$date} disetujui ({$saved->count()} indikator)."
                : "Laporan {$user->name} tanggal {$date} ditolak ({$saved->count()} indikator).",
        ]);
    }

    // ---------------- Opsional: per indikator / bulk ----------------

    public function queue(AdminSubmissionIndexRequest $request): AnonymousResourceCollection
    {
        return AdminKpiSubmissionResource::collection(
            $this->service->queue($request->user(), $request->validated(), (int) $request->input('per_page', 15))
        );
    }

    public function show(KpiSubmission $kpiSubmission): AdminKpiSubmissionResource
    {
        Gate::authorize('review', $kpiSubmission);

        return new AdminKpiSubmissionResource($this->detailService->detail($kpiSubmission));
    }

    public function store(ReviewDecisionRequest $request, KpiSubmission $kpiSubmission): AnonymousResourceCollection
    {
        return $this->respond($request, [$kpiSubmission->id]);
    }

    public function bulk(ReviewDecisionRequest $request): AnonymousResourceCollection
    {
        return $this->respond($request, $request->validated('submission_ids'));
    }

    private function respond(ReviewDecisionRequest $request, array $ids): AnonymousResourceCollection
    {
        $decision = KpiSubmissionStatus::from($request->validated('decision'));

        $saved = $this->service->decide(
            $request->user(), $ids, $decision, $request->validated('notes'),
        );

        return AdminKpiSubmissionResource::collection($saved)->additional([
            'affected_count' => $saved->count(),
            'message'        => $decision === KpiSubmissionStatus::Approved
                ? "Seluruh laporan disetujui ({$saved->count()} indikator)."
                : "Laporan ditolak: {$saved->count()} indikator dikembalikan ke karyawan.",
        ]);
    }
}