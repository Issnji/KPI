<?php // app/Http/Controllers/KpiIndicatorController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKpiIndicatorRequest;
use App\Http\Requests\UpdateKpiIndicatorRequest;
use App\Http\Resources\KpiIndicatorResource;
use App\Models\KpiIndicator;
use App\Services\KpiIndicatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class KpiIndicatorController extends Controller
{
    public function __construct(private readonly KpiIndicatorService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $f = $request->validate([
            'search'      => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer'],
            'is_active'   => ['nullable', 'boolean'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return KpiIndicatorResource::collection(
            $this->service->paginate($f, (int) ($f['per_page'] ?? 15))
        );
    }

    public function store(StoreKpiIndicatorRequest $request): JsonResponse
    {
        return (new KpiIndicatorResource($this->service->create($request->validated())))
            ->additional(['message' => 'Indikator berhasil ditambahkan.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(KpiIndicator $kpiIndicator): KpiIndicatorResource
    {
        return new KpiIndicatorResource($kpiIndicator->load('kpiCategory'));
    }

    public function update(UpdateKpiIndicatorRequest $request, KpiIndicator $kpiIndicator): KpiIndicatorResource
    {
        return (new KpiIndicatorResource($this->service->update($kpiIndicator, $request->validated())))
            ->additional(['message' => 'Indikator berhasil diperbarui.']);
    }

    public function destroy(KpiIndicator $kpiIndicator): JsonResponse
    {
        $result = $this->service->remove($kpiIndicator);

        return response()->json([
            'message' => $result === 'deleted'
                ? 'Indikator dihapus.'
                : 'Indikator sudah punya riwayat capaian, jadi dinonaktifkan (bukan dihapus).',
            'result'  => $result,
        ]);
    }
}