<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GenerateMassalSertifikatRequest;
use App\Http\Requests\Api\GenerateSertifikatRequest;
use App\Http\Resources\SertifikatBatchResource;
use App\Http\Resources\SertifikatPesertaResource;
use App\Models\Peserta;
use App\Models\SertifikatBatch;
use App\Models\SertifikatPeserta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SertifikatPesertaController extends Controller
{
    public function pesertaByKegiatan($id)
    {
        $batches = SertifikatBatch::with(['kegiatan', 'penandatangan'])
            ->where('id_kegiatan', $id)
            ->orderByDesc('created_at')
            ->get();

        $peserta = Peserta::query()
            ->select(['id_peserta', 'id_kegiatan', 'nama_lengkap', 'peran'])
            ->where('id_kegiatan', $id)
            ->with(['sertifikatPeserta' => function ($query) use ($id) {
                $query->select(['id', 'id_batch', 'id_peserta', 'status'])
                    ->whereHas('batch', fn ($batchQuery) => $batchQuery->where('id_kegiatan', $id))
                    ->with(['batch:id_batch,id_kegiatan'])
                    ->orderByDesc('id');
            }])
            ->orderBy('nama_lengkap')
            ->get();

        $data = $peserta->map(function (Peserta $item) {
            $sertifikat = $item->sertifikatPeserta->first();

            return [
                'id_peserta' => $item->id_peserta,
                'nama_lengkap' => $item->nama_lengkap,
                'peran' => $item->peran,
                'status_sertifikat' => $sertifikat?->status ?? 'belum_ada',
                'id_batch' => $sertifikat?->id_batch,
            ];
        });

        return response()->json([
            'success' => true,
            'batch_exists' => $batches->isNotEmpty(),
            'batch' => $batches->isNotEmpty()
                ? new SertifikatBatchResource($batches->first())
                : null,
            'batches' => SertifikatBatchResource::collection($batches),
            'data' => $data,
        ]);
    }

    public function generate(GenerateSertifikatRequest $request)
    {
        $validated = $request->validated();
        $batch = SertifikatBatch::findOrFail($validated['id_batch']);
        $peserta = Peserta::findOrFail($validated['id_peserta']);

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

        $sertifikat->load(['batch.kegiatan', 'batch.penandatangan', 'peserta']);

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

        $sertifikat = SertifikatPeserta::with(['batch.kegiatan', 'batch.penandatangan', 'peserta'])
            ->where('id_batch', $batch->id_batch)
            ->whereIn('id_peserta', $pesertaIds)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Generate sertifikat massal berhasil diproses.',
            'data' => SertifikatPesertaResource::collection($sertifikat),
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['draft', 'terbit', 'dicabut'])],
        ]);

        $sertifikat = SertifikatPeserta::with(['batch.kegiatan', 'batch.penandatangan', 'peserta'])->find($id);

        if (!$sertifikat) {
            return response()->json(['success' => false, 'message' => 'Sertifikat peserta tidak ditemukan.'], 404);
        }

        $sertifikat->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Status sertifikat peserta berhasil diperbarui.',
            'data' => new SertifikatPesertaResource($sertifikat->refresh()->load(['batch.kegiatan', 'batch.penandatangan', 'peserta'])),
        ]);
    }

    public function destroy($id)
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
        $validCount = Peserta::query()
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
