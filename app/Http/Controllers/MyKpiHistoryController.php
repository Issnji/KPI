<?php
// app/Http/Controllers/MyKpiHistoryController.php

namespace App\Http\Controllers;

use App\Http\Requests\MyKpiHistoryIndexRequest;
use App\Services\KpiHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyKpiHistoryController extends Controller
{
    public function __construct(private readonly KpiHistoryService $service) {}

    /** Halaman Riwayat: chip ringkasan + daftar laporan (filter ?status=APPROVED|PENDING|REVISION). */
    public function index(MyKpiHistoryIndexRequest $request): JsonResponse
    {
        return response()->json($this->service->list(
            $request->user(),
            $request->validated(),
            (int) $request->input('per_page', 15),
        ));
    }

    /** Halaman Detail Riwayat: ringkasan per kategori + catatan revisi. */
    public function show(Request $request, string $date): JsonResponse
    {
        return response()->json(['data' => $this->service->detail($request->user(), $date)]);
    }
}