<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Notifikasi;
use App\Models\Pegawai;
use App\Models\PenugasanPegawai;
use App\Models\SertifikatBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Fitur lonceng notifikasi real-time:
 * - skema tabel notifikasi
 * - endpoint (20 terbaru + jumlah_belum_dibaca, tandai dibaca, baca-semua)
 * - pemicu otomatis (status naskah, pengajuan baru, penugasan)
 */
class NotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private static int $nomorUser = 0;

    private function buatUser(array $overrides = []): User
    {
        self::$nomorUser++;
        $nama = 'user' . self::$nomorUser;

        return User::forceCreate(array_merge([
            'email' => $nama . '@contoh.test',
            'user' => $nama,
            'password' => 'rahasia',
            'role' => 'operator',
            'status' => 'aktif',
        ], $overrides));
    }

    private function buatBatch(array $overrides = []): SertifikatBatch
    {
        $kegiatan = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan Notifikasi']);

        return SertifikatBatch::forceCreate(array_merge([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'nomor_sertifikat' => 'draft',
            'status' => 'diajukan',
            'tanggal_diajukan' => now(),
        ], $overrides));
    }

    // ===================== SKEMA =====================

    public function test_tabel_notifikasi_memiliki_skema_yang_diharapkan(): void
    {
        $this->assertTrue(Schema::hasTable('notifikasi'));

        foreach (['id', 'id_user', 'tipe', 'judul', 'pesan', 'tautan', 'terkait_tipe', 'terkait_id', 'dibaca_pada', 'created_at', 'updated_at'] as $kolom) {
            $this->assertTrue(Schema::hasColumn('notifikasi', $kolom), "Kolom notifikasi.{$kolom} tidak ditemukan.");
        }
    }

    // ===================== ENDPOINT =====================

    public function test_daftar_notifikasi_memberikan_20_terbaru_dan_jumlah_belum_dibaca(): void
    {
        $user = $this->buatUser();
        foreach (range(1, 25) as $i) {
            Notifikasi::forceCreate([
                'id_user' => $user->id_user,
                'tipe' => 'revisi',
                'judul' => 'Notifikasi ' . $i,
                'pesan' => 'pesan ' . $i,
                'created_at' => now()->subMinutes(100 - $i),
                'updated_at' => now()->subMinutes(100 - $i),
            ]);
        }

        $this->actingAs($user, 'sanctum');
        $response = $this->getJson('/api/v1/notifikasi');

        $response->assertStatus(200);
        $response->assertJsonCount(20, 'data');
        $this->assertSame('Notifikasi 25', $response->json('data.0.judul'));
        $this->assertSame(25, $response->json('jumlah_belum_dibaca'));
    }

    public function test_notifikasi_hanya_milik_user_yang_login(): void
    {
        $pemilik = $this->buatUser();
        $lain = $this->buatUser();
        $milikLain = Notifikasi::forceCreate([
            'id_user' => $lain->id_user,
            'tipe' => 'penugasan',
            'judul' => 'Milik user lain',
            'pesan' => 'x',
        ]);
        $milikSaya = Notifikasi::forceCreate([
            'id_user' => $pemilik->id_user,
            'tipe' => 'penugasan',
            'judul' => 'Milik saya',
            'pesan' => 'x',
        ]);

        $this->actingAs($pemilik, 'sanctum');

        $response = $this->getJson('/api/v1/notifikasi');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertSame('Milik saya', $response->json('data.0.judul'));

        // Notifikasi user lain tidak boleh bisa ditandai lewat akun ini.
        $this->postJson('/api/v1/notifikasi/' . $milikLain->id . '/baca')->assertStatus(404);
        $this->assertNull($milikLain->fresh()->dibaca_pada);
        $this->assertSame('Milik saya', $milikSaya->fresh()->judul);
    }

    public function test_tandai_satu_notifikasi_dibaca(): void
    {
        $user = $this->buatUser();
        $notif = Notifikasi::forceCreate([
            'id_user' => $user->id_user,
            'tipe' => 'disetujui',
            'judul' => 'Uji baca',
            'pesan' => 'x',
        ]);
        Notifikasi::forceCreate([
            'id_user' => $user->id_user,
            'tipe' => 'revisi',
            'judul' => 'Belum dibaca',
            'pesan' => 'x',
        ]);

        $this->actingAs($user, 'sanctum');
        $response = $this->postJson('/api/v1/notifikasi/' . $notif->id . '/baca');

        $response->assertStatus(200);
        $this->assertNotNull($notif->fresh()->dibaca_pada);
        $this->assertSame(1, $response->json('jumlah_belum_dibaca'));

        // Idempoten: dibaca dua kali tidak mengubah hasil.
        $this->postJson('/api/v1/notifikasi/' . $notif->id . '/baca')->assertStatus(200);
        $this->assertSame(1, $this->getJson('/api/v1/notifikasi')->json('jumlah_belum_dibaca'));
    }

    public function test_tandai_semua_notifikasi_dibaca(): void
    {
        $user = $this->buatUser();
        foreach (range(1, 3) as $i) {
            Notifikasi::forceCreate([
                'id_user' => $user->id_user,
                'tipe' => 'pengajuan_baru',
                'judul' => 'Notif ' . $i,
                'pesan' => 'x',
            ]);
        }

        $this->actingAs($user, 'sanctum');
        $response = $this->postJson('/api/v1/notifikasi/baca-semua');

        $response->assertStatus(200);
        $this->assertSame(0, Notifikasi::where('id_user', $user->id_user)->whereNull('dibaca_pada')->count());
        $this->assertSame(0, $this->getJson('/api/v1/notifikasi')->json('jumlah_belum_dibaca'));
    }

    public function test_endpoint_notifikasi_membutuhkan_login(): void
    {
        $this->getJson('/api/v1/notifikasi')->assertStatus(401);
        $this->postJson('/api/v1/notifikasi/1/baca')->assertStatus(401);
        $this->postJson('/api/v1/notifikasi/baca-semua')->assertStatus(401);
    }

    // ===================== PEMICU: STATUS NASKAH =====================

    public function test_perubahan_status_naskah_memberi_notifikasi_ke_pembuat_batch(): void
    {
        $pembuat = $this->buatUser(['email' => 'pembuat@contoh.test', 'user' => 'pembuat']);
        $reviewer = $this->buatUser();
        $this->actingAs($reviewer, 'sanctum');

        // disetujui -> pembuat
        $batchSetujui = $this->buatBatch(['id_pengaju' => $pembuat->id_user]);
        $this->postJson('/api/v1/sertifikat-batch/' . $batchSetujui->id_batch . '/setujui')->assertStatus(200);
        $this->assertDatabaseHas('notifikasi', [
            'id_user' => $pembuat->id_user,
            'tipe' => 'disetujui',
            'terkait_tipe' => 'sertifikat_batch',
            'terkait_id' => $batchSetujui->id_batch,
            'tautan' => '/admin/peserta',
        ]);

        // revisi -> pembuat, memuat pesan revisi
        $batchRevisi = $this->buatBatch(['id_pengaju' => $pembuat->id_user]);
        $this->postJson('/api/v1/sertifikat-batch/' . $batchRevisi->id_batch . '/revisi', ['pesan_revisi' => 'Perbaiki kop surat'])
            ->assertStatus(200);
        $notifRevisi = Notifikasi::where('id_user', $pembuat->id_user)->where('tipe', 'revisi')->where('terkait_id', $batchRevisi->id_batch)->first();
        $this->assertNotNull($notifRevisi);
        $this->assertStringContainsString('Perbaiki kop surat', $notifRevisi->pesan);

        // ditolak -> pembuat, memuat alasan penolakan
        $batchTolak = $this->buatBatch(['id_pengaju' => $pembuat->id_user]);
        $this->postJson('/api/v1/sertifikat-batch/' . $batchTolak->id_batch . '/tolak', ['alasan_penolakan' => 'Data peserta tidak valid'])
            ->assertStatus(200);
        $notifTolak = Notifikasi::where('id_user', $pembuat->id_user)->where('tipe', 'ditolak')->where('terkait_id', $batchTolak->id_batch)->first();
        $this->assertNotNull($notifTolak);
        $this->assertStringContainsString('Data peserta tidak valid', $notifTolak->pesan);
    }

    public function test_batch_tanpa_id_pengaju_tidak_menghasilkan_notifikasi_status(): void
    {
        $reviewer = $this->buatUser();
        $this->actingAs($reviewer, 'sanctum');

        $batch = $this->buatBatch([]); // id_pengaju null (batch lama)
        $this->postJson('/api/v1/sertifikat-batch/' . $batch->id_batch . '/setujui')->assertStatus(200);

        $this->assertSame(0, Notifikasi::where('tipe', 'disetujui')->where('terkait_id', $batch->id_batch)->count());
    }

    // ===================== PEMICU: PENGAJUAN BARU =====================

    public function test_pengajuan_baru_memberi_notifikasi_ke_reviewer_bukan_ke_pengaju(): void
    {
        $pengaju = $this->buatUser();
        $reviewer = $this->buatUser();
        $nonaktif = $this->buatUser(['status' => 'nonaktif']);
        $this->actingAs($pengaju, 'sanctum');

        $kegiatan = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan Pengajuan']);
        $response = $this->postJson('/api/v1/sertifikat-batch', ['id_kegiatan' => $kegiatan->id_kegiatan]);
        $response->assertStatus(201);
        $idBatch = $response->json('data.id_batch');

        // Reviewer aktif mendapat notifikasi, dengan tautan ke meja reviewer.
        $this->assertDatabaseHas('notifikasi', [
            'id_user' => $reviewer->id_user,
            'tipe' => 'pengajuan_baru',
            'terkait_tipe' => 'sertifikat_batch',
            'terkait_id' => $idBatch,
            'tautan' => '/admin/pengajuan-sertifikat',
        ]);

        // Pengaju sendiri dan user nonaktif tidak dikirimi.
        $this->assertSame(0, Notifikasi::where('id_user', $pengaju->id_user)->where('tipe', 'pengajuan_baru')->count());
        $this->assertSame(0, Notifikasi::where('id_user', $nonaktif->id_user)->count());
    }

    public function test_pengajuan_ulang_memicu_notifikasi_pengajuan_baru(): void
    {
        $reviewer = $this->buatUser();
        $pembuat = $this->buatUser();
        $this->actingAs($pembuat, 'sanctum');

        // Jalur 1: endpoint ajukan-ulang (revisi -> diajukan).
        $batch = $this->buatBatch(['id_pengaju' => $pembuat->id_user, 'status' => 'revisi']);
        $this->postJson('/api/v1/sertifikat-batch/' . $batch->id_batch . '/ajukan-ulang')->assertStatus(200);
        $this->assertSame(1, Notifikasi::where('id_user', $reviewer->id_user)->where('tipe', 'pengajuan_baru')->where('terkait_id', $batch->id_batch)->count());

        // Jalur 2: POST sertifikat-batch dengan id_batch (menyimpan ulang = ajukan ulang).
        $batch2 = $this->buatBatch(['id_pengaju' => $pembuat->id_user, 'status' => 'ditolak']);
        $this->postJson('/api/v1/sertifikat-batch', [
            'id_batch' => $batch2->id_batch,
            'id_kegiatan' => $batch2->id_kegiatan,
        ])->assertSuccessful();
        $this->assertSame(1, Notifikasi::where('id_user', $reviewer->id_user)->where('tipe', 'pengajuan_baru')->where('terkait_id', $batch2->id_batch)->count());
    }

    // ===================== PEMICU: PENUGASAN =====================

    public function test_penugasan_baru_memberi_notifikasi_ke_user_yang_terhubung(): void
    {
        $pegawai = Pegawai::forceCreate(['nama' => 'Budi']);
        $pegawaiTanpaUser = Pegawai::forceCreate(['nama' => 'Citra']);
        $user = $this->buatUser(['id_pegawai' => $pegawai->id_pegawai]);
        $this->actingAs($user, 'sanctum');

        $kegiatan = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan Penugasan']);

        // Pegawai dengan user -> notifikasi masuk ke user terkait.
        $response = $this->postJson('/api/v1/penugasan-pegawai', [
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_pegawai' => $pegawai->id_pegawai,
            'peran' => 'ketua_panitia',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('notifikasi', [
            'id_user' => $user->id_user,
            'tipe' => 'penugasan',
            'terkait_tipe' => 'penugasan_pegawai',
            'terkait_id' => $response->json('data.id'),
            'tautan' => '/admin/penugasan',
        ]);

        // Pegawai tanpa user: tidak ada penerima yang bisa dipastikan -> tidak ada notifikasi.
        $respons2 = $this->postJson('/api/v1/penugasan-pegawai', [
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_pegawai' => $pegawaiTanpaUser->id_pegawai,
            'peran' => 'narasumber',
        ]);
        $respons2->assertStatus(201);
        $this->assertSame(0, Notifikasi::where('tipe', 'penugasan')->where('terkait_id', $respons2->json('data.id'))->count());
    }

    public function test_perubahan_dan_penghapusan_penugasan_memicu_notifikasi(): void
    {
        $pegawai = Pegawai::forceCreate(['nama' => 'Dewi']);
        $pegawaiLain = Pegawai::forceCreate(['nama' => 'Eka']);
        $user = $this->buatUser(['id_pegawai' => $pegawai->id_pegawai]);
        $userLain = $this->buatUser(['id_pegawai' => $pegawaiLain->id_pegawai]);
        $this->actingAs($user, 'sanctum');

        $kegiatan = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan Ubah Penugasan']);
        $penugasan = PenugasanPegawai::forceCreate([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_pegawai' => $pegawai->id_pegawai,
            'peran' => 'narasumber',
        ]);

        // Diubah (peran) -> notifikasi "diperbarui" ke user pegawai terkait.
        $this->putJson('/api/v1/penugasan-pegawai/' . $penugasan->id, ['peran' => 'pendamping'])->assertStatus(200);
        $this->assertDatabaseHas('notifikasi', [
            'id_user' => $user->id_user,
            'tipe' => 'penugasan',
            'terkait_id' => $penugasan->id,
            'judul' => 'Penugasan diperbarui',
        ]);

        // Dialihkan ke pegawai lain -> pemegang lama & pemegang baru sama-sama diberi tahu.
        $this->putJson('/api/v1/penugasan-pegawai/' . $penugasan->id, ['id_pegawai' => $pegawaiLain->id_pegawai])
            ->assertStatus(200);
        $this->assertDatabaseHas('notifikasi', [
            'id_user' => $user->id_user,
            'tipe' => 'penugasan',
            'terkait_id' => $penugasan->id,
            'judul' => 'Penugasan dialihkan',
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'id_user' => $userLain->id_user,
            'tipe' => 'penugasan',
            'terkait_id' => $penugasan->id,
            'judul' => 'Penugasan dialihkan',
        ]);

        // Dihapus -> notifikasi "dihapus" ke pemegang terakhir.
        $this->deleteJson('/api/v1/penugasan-pegawai/' . $penugasan->id)->assertStatus(200);
        $this->assertDatabaseHas('notifikasi', [
            'id_user' => $userLain->id_user,
            'tipe' => 'penugasan',
            'terkait_id' => $penugasan->id,
            'judul' => 'Penugasan dihapus',
        ]);
    }
}
