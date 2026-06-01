<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSertifikatBatchRequest;
use App\Http\Requests\Api\UpdateSertifikatBatchRequest;
use App\Http\Resources\SertifikatBatchResource;
use App\Models\SertifikatBatch;

class SertifikatBatchController extends Controller
{
    public function index()
    {
        $data = SertifikatBatch::with(['kegiatan', 'penandatangan'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => SertifikatBatchResource::collection($data),
        ]);
    }

    public function byKegiatan($id)
    {
        $batches = SertifikatBatch::with(['kegiatan', 'penandatangan'])
            ->where('id_kegiatan', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($batches->isEmpty()) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists' => true,
            'data' => SertifikatBatchResource::collection($batches),
        ]);
    }

    public function store(StoreSertifikatBatchRequest $request)
    {
        $batch = SertifikatBatch::create($request->validated())
            ->load(['kegiatan', 'penandatangan']);

        return response()->json([
            'success' => true,
            'message' => 'Batch sertifikat berhasil dibuat.',
            'data' => new SertifikatBatchResource($batch),
        ], 201);
    }

    public function show($id)
    {
        $batch = SertifikatBatch::with(['kegiatan', 'penandatangan', 'sertifikatPeserta.peserta'])
            ->find($id);

        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Batch sertifikat tidak ditemukan.'], 404);
        }

        return response()->json(['success' => true, 'data' => new SertifikatBatchResource($batch)]);
    }

    public function update(UpdateSertifikatBatchRequest $request, $id)
    {
        $batch = SertifikatBatch::find($id);

        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Batch sertifikat tidak ditemukan.'], 404);
        }

        $batch->update($request->validated());
        $batch->load(['kegiatan', 'penandatangan']);

        return response()->json([
            'success' => true,
            'message' => 'Batch sertifikat berhasil diperbarui.',
            'data' => new SertifikatBatchResource($batch),
        ]);
    }

    public function destroy($id)
    {
        $batch = SertifikatBatch::find($id);

        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Batch sertifikat tidak ditemukan.'], 404);
        }

        $batch->delete();

        return response()->json(['success' => true, 'message' => 'Batch sertifikat berhasil dihapus.']);
    }
}
