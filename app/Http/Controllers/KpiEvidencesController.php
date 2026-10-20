<?php

namespace App\Http\Controllers;

use App\Models\KpiSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KpiEvidencesController extends Controller
{
    public function storeForSubmission(Request $request, KpiSubmission $kpiSubmission): JsonResponse
    {
        abort_unless($kpiSubmission->user_id === $request->user()->id, 403, 'Anda tidak memiliki akses.');
        abort_unless($kpiSubmission->status->isEditable(), 422, 'Submission sudah dikirim, bukti tidak bisa diubah.');

        $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5 MB
        ]);

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
}