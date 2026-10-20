<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMyKpiSubmissionRequest;
use App\Http\Resources\KpiSubmissionResource;
use App\Services\KpiSubmissionService;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MyKpiSubmissionController extends Controller
{
    public function __construct(private readonly KpiSubmissionService $service) {}

    public function store(StoreMyKpiSubmissionRequest $request): AnonymousResourceCollection
    {
        $submit = $request->boolean('submit');

        $saved = $this->service->saveForUser(
            $request->user(),
            $request->validated('items'),
            $submit,
            $request->filled('date') ? Carbon::parse($request->input('date')) : null,
        );

        return KpiSubmissionResource::collection($saved)->additional([
            'message' => $submit ? 'Capaian KPI berhasil dikirim.' : 'Draft capaian KPI tersimpan.',
        ]);
    }
}