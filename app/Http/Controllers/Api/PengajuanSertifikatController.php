<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RiwayatSertifikat;
use App\Models\SertifikatBatch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Alur pengajuan penerbitan sertifikat (naskah):
 * diajukan -> revisi | ditolak | disetujui -> terbit (proses nomor oleh persuratan).
 */
class PengajuanSertifikatController extends Controller
{
    private const STATUS_DIAJUKAN = 'diajukan';
    private const STATUS_REVISI = 'revisi';
    private const STATUS_DITOLAK = 'ditolak';
    private const STATUS_DISETUJUI = 'disetujui';
    private const STATUS_TERBIT = 'terbit';

    /** Daftar pengajuan untuk meja reviewer (semua status, urut tanggal pengajuan). */
    public function pengajuan(Request $request)
    {
        $query = SertifikatBatch::with(['kegiatan', 'penandatangan'])
            ->withCount(['sertifikatPeserta as jumlah_peserta'])
            ->withCount(['sertifikatPeserta as jumlah_terbit' => fn ($q) => $q->where('status', 'terbit')])
            ->orderByDesc('tanggal_diajukan')
            ->orderByDesc('id_batch');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
            'ringkasan' => $this->ringkasanStatus(),
        ]);
    }

    /** Daftar untuk meja persuratan (yang sudah disetujui + yang sudah terbit, urut tanggal persetujuan). */
    public function prosesList()
    {
        $data = SertifikatBatch::with(['kegiatan', 'penandatangan'])
            ->withCount(['sertifikatPeserta as jumlah_peserta'])
            ->withCount(['sertifikatPeserta as jumlah_terbit' => fn ($q) => $q->where('status', 'terbit')])
            ->whereIn('status', [self::STATUS_DISETUJUI, self::STATUS_TERBIT])
            ->orderByDesc('tanggal_persetujuan')
            ->orderByDesc('id_batch')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function setujui(Request $request, $id)
    {
        $batch = $this->cariBatch($id);
        if ($batch instanceof \Illuminate\Http\JsonResponse) {
            return $batch;
        }

        if ($batch->status !== self::STATUS_DIAJUKAN) {
            return $this->tolakStatus(['diajukan'], $batch->status);
        }

        $lama = $batch->status;
        $batch->update([
            'status' => self::STATUS_DISETUJUI,
            'tanggal_persetujuan' => now(),
        ]);
        RiwayatSertifikat::catat('naskah', $batch->id_batch, 'setujui', $lama, self::STATUS_DISETUJUI, null, $request->user()?->getAuthIdentifier(), $batch->id_batch);

        return $this->sukses('Batch sertifikat disetujui.', $batch);
    }

    public function revisi(Request $request, $id)
    {
        $batch = $this->cariBatch($id);
        if ($batch instanceof \Illuminate\Http\JsonResponse) {
            return $batch;
        }

        if ($batch->status !== self::STATUS_DIAJUKAN) {
            return $this->tolakStatus(['diajukan'], $batch->status);
        }

        $validated = $request->validate([
            'pesan_revisi' => ['required', 'string', 'max:2000'],
        ]);

        $lama = $batch->status;
        $batch->update([
            'status' => self::STATUS_REVISI,
            'pesan_revisi' => $validated['pesan_revisi'],
        ]);
        RiwayatSertifikat::catat('naskah', $batch->id_batch, 'revisi', $lama, self::STATUS_REVISI, $validated['pesan_revisi'], $request->user()?->getAuthIdentifier(), $batch->id_batch);

        return $this->sukses('Batch sertifikat dikembalikan untuk revisi.', $batch);
    }

    public function tolak(Request $request, $id)
    {
        $batch = $this->cariBatch($id);
        if ($batch instanceof \Illuminate\Http\JsonResponse) {
            return $batch;
        }

        if ($batch->status !== self::STATUS_DIAJUKAN) {
            return $this->tolakStatus(['diajukan'], $batch->status);
        }

        $validated = $request->validate([
            'alasan_penolakan' => ['required', 'string', 'max:2000'],
        ]);

        $lama = $batch->status;
        $batch->update([
            'status' => self::STATUS_DITOLAK,
            'alasan_penolakan' => $validated['alasan_penolakan'],
        ]);
        RiwayatSertifikat::catat('naskah', $batch->id_batch, 'tolak', $lama, self::STATUS_DITOLAK, $validated['alasan_penolakan'], $request->user()?->getAuthIdentifier(), $batch->id_batch);

        return $this->sukses('Pengajuan batch sertifikat ditolak.', $batch);
    }

    /** Proses nomor surat oleh persuratan: edit "draft" menjadi nomor resmi, tanggal ttd = tanggal proses. */
    public function proses(Request $request, $id)
    {
        $batch = $this->cariBatch($id);
        if ($batch instanceof \Illuminate\Http\JsonResponse) {
            return $batch;
        }

        if ($batch->status !== self::STATUS_DISETUJUI) {
            return $this->tolakStatus([self::STATUS_DISETUJUI], $batch->status);
        }

        $validated = $request->validate([
            'nomor_sertifikat' => [
                'required', 'string', 'max:150',
                Rule::unique('sertifikat_batch', 'nomor_sertifikat')->ignore($batch->id_batch, 'id_batch'),
            ],
            'tanggal_ttd' => ['nullable', 'date'],
        ]);

        $lama = $batch->status;
        $batch->update([
            'status' => self::STATUS_TERBIT,
            'nomor_sertifikat' => $validated['nomor_sertifikat'],
            'tanggal_ttd' => $validated['tanggal_ttd'] ?? now()->toDateString(),
            'tanggal_proses' => now(),
        ]);
        RiwayatSertifikat::catat('naskah', $batch->id_batch, 'proses', $lama, self::STATUS_TERBIT, 'Nomor: ' . $validated['nomor_sertifikat'], $request->user()?->getAuthIdentifier(), $batch->id_batch);

        return $this->sukses('Sertifikat batch diproses dan bernomor resmi.', $batch);
    }

    /** Ajukan ulang setelah revisi/penolakan (dipanggil saat pemohon menyimpan perubahan). */
    public function ajukanUlang(Request $request, $id)
    {
        $batch = $this->cariBatch($id);
        if ($batch instanceof \Illuminate\Http\JsonResponse) {
            return $batch;
        }

        if (!in_array($batch->status, [self::STATUS_REVISI, self::STATUS_DITOLAK], true)) {
            return $this->tolakStatus([self::STATUS_REVISI, self::STATUS_DITOLAK], $batch->status);
        }

        $lama = $batch->status;
        $batch->update([
            'status' => self::STATUS_DIAJUKAN,
            'tanggal_diajukan' => now(),
        ]);
        RiwayatSertifikat::catat('naskah', $batch->id_batch, 'ajukan-ulang', $lama, self::STATUS_DIAJUKAN, null, $request->user()?->getAuthIdentifier(), $batch->id_batch);

        return $this->sukses('Batch sertifikat diajukan ulang.', $batch);
    }

    private function ringkasanStatus(): array
    {
        $hitung = SertifikatBatch::selectRaw('status, COUNT(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');

        return [
            'diajukan' => (int) ($hitung['diajukan'] ?? 0),
            'revisi' => (int) ($hitung['revisi'] ?? 0),
            'ditolak' => (int) ($hitung['ditolak'] ?? 0),
            'disetujui' => (int) ($hitung['disetujui'] ?? 0),
            'terbit' => (int) ($hitung['terbit'] ?? 0),
        ];
    }

    private function cariBatch($id)
    {
        $batch = SertifikatBatch::find($id);
        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Batch sertifikat tidak ditemukan.'], 404);
        }

        return $batch;
    }

    private function tolakStatus(array $boleh, string $aktual)
    {
        return response()->json([
            'success' => false,
            'message' => 'Aksi tidak dapat dilakukan: status batch saat ini "' . $aktual . '", diharuskan "' . implode('" / "', $boleh) . '".',
        ], 422);
    }

    private function sukses(string $message, SertifikatBatch $batch)
    {
        $batch->load(['kegiatan', 'penandatangan', 'strukturProgram']);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $batch,
        ]);
    }
}
