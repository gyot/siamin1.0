<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GenerateMassalSertifikatRequest;
use App\Http\Requests\Api\GenerateSertifikatRequest;
use App\Http\Requests\Api\StoreSertifikatBatchRequest;
use App\Http\Requests\Api\UpdateSertifikatBatchRequest;
use App\Http\Resources\SertifikatPesertaResource;
use App\Http\Resources\SertifikatResource;
use App\Models\SertifikatBatch;
use App\Models\SertifikatPeserta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SertifikatController extends Controller
{
    public function index()
    {
        $batches = SertifikatBatch::with(['kegiatan', 'penandatangan', 'sertifikatPeserta.peserta'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => SertifikatResource::collection($batches),
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
            'message' => 'Sertifikat batch berhasil dibuat.',
            'data' => new SertifikatResource($batch),
        ], 201);
    }

    public function show($id)
    {
        $batch = SertifikatBatch::with(['kegiatan', 'penandatangan', 'sertifikatPeserta.peserta'])
            ->find($id);

        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Sertifikat tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new SertifikatResource($batch),
        ]);
    }

    public function update(UpdateSertifikatBatchRequest $request, $id)
    {
        $batch = SertifikatBatch::find($id);

        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Sertifikat tidak ditemukan.'], 404);
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

        $batch->load(['kegiatan', 'penandatangan', 'sertifikatPeserta.peserta']);

        return response()->json([
            'success' => true,
            'message' => 'Sertifikat batch berhasil diperbarui.',
            'data' => new SertifikatResource($batch),
        ]);
    }

    public function destroy($id)
    {
        $batch = SertifikatBatch::find($id);

        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Sertifikat tidak ditemukan.'], 404);
        }

        if ($batch->template_file && Storage::disk('public')->exists($batch->template_file)) {
            Storage::disk('public')->delete($batch->template_file);
        }

        $batch->delete();

        return response()->json(['success' => true, 'message' => 'Sertifikat berhasil dihapus.']);
    }

    public function generate(GenerateSertifikatRequest $request)
    {
        $validated = $request->validated();
        $batch = SertifikatBatch::findOrFail($validated['id_batch']);
        $peserta = \App\Models\Peserta::findOrFail($validated['id_peserta']);

        $this->ensurePesertaInBatchKegiatan($batch, collect([$peserta->id_peserta]));

        $sertifikat = SertifikatPeserta::updateOrCreate(
            [
                'id_batch' => $batch->id_batch,
                'id_peserta' => $peserta->id_peserta,
            ],
            [
                'updated_at' => now(),
            ]
        );

        if ($sertifikat->wasRecentlyCreated) {
            $sertifikat->forceFill([
                'qr_token' => Str::uuid()->toString(),
                'status' => $validated['status'] ?? 'draft',
            ])->save();
        }

        $sertifikat->load(['batch', 'peserta']);

        return response()->json([
            'success' => true,
            'message' => 'Sertifikat peserta berhasil dibuat.',
            'data' => new SertifikatPesertaResource($sertifikat),
        ], 201);
    }

    public function generateMassal(GenerateMassalSertifikatRequest $request)
    {
        $validated = $request->validated();
        $batch = SertifikatBatch::findOrFail($validated['id_batch']);
        $pesertaIds = collect($validated['peserta_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        $this->ensurePesertaInBatchKegiatan($batch, $pesertaIds);

        DB::transaction(function () use ($batch, $pesertaIds) {
            $now = now();
            $payload = $pesertaIds->map(function ($idPeserta) use ($batch, $now) {
                return [
                    'id_batch' => $batch->id_batch,
                    'id_peserta' => $idPeserta,
                    'qr_token' => Str::uuid()->toString(),
                    'status' => 'draft',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            SertifikatPeserta::upsert(
                $payload,
                ['id_batch', 'id_peserta'],
                ['updated_at']
            );
        });

        $sertifikat = SertifikatPeserta::with(['batch', 'peserta'])
            ->where('id_batch', $batch->id_batch)
            ->whereIn('id_peserta', $pesertaIds)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Generate sertifikat massal berhasil diproses.',
            'data' => SertifikatPesertaResource::collection($sertifikat),
        ], 201);
    }

    public function updatePesertaStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['draft', 'terbit', 'dicabut'])],
        ]);

        $sertifikat = SertifikatPeserta::with(['batch', 'peserta'])->find($id);

        if (!$sertifikat) {
            return response()->json(['success' => false, 'message' => 'Sertifikat peserta tidak ditemukan.'], 404);
        }

        $sertifikat->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Status sertifikat peserta berhasil diperbarui.',
            'data' => new SertifikatPesertaResource($sertifikat->refresh()->load(['batch', 'peserta'])),
        ]);
    }

    public function destroyPeserta($id)
    {
        $sertifikat = SertifikatPeserta::find($id);

        if (!$sertifikat) {
            return response()->json(['success' => false, 'message' => 'Sertifikat peserta tidak ditemukan.'], 404);
        }

        $sertifikat->delete();

        return response()->json(['success' => true, 'message' => 'Sertifikat peserta berhasil dihapus.']);
    }

    private function ensurePesertaInBatchKegiatan(SertifikatBatch $batch, $pesertaIds): void
    {
        $validCount = \App\Models\Peserta::query()
            ->where('id_kegiatan', $batch->id_kegiatan)
            ->whereIn('id_peserta', $pesertaIds)
            ->count();

        if ($validCount !== $pesertaIds->count()) {
            throw ValidationException::withMessages([
                'peserta_ids' => ['Semua peserta harus berasal dari kegiatan yang sama dengan batch sertifikat.'],
            ]);
        }
    }
}
