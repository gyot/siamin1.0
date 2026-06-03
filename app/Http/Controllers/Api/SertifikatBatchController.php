<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSertifikatBatchRequest;
use App\Http\Requests\Api\UpdateSertifikatBatchRequest;
use App\Http\Resources\SertifikatBatchResource;
use App\Models\SertifikatBatch;
use Illuminate\Support\Facades\Storage;

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
        $validated = $request->validated();
        $storedFile = null;

        if ($request->hasFile('template_file')) {
            $storedFile = $request->file('template_file')->store('template_sertifikat', 'public');
            $validated['template_file'] = $storedFile;
        }

        try {
            $batch = SertifikatBatch::create($validated)
                ->load(['kegiatan', 'penandatangan']);
        } catch (\Throwable $e) {
            if ($storedFile && Storage::disk('public')->exists($storedFile)) {
                Storage::disk('public')->delete($storedFile);
            }

            throw $e;
        }

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

        $validated = $request->validated();
        $oldTemplateFile = $batch->template_file;
        $newTemplateFile = null;
        $deleteOldTemplateFile = false;

        if ($request->hasFile('template_file')) {
            $newTemplateFile = $request->file('template_file')->store('template_sertifikat', 'public');
            $validated['template_file'] = $newTemplateFile;
            $deleteOldTemplateFile = true;
        } elseif ($request->exists('template_file') && blank($request->input('template_file'))) {
            $validated['template_file'] = null;
            $deleteOldTemplateFile = true;
        } else {
            unset($validated['template_file']);
        }

        try {
            $batch->update($validated);
        } catch (\Throwable $e) {
            if ($newTemplateFile && Storage::disk('public')->exists($newTemplateFile)) {
                Storage::disk('public')->delete($newTemplateFile);
            }

            throw $e;
        }

        if ($deleteOldTemplateFile && $oldTemplateFile && Storage::disk('public')->exists($oldTemplateFile)) {
            Storage::disk('public')->delete($oldTemplateFile);
        }

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

        if ($batch->template_file && Storage::disk('public')->exists($batch->template_file)) {
            Storage::disk('public')->delete($batch->template_file);
        }

        $batch->delete();

        return response()->json(['success' => true, 'message' => 'Batch sertifikat berhasil dihapus.']);
    }
}
