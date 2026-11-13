<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSertifikatBatchRequest;
use App\Http\Requests\Api\UpdateSertifikatBatchRequest;
use App\Http\Resources\SertifikatBatchResource;
use App\Models\SertifikatBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

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

    /**
     * PDF contoh (draft) dari template batch untuk pratinjau peninjau.
     * - Isi memakai data contoh yang diberi tanda jelas (CONTOH ...).
     * - Materi/JP diambil dari struktur_program batch (filter id_kelas bila diberikan,
     *   dengan fallback yang sama seperti generator: kelas umum -> semua baris).
     * - Penandatangan diambil dari data batch.
     * Tidak mengubah data dan tidak memerlukan data peserta.
     */
    public function draftPdf(Request $request, $id)
    {
        $validated = $request->validate([
            'id_kelas' => ['nullable', 'integer'],
        ]);

        $batch = SertifikatBatch::with(['penandatangan', 'strukturProgram'])->find($id);
        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Batch sertifikat tidak ditemukan.'], 404);
        }

        if (!$batch->template_file || !Storage::disk('public')->exists($batch->template_file)) {
            return response()->json(['success' => false, 'message' => 'File template tidak ditemukan.'], 404);
        }

        $idKelas = isset($validated['id_kelas']) ? (int) $validated['id_kelas'] : null;
        $items = $this->strukturUntukDraft($batch, $idKelas);
        $totalJam = array_sum(array_column($items, 'jumlah_jam'));
        $penandatangan = $batch->penandatangan;

        $tags = [
            'nomor_sertifikat' => 'CONTOH NOMOR - DRAFT',
            'tgl_ttd' => 'CONTOH TANGGAL TTD',
            'tanggal_tanda_tangan' => 'CONTOH TANGGAL TTD',
            'tanggal' => 'CONTOH TANGGAL KEGIATAN',
            'tanggal_kegiatan' => 'CONTOH TANGGAL KEGIATAN',
            'nama_kegiatan' => 'CONTOH KEGIATAN',
            'kegiatan' => 'CONTOH KEGIATAN',
            'nama' => 'CONTOH PESERTA - DRAFT',
            'instansi' => 'CONTOH INSTANSI',
            'jabatan' => 'CONTOH JABATAN',
            'nip' => 'CONTOH NIP',
            'peran' => 'CONTOH PERAN',
            'kelas' => 'CONTOH KELAS',
            'lokasi' => 'CONTOH TPK',
            'tpk' => 'CONTOH TPK',
            'kabupaten_kota' => 'CONTOH KABUPATEN/KOTA',
            'qr_token' => 'CONTOH QR - DRAFT',
            'kode_qr' => 'CONTOH QR - DRAFT',
            'status' => 'DRAFT',
            'penanda_tangan' => $penandatangan?->nama ?: 'CONTOH PENANDATANGAN',
            'nama_penandatangan' => $penandatangan?->nama ?: 'CONTOH PENANDATANGAN',
            'jabatan_penandatangan' => $penandatangan?->nama_jabatan ?: 'CONTOH JABATAN PENANDATANGAN',
            'penanda_tangan_nip' => $penandatangan?->nip ?: 'CONTOH NIP PENANDATANGAN',
            'total_jam' => (string) $totalJam,
        ];

        try {
            $pdf = $this->konversiDocxKePdf(
                $this->isiTemplateDocx(Storage::disk('public')->get($batch->template_file), $items, $tags)
            );
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat PDF contoh: ' . $e->getMessage(),
            ], 500);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="draft-sertifikat-' . $batch->id_batch . '.pdf"',
        ]);
    }

    /**
     * Baris struktur_program untuk draft: filter per id_kelas bila diberikan,
     * fallback kelas umum (id_kelas null) lalu semua baris — sama dengan generator.
     */
    private function strukturUntukDraft(SertifikatBatch $batch, ?int $idKelas): array
    {
        $semua = $batch->strukturProgram;

        if ($idKelas !== null) {
            $rows = $semua->where('id_kelas', $idKelas)->values();
            if ($rows->isEmpty()) {
                $rows = $semua->whereNull('id_kelas')->values();
            }
        } else {
            $rows = $semua->whereNull('id_kelas')->values();
        }
        if ($rows->isEmpty()) {
            $rows = $semua->values();
        }

        if ($rows->isEmpty()) {
            return [['materi' => 'CONTOH MATERI - DRAFT', 'jumlah_jam' => 0]];
        }

        return $rows->map(fn ($sp) => [
            'materi' => (string) $sp->materi,
            'jumlah_jam' => (int) $sp->jumlah_jam,
        ])->all();
    }

    /**
     * Isi template docx (docxtemplater) dengan data contoh: loop baris tabel
     * {#struktur_program} diulang per materi, tag sederhana diganti langsung.
     */
    private function isiTemplateDocx(string $docxBinary, array $items, array $tags): string
    {
        $tmpDocx = tempnam(sys_get_temp_dir(), 'simaik-draft-src-');
        file_put_contents($tmpDocx, $docxBinary);

        try {
            $zip = new \ZipArchive();
            if ($zip->open($tmpDocx) !== true) {
                throw new \RuntimeException('Template docx tidak dapat dibuka.');
            }

            $xml = (string) $zip->getFromName('word/document.xml');

            // Tag yang terpecah antar run Word digabung dulu (seperti yang dilakukan
            // docxtemplater), tanpa menyentuh region yang memotong paragraf.
            $xml = (string) preg_replace_callback('/\{[^{}]*\}/s', function ($m) {
                if (str_contains($m[0], '<w:p') || str_contains($m[0], '</w:p')) {
                    return $m[0];
                }

                return trim(strip_tags($m[0]));
            }, $xml);

            $open = '{#struktur_program}';
            $close = '{/struktur_program}';
            if (str_contains($xml, $open) && str_contains($xml, $close)) {
                // Loop dalam satu baris tabel: ulangi seluruh baris per materi.
                $xml = (string) preg_replace_callback(
                    '/<w:tr\b[^>]*>(?:(?!<\/w:tr>).)*?<\/w:tr>/s',
                    function ($m) use ($items, $open, $close) {
                        $row = $m[0];
                        if (!str_contains($row, $open) || !str_contains($row, $close)) {
                            return $row;
                        }
                        $tplRow = str_replace([$open, $close], '', $row);
                        $out = '';
                        foreach ($items as $it) {
                            $out .= $this->gantiTag($tplRow, [
                                'materi' => $it['materi'],
                                'jumlah_jam' => (string) $it['jumlah_jam'],
                            ]);
                        }

                        return $out;
                    },
                    $xml
                );

                // Fallback: loop yang membentang di luar satu baris — ulangi segmen
                // di antara penanda loop per materi.
                if (str_contains($xml, $open)) {
                    $start = strpos($xml, $open);
                    $end = strpos($xml, $close, $start);
                    if ($end !== false) {
                        $inner = substr($xml, $start + strlen($open), $end - $start - strlen($open));
                        $rep = '';
                        foreach ($items as $it) {
                            $rep .= $this->gantiTag($inner, [
                                'materi' => $it['materi'],
                                'jumlah_jam' => (string) $it['jumlah_jam'],
                            ]);
                        }
                        $xml = substr($xml, 0, $start) . $rep . substr($xml, $end + strlen($close));
                    }
                }
            }

            $xml = $this->gantiTag($xml, $tags);

            $zip->addFromString('word/document.xml', $xml);
            $zip->close();

            return (string) file_get_contents($tmpDocx);
        } finally {
            @unlink($tmpDocx);
        }
    }

    private function gantiTag(string $xml, array $tags): string
    {
        foreach ($tags as $key => $value) {
            $xml = str_replace(
                '{' . $key . '}',
                htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                $xml
            );
        }

        return $xml;
    }

    /**
     * Konversi docx -> PDF memakai LibreOffice headless (konverter yang sama
     * dengan layanan generator sertifikat).
     */
    private function konversiDocxKePdf(string $docxBinary): string
    {
        $dir = sys_get_temp_dir() . '/simaik-draft-' . uniqid('', true);
        if (!mkdir($dir) && !is_dir($dir)) {
            throw new \RuntimeException('Gagal membuat direktori kerja sementara.');
        }

        try {
            $nama = 'draft-' . uniqid('', true);
            $docxPath = $dir . '/' . $nama . '.docx';
            file_put_contents($docxPath, $docxBinary);

            $soffice = trim((string) shell_exec('command -v soffice 2>/dev/null'))
                ?: trim((string) shell_exec('command -v libreoffice 2>/dev/null'));
            if ($soffice === '') {
                throw new \RuntimeException('LibreOffice (soffice) tidak tersedia di server.');
            }

            $process = new Process([
                $soffice,
                '--headless',
                '--norestore',
                '-env:UserInstallation=file://' . $dir . '/lo-profile',
                '--convert-to', 'pdf',
                '--outdir', $dir,
                $docxPath,
            ], null, null, null, 120);
            $process->run();

            $pdfPath = $dir . '/' . $nama . '.pdf';
            if (!$process->isSuccessful() || !is_file($pdfPath)) {
                throw new \RuntimeException('Konversi ke PDF gagal: ' . trim($process->getErrorOutput() . ' ' . $process->getOutput()));
            }

            return (string) file_get_contents($pdfPath);
        } finally {
            File::deleteDirectory($dir);
        }
    }
}
