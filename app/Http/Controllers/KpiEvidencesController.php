<?php

namespace App\Http\Controllers;

use App\Models\KpiEvidence;
use App\Models\KpiSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KpiEvidencesController extends Controller
{
    private const MAX_PER_SUBMISSION = 5;

    public function storeForSubmission(Request $request, KpiSubmission $kpiSubmission): JsonResponse
    {
        $this->authorizeOwner($request, $kpiSubmission);

        $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5 MB
        ]);

        abort_if(
            $kpiSubmission->evidences()->count() >= self::MAX_PER_SUBMISSION,
            422,
            'Maksimal '.self::MAX_PER_SUBMISSION.' foto per indikator.'
        );

        $file = $request->file('file');
        $path = $file->store("kpi-evidences/{$kpiSubmission->user_id}/{$kpiSubmission->id}", 'public');

        $evidence = $kpiSubmission->evidences()->create([
            'uploaded_by' => $request->user()->id,
            'file_name'   => $file->getClientOriginalName(),
            'file_path'   => $path,
            'file_type'   => $file->getClientMimeType(),
        ]);

        return response()->json([
            'message' => 'Bukti berhasil diunggah.',
            'data'    => [
                'id'        => $evidence->id,
                'file_name' => $evidence->file_name,
                'url'       => Storage::disk('public')->url($path),
            ],
        ], 201);
    }

    public function destroy(Request $request, KpiSubmission $kpiSubmission, KpiEvidence $evidence): JsonResponse
    {
        $this->authorizeOwner($request, $kpiSubmission);
        abort_unless($evidence->submission_id === $kpiSubmission->id, 404);

        Storage::disk('public')->delete($evidence->file_path);
        $evidence->delete();

        return response()->json(['message' => 'Bukti dihapus.']);
    }

    private function authorizeOwner(Request $request, KpiSubmission $submission): void
    {
        abort_unless($submission->user_id === $request->user()->id, 403, 'Anda tidak memiliki akses.');
        abort_unless($submission->status->isEditable(), 422, 'Submission sudah dikirim, bukti tidak bisa diubah.');
    }
}