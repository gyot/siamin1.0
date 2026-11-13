<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GenerateMassalSertifikatRequest;
use App\Http\Requests\Api\GenerateSertifikatRequest;
use App\Http\Requests\Api\StoreSertifikatBatchRequest;
use App\Http\Requests\Api\UpdateSertifikatBatchRequest;
use App\Http\Resources\SertifikatPesertaResource;
use App\Http\Resources\SertifikatResource;
use App\Models\RiwayatSertifikat;
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
        $batches = SertifikatBatch::with(['kegiatan', 'penandatangan', 'sertifikatPeserta.peserta.tpk', 'sertifikatPeserta.peserta.kelas'])
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
        $idBatch = $validated['id_batch'] ?? null;
        unset($validated['id_batch']);

        // Kompatibilitas untuk klien yang mengirim POST saat menyunting data.
        // POST dengan id_batch tidak boleh membuat batch baru.
        if ($idBatch) {
            return $this->updateExistingBatch($request, $validated, $idBatch);
        }

        $storedFile = null;

        if ($request->hasFile('template_file')) {
            $storedFile = $request->file('template_file')->store('template_sertifikat', 'public');
            $validated['template_file'] = $storedFile;
        }

        try {
            // Alur pengajuan: Simpan = Ajukan (nomor "draft", tanggal ttd otomatis hari ini).
            $validated['nomor_sertifikat'] = 'draft';
            $validated['status'] = 'diajukan';
            $validated['tanggal_ttd'] = now()->toDateString();
            $validated['tanggal_diajukan'] = now();
            $validated['id_pengaju'] = $request->user()?->getAuthIdentifier();
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

    private function updateExistingBatch(Request $request, array $validated, int $idBatch)
    {
        $batch = SertifikatBatch::findOrFail($idBatch);

        // Kunci alur + simpan ulang = ajukan ulang (konsisten dengan SertifikatBatchController).
        if (!in_array($batch->status, ['draft', 'revisi', 'ditolak'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Batch berstatus "' . $batch->status . '" tidak dapat diubah. Menunggu hasil peninjauan atau proses nomor.',
            ], 422);
        }
        $validated['nomor_sertifikat'] = 'draft';
        $validated['status'] = 'diajukan';
        $validated['tanggal_ttd'] = now()->toDateString();
        $validated['tanggal_diajukan'] = now();
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

        $batch->load(['kegiatan', 'penandatangan', 'sertifikatPeserta.peserta.tpk', 'sertifikatPeserta.peserta.kelas']);

        return response()->json([
            'success' => true,
            'message' => 'Sertifikat batch berhasil diperbarui.',
            'data' => new SertifikatResource($batch),
        ]);
    }

    public function show($id)
    {
        $sertifikat = SertifikatPeserta::with(['batch.kegiatan', 'batch.penandatangan', 'batch.strukturProgram', 'peserta.tpk', 'peserta.kelas'])
            ->where('id_peserta', $id)
            ->get();

        // Sumber data generator file PDF (jalur publik) — ikut aturan status.
        return $this->responsUnduhPublik($sertifikat);
    }

    public function showByUUID($id)
    {
        $sertifikat = SertifikatPeserta::with(['batch.kegiatan', 'batch.penandatangan', 'batch.strukturProgram', 'peserta.tpk', 'peserta.kelas'])
            ->where('qr_token', $id)
            ->get();

        return $this->responsUnduhPublik($sertifikat);
    }

    public function update(UpdateSertifikatBatchRequest $request, $id)
    {
        $batch = SertifikatBatch::find($id);

        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Sertifikat tidak ditemukan.'], 404);
        }

        $validated = $request->validated();

        // Kunci alur + simpan ulang = ajukan ulang (konsisten dengan SertifikatBatchController).
        if (!in_array($batch->status, ['draft', 'revisi', 'ditolak'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Batch berstatus "' . $batch->status . '" tidak dapat diubah. Menunggu hasil peninjauan atau proses nomor.',
            ], 422);
        }
        $validated['nomor_sertifikat'] = 'draft';
        $validated['status'] = 'diajukan';
        $validated['tanggal_ttd'] = now()->toDateString();
        $validated['tanggal_diajukan'] = now();

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

        $batch->load(['kegiatan', 'penandatangan', 'sertifikatPeserta.peserta.tpk', 'sertifikatPeserta.peserta.kelas']);

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

        $sertifikat = $this->barisSertifikatPeserta($batch, $peserta, $validated['status'] ?? 'draft');

        $sertifikat->load(['batch.kegiatan', 'batch.penandatangan', 'peserta.tpk', 'peserta.kelas']);

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

        $sertifikat = SertifikatPeserta::with(['batch.kegiatan', 'batch.penandatangan', 'peserta.tpk', 'peserta.kelas'])
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

        $sertifikat = SertifikatPeserta::with(['batch.kegiatan', 'batch.penandatangan', 'peserta.tpk', 'peserta.kelas'])->find($id);

        if (!$sertifikat) {
            return response()->json(['success' => false, 'message' => 'Sertifikat peserta tidak ditemukan.'], 404);
        }

        $sertifikat->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Status sertifikat peserta berhasil diperbarui.',
            'data' => new SertifikatPesertaResource($sertifikat->refresh()->load(['batch.kegiatan', 'batch.penandatangan', 'peserta.tpk', 'peserta.kelas'])),
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

    public function updateStatusByPeserta(Request $request)
    {
        $validated = $request->validate([
            'id_peserta' => ['required', 'integer'],
            'id_kegiatan' => ['nullable', 'integer'],
            'status' => ['required', Rule::in(['draft', 'terbit', 'dicabut'])],
        ]);

        $query = SertifikatPeserta::with(['batch.kegiatan', 'batch.penandatangan', 'peserta.tpk', 'peserta.kelas'])
            ->where('id_peserta', $validated['id_peserta']);

        if (!empty($validated['id_kegiatan'])) {
            $query->whereHas('batch', function ($q) use ($validated) {
                $q->where('id_kegiatan', $validated['id_kegiatan']);
            });
        }

        $sertifikat = $query->first();

        if (!$sertifikat) {
            return response()->json([
                'success' => false,
                'message' => 'Sertifikat peserta tidak ditemukan. Buat sertifikat terlebih dahulu.',
            ], 404);
        }

        $sertifikat->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Status sertifikat berhasil diperbarui.',
            'data' => new SertifikatPesertaResource($sertifikat->refresh()->load(['batch.kegiatan', 'batch.penandatangan', 'peserta.tpk', 'peserta.kelas'])),
        ]);
    }

    /**
     * Get sertifikat data by id_peserta from sertifikat_peserta table
     */
    public function byPeserta($id_peserta)
    {
        try {
            $sertifikat = SertifikatPeserta::with(['batch.kegiatan', 'batch.penandatangan', 'peserta.tpk', 'peserta.kelas'])
                ->where('id_peserta', $id_peserta)
                ->get();

            return $this->responsUnduhPublik($sertifikat);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gerbang aturan status untuk semua jalur PUBLIK unduh sertifikat
     * (byPeserta, showByUUID, dan show — sumber data generator file PDF):
     * hanya sertifikat berstatus 'terbit' yang boleh dilayani.
     * - status 'draft' atau belum ada baris sertifikat → 404 "Sertifikat belum tersedia"
     * - status 'dicabut' → 410 "Sertifikat telah dicabut"
     */
    private function responsUnduhPublik($sertifikat)
    {
        $terbit = $sertifikat->filter(fn ($s) => $s->status === 'terbit')->values();

        if ($terbit->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'data' => SertifikatPesertaResource::collection($terbit),
            ]);
        }

        if ($sertifikat->isNotEmpty() && $sertifikat->every(fn ($s) => $s->status === 'dicabut')) {
            return response()->json([
                'success' => false,
                'message' => 'Sertifikat telah dicabut',
            ], 410);
        }

        return response()->json([
            'success' => false,
            'message' => 'Sertifikat belum tersedia',
        ], 404);
    }

    private function ensurePesertaInBatchKegiatan(SertifikatBatch $batch, $pesertaIds, string $field = 'peserta_ids'): void
    {
        $validCount = \App\Models\Peserta::query()
            ->where('id_kegiatan', $batch->id_kegiatan)
            ->whereIn('id_peserta', $pesertaIds)
            ->count();

        if ($validCount !== $pesertaIds->count()) {
            throw ValidationException::withMessages([
                $field => ['Semua peserta harus berasal dari kegiatan yang sama dengan batch sertifikat.'],
            ]);
        }
    }

    // ------------------------------------------------------------------
    // ALUR PENGAJUAN SERTIFIKAT — level 2: status sertifikat per peserta.
    // Hanya menulis tabel sertifikat_peserta + riwayat_sertifikat.
    // ------------------------------------------------------------------

    /** GET sertifikat-batch/{id}/peserta-status — daftar peserta kegiatan batch + status sertifikatnya. */
    public function pesertaStatus($id)
    {
        $batch = SertifikatBatch::find($id);
        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Batch sertifikat tidak ditemukan.'], 404);
        }

        [$data, $ringkasan] = $this->daftarPesertaStatus($batch);

        return response()->json([
            'success' => true,
            'data' => $data,
            'ringkasan' => $ringkasan,
        ]);
    }

    /** PATCH sertifikat-batch/{id}/status-peserta — bulk ubah status ke draft/dicabut (idempoten). */
    public function ubahStatusPesertaBatch(Request $request, $id)
    {
        $batch = SertifikatBatch::find($id);
        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Batch sertifikat tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'id_pilih' => ['required', 'array', 'min:1'],
            'id_pilih.*' => ['required', 'integer', 'distinct', 'exists:peserta,id_peserta'],
            'status' => ['required', Rule::in(['draft', 'dicabut'])],
            'alasan_cabut' => ['required_if:status,dicabut', 'nullable', 'string', 'max:2000'],
        ]);

        $pesertaIds = collect($validated['id_pilih'])->map(fn ($id) => (int) $id)->unique()->values();
        $this->ensurePesertaInBatchKegiatan($batch, $pesertaIds, 'id_pilih');

        $target = $validated['status'];
        $alasan = $target === 'dicabut' ? ($validated['alasan_cabut'] ?? null) : null;
        $idPelaku = $request->user()?->getAuthIdentifier();
        $diubah = 0;

        DB::transaction(function () use ($batch, $pesertaIds, $target, $alasan, $idPelaku, &$diubah) {
            foreach ($pesertaIds as $idPeserta) {
                $sertifikat = SertifikatPeserta::where('id_batch', $batch->id_batch)
                    ->where('id_peserta', $idPeserta)
                    ->first();
                $lama = $sertifikat?->status ?? 'belum';

                // Idempoten: status dan alasan sudah sama — lewati tanpa riwayat baru.
                if ($sertifikat && $sertifikat->status === $target && $sertifikat->alasan_cabut === $alasan) {
                    continue;
                }

                if (!$sertifikat) {
                    $sertifikat = $this->barisSertifikatPeserta(
                        $batch,
                        \App\Models\Peserta::find($idPeserta),
                        $target,
                        $alasan
                    );
                } else {
                    $sertifikat->update(['status' => $target, 'alasan_cabut' => $alasan]);
                }

                RiwayatSertifikat::catat(
                    'peserta',
                    $sertifikat->id,
                    'ubah-status',
                    $lama,
                    $target,
                    $alasan,
                    $idPelaku,
                    $batch->id_batch
                );
                $diubah++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => $diubah > 0
                ? "Status sertifikat {$diubah} peserta berhasil diperbarui."
                : 'Tidak ada perubahan — status sudah sesuai.',
            'diubah' => $diubah,
            'ringkasan' => $this->ringkasanPesertaStatus($batch),
        ]);
    }

    /** POST sertifikat-batch/{id}/terbitkan — terbitkan sertifikat satu peserta (idempoten). */
    public function terbitkanPeserta(Request $request, $id)
    {
        $batch = SertifikatBatch::find($id);
        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Batch sertifikat tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'id_peserta' => ['required', 'integer', 'exists:peserta,id_peserta'],
        ]);

        $idPeserta = (int) $validated['id_peserta'];
        $this->ensurePesertaInBatchKegiatan($batch, collect([$idPeserta]), 'id_peserta');

        $sertifikat = SertifikatPeserta::where('id_batch', $batch->id_batch)
            ->where('id_peserta', $idPeserta)
            ->first();

        // Idempoten: yang sudah terbit tidak dibuat/diproses ulang.
        if ($sertifikat && $sertifikat->status === 'terbit') {
            return response()->json([
                'success' => true,
                'sudah_terbit' => true,
                'message' => 'Sertifikat peserta sudah terbit sebelumnya.',
                'data' => new SertifikatPesertaResource($sertifikat->load(['batch.kegiatan', 'batch.penandatangan', 'peserta.tpk', 'peserta.kelas'])),
                'ringkasan' => $this->ringkasanPesertaStatus($batch),
            ]);
        }

        $lama = $sertifikat?->status ?? 'belum';

        // Alur generate yang sudah ada: buatkan baris sertifikat (file dibuat generator dari baris ini).
        if (!$sertifikat) {
            $sertifikat = $this->barisSertifikatPeserta($batch, \App\Models\Peserta::find($idPeserta), 'terbit');
        }

        $sertifikat->update(['status' => 'terbit', 'alasan_cabut' => null]);
        RiwayatSertifikat::catat(
            'peserta',
            $sertifikat->id,
            'terbitkan',
            $lama,
            'terbit',
            null,
            $request->user()?->getAuthIdentifier(),
            $batch->id_batch
        );

        return response()->json([
            'success' => true,
            'message' => 'Sertifikat peserta berhasil diterbitkan.',
            'data' => new SertifikatPesertaResource($sertifikat->load(['batch.kegiatan', 'batch.penandatangan', 'peserta.tpk', 'peserta.kelas'])),
            'ringkasan' => $this->ringkasanPesertaStatus($batch),
        ]);
    }

    /**
     * Alur generate yang dipakai generate(), terbitkan, dan bulk status:
     * ambil/buat baris sertifikat_peserta unik (id_batch + id_peserta) dengan qr_token.
     */
    private function barisSertifikatPeserta(SertifikatBatch $batch, \App\Models\Peserta $peserta, string $statusBaru = 'draft', ?string $alasanCabut = null): SertifikatPeserta
    {
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
                'status' => $statusBaru,
                'alasan_cabut' => $alasanCabut,
            ])->save();
        }

        return $sertifikat;
    }

    /** Baris daftar peserta + ringkasan jumlah (total/terbit/draft/dicabut/belum). */
    private function daftarPesertaStatus(SertifikatBatch $batch): array
    {
        $pesertaList = \App\Models\Peserta::query()
            ->with('kelas')
            ->where('id_kegiatan', $batch->id_kegiatan)
            ->orderBy('nama_lengkap')
            ->get();

        $sertifikat = SertifikatPeserta::where('id_batch', $batch->id_batch)->get()->keyBy('id_peserta');

        $ringkasan = ['total' => 0, 'terbit' => 0, 'draft' => 0, 'dicabut' => 0, 'belum' => 0];

        $data = $pesertaList->map(function ($peserta) use ($batch, $sertifikat, &$ringkasan) {
            $s = $sertifikat->get($peserta->id_peserta);
            $statusSertifikat = $s->status ?? 'belum';
            $ringkasan['total']++;
            $ringkasan[$statusSertifikat]++;

            $kelas = $peserta->kelas->firstWhere('id_kegiatan', $batch->id_kegiatan) ?? $peserta->kelas->first();

            return [
                'id_peserta' => $peserta->id_peserta,
                'nama_lengkap' => $peserta->nama_lengkap,
                'nama_instansi' => $peserta->nama_instansi,
                'status_kegiatan' => $batch->kegiatan?->status,
                'id_kelas' => $kelas?->id_kelas,
                'nama_kelas' => $kelas?->nama_kelas,
                'id_sertifikat_peserta' => $s?->id,
                'status_sertifikat' => $statusSertifikat,
            ];
        })->values()->all();

        return [$data, $ringkasan];
    }

    private function ringkasanPesertaStatus(SertifikatBatch $batch): array
    {
        return $this->daftarPesertaStatus($batch)[1];
    }
}
