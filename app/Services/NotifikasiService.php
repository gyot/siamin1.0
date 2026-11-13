<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\PenugasanPegawai;
use App\Models\SertifikatBatch;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Pembuat notifikasi lonceng untuk SIMAIK.
 *
 * Semua metode defensif: kegagalan menyimpan notifikasi dicatat ke log dan
 * TIDAK PERNAH menggagalkan aksi utama (mirip pola push notification lama).
 *
 * Keputusan target penerima:
 * - Perubahan status naskah (disetujui/revisi/ditolak) -> pembuat batch
 *   (sertifikat_batch.id_pengaju, diisi otomatis saat "Simpan = Ajukan").
 *   Batch lama tanpa id_pengaju tidak dikirimi (penerima tidak bisa dipastikan).
 * - Batch diajukan (status -> 'diajukan') -> semua user aktif yang punya akses
 *   menu Pengajuan Sertifikat (menu itu tidak di-gate per role di sidebar, jadi
 *   semua user admin aktif berhak), kecuali pengaju itu sendiri.
 * - Penugasan diubah -> user yang terhubung ke pegawai lewat users.id_pegawai.
 *   Pegawai tanpa user tidak dikirimi (tidak ada hubungan lain yang pasti).
 */
class NotifikasiService
{
    /** Tautan halaman tujuan per tipe. */
    public const TAUTAN_NASKAH = '/admin/peserta';            // Modal Batch Sertifikat (Simpan & Ajukan Ulang)
    public const TAUTAN_PENGAJUAN = '/admin/pengajuan-sertifikat'; // meja reviewer
    public const TAUTAN_PENUGASAN = '/admin/penugasan';

    /**
     * Simpan satu baris notifikasi untuk tiap user penerima (yang benar-benar ada).
     */
    public static function kirim(array $idUsers, string $tipe, string $judul, string $pesan, ?string $tautan = null, ?string $terkaitTipe = null, ?int $terkaitId = null): void
    {
        try {
            $unik = array_values(array_unique(array_filter(array_map('intval', $idUsers))));
            if (empty($unik)) {
                return;
            }

            // Hanya user yang benar-benar tercatat di tabel users (PK id_user).
            $penerima = User::whereIn('id_user', $unik)->pluck('id_user')->all();

            foreach ($penerima as $idUser) {
                Notifikasi::create([
                    'id_user' => $idUser,
                    'tipe' => $tipe,
                    'judul' => $judul,
                    'pesan' => $pesan,
                    'tautan' => $tautan,
                    'terkait_tipe' => $terkaitTipe,
                    'terkait_id' => $terkaitId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[Notifikasi] Gagal menyimpan notifikasi (' . $tipe . '): ' . $e->getMessage());
        }
    }

    /**
     * Pemicu saat status naskah (sertifikat_batch) berubah atau batch baru diajukan.
     * Dipanggil dari hook model SertifikatBatch sehingga SEMUA jalur mutasi
     * (SertifikatController, SertifikatBatchController, ajukan-ulang) terpicu —
     * patch satu-satu jalur rawan terlewat (lihat pitfall "two entry points").
     */
    public static function dariStatusNaskah(SertifikatBatch $batch): void
    {
        $namaKegiatan = $batch->kegiatan?->nama_kegiatan ?? 'kegiatan';

        switch ($batch->status) {
            case 'diajukan':
                self::pengajuanBaru($batch, $namaKegiatan);
                break;
            case 'disetujui':
                self::kirim(
                    [$batch->id_pengaju],
                    'disetujui',
                    'Pengajuan sertifikat disetujui',
                    'Batch sertifikat "' . $namaKegiatan . '" telah disetujui dan dilanjutkan ke proses penerbitan nomor.',
                    self::TAUTAN_NASKAH,
                    'sertifikat_batch',
                    (int) $batch->id_batch
                );
                break;
            case 'revisi':
                self::kirim(
                    [$batch->id_pengaju],
                    'revisi',
                    'Pengajuan sertifikat diminta revisi',
                    'Batch sertifikat "' . $namaKegiatan . '" diminta revisi: ' . ($batch->pesan_revisi ?? '-'),
                    self::TAUTAN_NASKAH,
                    'sertifikat_batch',
                    (int) $batch->id_batch
                );
                break;
            case 'ditolak':
                self::kirim(
                    [$batch->id_pengaju],
                    'ditolak',
                    'Pengajuan sertifikat ditolak',
                    'Batch sertifikat "' . $namaKegiatan . '" ditolak: ' . ($batch->alasan_penolakan ?? '-'),
                    self::TAUTAN_NASKAH,
                    'sertifikat_batch',
                    (int) $batch->id_batch
                );
                break;
            default:
                // terbit / dicabut / draft: di luar tipe notifikasi yang diminta.
                break;
        }
    }

    /**
     * Pengajuan baru (atau ajukan ulang) -> meja reviewer.
     */
    public static function pengajuanBaru(SertifikatBatch $batch, ?string $namaKegiatan = null): void
    {
        $namaKegiatan = $namaKegiatan ?? ($batch->kegiatan?->nama_kegiatan ?? 'kegiatan');

        // Menu "Pengajuan Sertifikat" tidak di-gate per role di sidebar admin,
        // jadi semua user admin aktif dianggap reviewer, kecuali pengaju sendiri.
        $reviewer = User::where('status', 'aktif')
            ->when($batch->id_pengaju, fn ($q) => $q->where('id_user', '!=', $batch->id_pengaju))
            ->pluck('id_user')
            ->all();

        self::kirim(
            $reviewer,
            'pengajuan_baru',
            'Pengajuan sertifikat baru',
            'Batch sertifikat "' . $namaKegiatan . '" diajukan dan menunggu peninjauan.',
            self::TAUTAN_PENGAJUAN,
            'sertifikat_batch',
            (int) $batch->id_batch
        );
    }

    /**
     * Penugasan pegawai dibuat/diubah/dihapus -> user milik pegawai tersebut.
     *
     * @param string $aksi  baru | diperbarui | dialihkan | dihapus
     * @param int|null $idPegawaiLama  pegawai lama saat penugasan dialihkan
     */
    public static function penugasan(PenugasanPegawai $item, string $aksi, ?int $idPegawaiLama = null): void
    {
        $namaKegiatan = $item->kegiatan?->nama_kegiatan ?? 'kegiatan';
        $peran = $item->peran ?? 'panitia';

        switch ($aksi) {
            case 'baru':
                $judul = 'Penugasan baru';
                $pesan = 'Anda ditugaskan pada kegiatan "' . $namaKegiatan . '" sebagai ' . $peran . '.';
                break;
            case 'dialihkan':
                $judul = 'Penugasan dialihkan';
                $pesan = 'Penugasan Anda pada kegiatan "' . $namaKegiatan . '" dialihkan ke pegawai lain.';
                break;
            case 'dihapus':
                $judul = 'Penugasan dihapus';
                $pesan = 'Penugasan Anda pada kegiatan "' . $namaKegiatan . '" sebagai ' . $peran . ' dihapus.';
                break;
            default:
                $judul = 'Penugasan diperbarui';
                $pesan = 'Penugasan Anda pada kegiatan "' . $namaKegiatan . '" diperbarui (peran: ' . $peran . ').';
                break;
        }

        // Hubungan pegawai -> user: kolom users.id_pegawai (FK ke pegawai.id_pegawai).
        $idUsers = User::where('id_pegawai', $item->id_pegawai)->pluck('id_user')->all();
        self::kirim($idUsers, 'penugasan', $judul, $pesan, self::TAUTAN_PENUGASAN, 'penugasan_pegawai', (int) $item->id);

        // Penugasan dialihkan: kabari juga pemegang lama (user milik pegawai lama).
        if ($aksi === 'dialihkan' && $idPegawaiLama && $idPegawaiLama !== (int) $item->id_pegawai) {
            $idUserLama = User::where('id_pegawai', $idPegawaiLama)->pluck('id_user')->all();
            self::kirim(
                $idUserLama,
                'penugasan',
                'Penugasan dialihkan',
                'Penugasan Anda pada kegiatan "' . $namaKegiatan . '" dialihkan ke pegawai lain.',
                self::TAUTAN_PENUGASAN,
                'penugasan_pegawai',
                (int) $item->id
            );
        }
    }
}
