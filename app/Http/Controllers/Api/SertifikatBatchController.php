<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSertifikatBatchRequest;
use App\Http\Requests\Api\UpdateSertifikatBatchRequest;
use App\Http\Resources\SertifikatBatchResource;
use App\Models\SertifikatBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SertifikatBatchController extends Controller
{
    public function index()
    {
        $data = SertifikatBatch::with(['kegiatan', 'penandatangan', 'strukturProgram'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => SertifikatBatchResource::collection($data),
        ]);
    }

    public function byKegiatan($id)
    {
        $batches = SertifikatBatch::with(['kegiatan', 'penandatangan', 'strukturProgram'])
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
        $idBatch = $validated['id_batch'] ?? null;
        $strukturProgram = $validated['struktur_program'] ?? null;
        unset($validated['id_batch'], $validated['struktur_program']);

        // Kompatibilitas untuk klien yang mengirim POST saat menyunting data.
        // POST dengan id_batch tidak boleh membuat batch baru.
        if ($idBatch) {
            return $this->updateExistingBatch($request, $validated, $idBatch, $strukturProgram);
        }

        $storedFile = null;

        // Alur pengajuan: Simpan = Ajukan. Nomor selalu "draft" dan tanggal ttd otomatis
        // hari ini (tanggal sesungguhnya diisi saat tombol Proses oleh persuratan).
        $validated['nomor_sertifikat'] = 'draft';
        $validated['status'] = 'diajukan';
        $validated['tanggal_ttd'] = now()->toDateString();
        $validated['tanggal_diajukan'] = now();
        $validated['id_pengaju'] = $request->user()?->getAuthIdentifier();

        if ($request->hasFile('template_file')) {
            $storedFile = $request->file('template_file')->store('template_sertifikat', 'public');
            $validated['template_file'] = $storedFile;
        }

        try {
            $batch = SertifikatBatch::create($validated)
                ->load(['kegiatan', 'penandatangan']);

            if ($strukturProgram !== null) {
                $this->syncStrukturProgram($batch, $strukturProgram);
            }
            $batch->load('strukturProgram');
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

    /**
     * Sinkronkan struktur program batch (ganti semua: hapus lama + simpan baru).
     * Item: { id_kelas: int|null, materi: string, jumlah_jam: int, urutan?: int }
     */
    private function syncStrukturProgram(SertifikatBatch $batch, array $items): void
    {
        $batch->strukturProgram()->delete();

        foreach (array_values($items) as $i => $item) {
            $materi = trim((string) ($item['materi'] ?? ''));
            if ($materi === '') {
                continue;
            }

            \App\Models\StrukturProgram::create([
                'id_batch' => $batch->id_batch,
                'id_kegiatan' => $batch->id_kegiatan,
                'id_kelas' => $item['id_kelas'] ?? null,
                'materi' => $materi,
                'jumlah_jam' => (int) ($item['jumlah_jam'] ?? 0),
                'urutan' => (int) ($item['urutan'] ?? $i),
            ]);
        }
    }

    private function updateExistingBatch(Request $request, array $validated, int $idBatch, ?array $strukturProgram = null)
    {
        $batch = SertifikatBatch::findOrFail($idBatch);

        // Kunci alur: sama dengan update() — hanya batch yang dikembalikan yang boleh
        // disunting, dan menyimpan ulang = ajukan ulang.
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

        if ($strukturProgram !== null) {
            $this->syncStrukturProgram($batch, $strukturProgram);
        }
        $batch->load(['kegiatan', 'penandatangan', 'strukturProgram']);

        return response()->json([
            'success' => true,
            'message' => 'Batch sertifikat berhasil diperbarui.',
            'data' => new SertifikatBatchResource($batch),
        ]);
    }

    public function show($id)
    {
        $batch = SertifikatBatch::with(['kegiatan', 'penandatangan', 'strukturProgram', 'sertifikatPeserta.peserta'])
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
        $strukturProgram = $validated['struktur_program'] ?? null;
        unset($validated['struktur_program']);

        // Kunci alur: hanya batch yang dikembalikan (revisi/ditolak) yang boleh disunting.
        // Menyimpan ulang = ajukan ulang (status kembali "diajukan").
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

        if ($strukturProgram !== null) {
            $this->syncStrukturProgram($batch, $strukturProgram);
        }
        $batch->load(['kegiatan', 'penandatangan', 'strukturProgram']);

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

    /**
     * Unduh file template sertifikat batch yang sudah diunggah.
     * Pola POST seperti unduh sertifikat: request POST → server mengirim file.
     */
    public function downloadTemplateFile(Request $request)
    {
        $validated = $request->validate([
            'template_file' => ['required', 'string', 'regex:/^(storage\/)?template_sertifikat\/[A-Za-z0-9._-]+$/'],
        ], [
            'template_file.regex' => 'Path template tidak valid.',
        ]);

        $path = preg_replace('#^storage/#', '', $validated['template_file']);

        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['success' => false, 'message' => 'File template tidak ditemukan.'], 404);
        }

        return Storage::disk('public')->download($path, basename($path), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}
