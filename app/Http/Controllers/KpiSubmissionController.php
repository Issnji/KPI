<?php

namespace App\Http\Controllers;

use App\Models\KpiSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class KpiSubmissionController extends Controller
{
    public function index(): JsonResponse
    {
        $submissions = KpiSubmission::with(['positionKpi', 'user'])->get();

        return response()->json([
            'data' => $submissions,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'position_kpi_id' => 'required|exists:position_kpis,id',
            'user_id' => 'required|exists:users,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'answer_yes_no' => 'nullable|boolean',
            'numeric_value' => 'nullable|numeric',
            'status' => 'required|string',
        ]);

        $submission = KpiSubmission::create($validated);

        return response()->json([
            'message' => 'KPI submission berhasil dibuat',
            'data' => $submission,
        ], 201);
    }

    public function show(KpiSubmission $kpiSubmission): JsonResponse
    {
        $kpiSubmission->load(['positionKpi', 'user', 'evidences', 'review']);

        return response()->json([
            'data' => $kpiSubmission,
        ]);
    }

    public function update(Request $request, KpiSubmission $kpiSubmission): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => 'sometimes|date',
            'period_end' => 'sometimes|date|after_or_equal:period_start',
            'answer_yes_no' => 'nullable|boolean',
            'numeric_value' => 'nullable|numeric',
            'status' => 'sometimes|string',
        ]);

        $kpiSubmission->update($validated);

        return response()->json([
            'message' => 'KPI submission berhasil diperbarui',
            'data' => $kpiSubmission,
        ]);
    }

    public function destroy(KpiSubmission $kpiSubmission): JsonResponse
    {
        $kpiSubmission->delete();

        return response()->json([
            'message' => 'KPI submission berhasil dihapus',
        ]);
    }
}