<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Kelas;
use App\Models\Peserta;
use App\Models\RiwayatSertifikat;
use App\Models\SertifikatBatch;
use App\Models\SertifikatPeserta;
use App\Models\User;
use App\Models\UserAksesMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Alur pengajuan sertifikat level 2: status sertifikat per peserta pada satu batch.
 * - GET   /api/v1/sertifikat-batch/{id}/peserta-status
 * - PATCH /api/v1/sertifikat-batch/{id}/status-peserta
 * - POST  /api/v1/sertifikat-batch/{id}/terbitkan
 */
class StatusSertifikatPesertaBatchTest extends TestCase
{
    use RefreshDatabase;

    private function login(): User
    {
        $user = new User();
        $user->id_user = 99;
        // Status peserta batch = menu proses-sertifikat (lihat peta route di routes/api.php).
        UserAksesMenu::forceCreate(['id_user' => 99, 'menu' => 'proses-sertifikat']);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    private function buatBatch(Kegiatan $kegiatan, array $overrides = []): SertifikatBatch
    {
        return SertifikatBatch::forceCreate(array_merge([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'nomor_sertifikat' => 'draft',
            'status' => 'diajukan',
            'tanggal_diajukan' => now(),
        ], $overrides));
    }

    private function buatPeserta(Kegiatan $kegiatan, string $nama, array $overrides = []): Peserta
    {
        return Peserta::forceCreate(array_merge([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'nama_lengkap' => $nama,
            'nama_instansi' => 'SMP Negeri 1 Contoh',
        ], $overrides));
    }

    private function buatSertifikat(SertifikatBatch $batch, Peserta $peserta, string $status, ?string $alasan = null): SertifikatPeserta
    {
        return SertifikatPeserta::forceCreate([
            'id_batch' => $batch->id_batch,
            'id_peserta' => $peserta->id_peserta,
            'qr_token' => Str::uuid()->toString(),
            'status' => $status,
            'alasan_cabut' => $alasan,
        ]);
    }

    // ------------------------------------------------------------------
    // GET peserta-status
    // ------------------------------------------------------------------

    public function test_peserta_status_mengembalikan_daftar_dan_ringkasan(): void
    {
        $this->login();
        $kegiatan = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan Status', 'status' => 'berjalan']);
        $batch = $this->buatBatch($kegiatan);

        $kelas = Kelas::forceCreate(['id_kegiatan' => $kegiatan->id_kegiatan, 'nama_kelas' => 'Kelas A']);

        $pTerbit = $this->buatPeserta($kegiatan, 'Andi');
        $pDraft = $this->buatPeserta($kegiatan, 'Budi');
        $pCabut = $this->buatPeserta($kegiatan, 'Citra');
        $pBelum = $this->buatPeserta($kegiatan, 'Dedi');
        $pTerbit->kelas()->attach($kelas->id_kelas);

        $sTerbit = $this->buatSertifikat($batch, $pTerbit, 'terbit');
        $sDraft = $this->buatSertifikat($batch, $pDraft, 'draft');
        $sCabut = $this->buatSertifikat($batch, $pCabut, 'dicabut', 'Ganda');

        // Peserta kegiatan lain tidak boleh muncul.
        $kegiatanLain = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan Lain']);
        $this->buatPeserta($kegiatanLain, 'Eka');

        $response = $this->getJson("/api/v1/sertifikat-batch/{$batch->id_batch}/peserta-status");

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertCount(4, $response->json('data'));

        $rows = collect($response->json('data'))->keyBy('id_peserta');

        $this->assertSame('Andi', $rows[$pTerbit->id_peserta]['nama_lengkap']);
        $this->assertSame('SMP Negeri 1 Contoh', $rows[$pTerbit->id_peserta]['nama_instansi']);
        $this->assertSame('berjalan', $rows[$pTerbit->id_peserta]['status_kegiatan']);
        $this->assertSame($kelas->id_kelas, $rows[$pTerbit->id_peserta]['id_kelas']);
        $this->assertSame('Kelas A', $rows[$pTerbit->id_peserta]['nama_kelas']);
        $this->assertSame($sTerbit->id, $rows[$pTerbit->id_peserta]['id_sertifikat_peserta']);
        $this->assertSame('terbit', $rows[$pTerbit->id_peserta]['status_sertifikat']);

        $this->assertSame($sDraft->id, $rows[$pDraft->id_peserta]['id_sertifikat_peserta']);
        $this->assertSame('draft', $rows[$pDraft->id_peserta]['status_sertifikat']);

        $this->assertSame($sCabut->id, $rows[$pCabut->id_peserta]['id_sertifikat_peserta']);
        $this->assertSame('dicabut', $rows[$pCabut->id_peserta]['status_sertifikat']);

        $this->assertNull($rows[$pBelum->id_peserta]['id_sertifikat_peserta']);
        $this->assertSame('belum', $rows[$pBelum->id_peserta]['status_sertifikat']);
        $this->assertNull($rows[$pBelum->id_peserta]['id_kelas']);
        $this->assertNull($rows[$pBelum->id_peserta]['nama_kelas']);

        $this->assertSame([
            'total' => 4,
            'terbit' => 1,
            'draft' => 1,
            'dicabut' => 1,
            'belum' => 1,
        ], $response->json('ringkasan'));
    }

    public function test_peserta_status_butuh_login(): void
    {
        $kegiatan = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan Tanpa Login']);
        $batch = $this->buatBatch($kegiatan);

        $this->getJson("/api/v1/sertifikat-batch/{$batch->id_batch}/peserta-status")->assertStatus(401);
        $this->patchJson("/api/v1/sertifikat-batch/{$batch->id_batch}/status-peserta")->assertStatus(401);
        $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/terbitkan")->assertStatus(401);
    }

    // ------------------------------------------------------------------
    // PATCH status-peserta
    // ------------------------------------------------------------------

    public function test_status_peserta_membuat_baris_baru_dari_belum(): void
    {
        $this->login();
        $kegiatan = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan Buat Baris']);
        $batch = $this->buatBatch($kegiatan);
        $peserta = $this->buatPeserta($kegiatan, 'Andi');

        $this->assertDatabaseCount('sertifikat_peserta', 0);

        $response = $this->patchJson("/api/v1/sertifikat-batch/{$batch->id_batch}/status-peserta", [
            'id_pilih' => [$peserta->id_peserta],
            'status' => 'draft',
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);

        $sertifikat = SertifikatPeserta::where('id_batch', $batch->id_batch)
            ->where('id_peserta', $peserta->id_peserta)->first();
        $this->assertNotNull($sertifikat);
        $this->assertSame('draft', $sertifikat->status);
        $this->assertNotNull($sertifikat->qr_token);

        $riwayat = RiwayatSertifikat::where('tipe', 'peserta')->where('id_target', $sertifikat->id)->get();
        $this->assertCount(1, $riwayat);
        $this->assertSame('belum', $riwayat->first()->status_lama);
        $this->assertSame('draft', $riwayat->first()->status_baru);
        $this->assertSame(99, $riwayat->first()->id_pelaku);
        $this->assertSame($batch->id_batch, $riwayat->first()->id_batch);

        $this->assertSame(['total' => 1, 'terbit' => 0, 'draft' => 1, 'dicabut' => 0, 'belum' => 0], $response->json('ringkasan'));
    }

    public function test_status_peserta_idempoten_tidak_mencatat_riwayat_ganda(): void
    {
        $this->login();
        $kegiatan = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan Idempoten']);
        $batch = $this->buatBatch($kegiatan);
        $peserta = $this->buatPeserta($kegiatan, 'Andi');

        $body = ['id_pilih' => [$peserta->id_peserta], 'status' => 'draft'];

        $this->patchJson("/api/v1/sertifikat-batch/{$batch->id_batch}/status-peserta", $body)->assertStatus(200);
        $kedua = $this->patchJson("/api/v1/sertifikat-batch/{$batch->id_batch}/status-peserta", $body);

        $kedua->assertStatus(200)->assertJsonPath('success', true)->assertJsonPath('diubah', 0);
        $this->assertSame(1, SertifikatPeserta::where('id_batch', $batch->id_batch)->count());
        $this->assertSame(1, RiwayatSertifikat::where('tipe', 'peserta')->count());
    }

    public function test_cabut_wajib_alasan_dan_menyimpan_alasan(): void
    {
        $this->login();
        $kegiatan = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan Cabut']);
        $batch = $this->buatBatch($kegiatan);
        $peserta = $this->buatPeserta($kegiatan, 'Andi');
        $sertifikat = $this->buatSertifikat($batch, $peserta, 'terbit');

        // Tanpa alasan_cabut → 422, tidak ada perubahan.
        $this->patchJson("/api/v1/sertifikat-batch/{$batch->id_batch}/status-peserta", [
            'id_pilih' => [$peserta->id_peserta],
            'status' => 'dicabut',
        ])->assertStatus(422);

        $this->assertSame('terbit', $sertifikat->fresh()->status);
        $this->assertSame(0, RiwayatSertifikat::count());

        // Dengan alasan → dicabut, alasan tersimpan, riwayat tercatat.
        $response = $this->patchJson("/api/v1/sertifikat-batch/{$batch->id_batch}/status-peserta", [
            'id_pilih' => [$peserta->id_peserta],
            'status' => 'dicabut',
            'alasan_cabut' => 'Diterbitkan keliru',
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
        $sertifikat->refresh();
        $this->assertSame('dicabut', $sertifikat->status);
        $this->assertSame('Diterbitkan keliru', $sertifikat->alasan_cabut);

        $riwayat = RiwayatSertifikat::where('tipe', 'peserta')->where('id_target', $sertifikat->id)->first();
        $this->assertNotNull($riwayat);
        $this->assertSame('terbit', $riwayat->status_lama);
        $this->assertSame('dicabut', $riwayat->status_baru);
        $this->assertSame('Diterbitkan keliru', $riwayat->catatan);

        $this->assertSame(['total' => 1, 'terbit' => 0, 'draft' => 0, 'dicabut' => 1, 'belum' => 0], $response->json('ringkasan'));
    }

    // ------------------------------------------------------------------
    // POST terbitkan
    // ------------------------------------------------------------------

    public function test_terbitkan_membuat_baris_dan_mencatat_riwayat(): void
    {
        $this->login();
        $kegiatan = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan Terbitkan']);
        $batch = $this->buatBatch($kegiatan);
        $peserta = $this->buatPeserta($kegiatan, 'Andi');

        $response = $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/terbitkan", [
            'id_peserta' => $peserta->id_peserta,
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);

        $sertifikat = SertifikatPeserta::where('id_batch', $batch->id_batch)
            ->where('id_peserta', $peserta->id_peserta)->first();
        $this->assertNotNull($sertifikat);
        $this->assertSame('terbit', $sertifikat->status);
        $this->assertNotNull($sertifikat->qr_token);

        $riwayat = RiwayatSertifikat::where('tipe', 'peserta')->where('id_target', $sertifikat->id)->first();
        $this->assertNotNull($riwayat);
        $this->assertSame('terbitkan', $riwayat->aksi);
        $this->assertSame('belum', $riwayat->status_lama);
        $this->assertSame('terbit', $riwayat->status_baru);

        $this->assertSame(['total' => 1, 'terbit' => 1, 'draft' => 0, 'dicabut' => 0, 'belum' => 0], $response->json('ringkasan'));
    }

    public function test_terbitkan_idempoten_dengan_penanda_sudah_terbit(): void
    {
        $this->login();
        $kegiatan = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan Terbitkan Ulang']);
        $batch = $this->buatBatch($kegiatan);
        $peserta = $this->buatPeserta($kegiatan, 'Andi');
        $body = ['id_peserta' => $peserta->id_peserta];

        $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/terbitkan", $body)->assertStatus(200);

        $kedua = $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/terbitkan", $body);

        $kedua->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('sudah_terbit', true);
        $this->assertSame(1, SertifikatPeserta::where('id_batch', $batch->id_batch)->count());
        $this->assertSame(1, RiwayatSertifikat::where('tipe', 'peserta')->count());
    }

    // ------------------------------------------------------------------
    // Peserta kegiatan lain tidak bisa dimanipulasi
    // ------------------------------------------------------------------

    public function test_peserta_kegiatan_lain_tidak_bisa_dimanipulasi(): void
    {
        $this->login();
        $kegiatan = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan A']);
        $batch = $this->buatBatch($kegiatan);
        $pesertaSendiri = $this->buatPeserta($kegiatan, 'Andi');

        $kegiatanLain = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan B']);
        $pesertaLain = $this->buatPeserta($kegiatanLain, 'Eka');

        // PATCH: id_pilih berisi peserta kegiatan lain → 422, tidak ada baris yang dibuat.
        $this->patchJson("/api/v1/sertifikat-batch/{$batch->id_batch}/status-peserta", [
            'id_pilih' => [$pesertaLain->id_peserta],
            'status' => 'draft',
        ])->assertStatus(422);

        // POST terbitkan: peserta kegiatan lain → 422, tidak ada baris yang dibuat.
        $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/terbitkan", [
            'id_peserta' => $pesertaLain->id_peserta,
        ])->assertStatus(422);

        $this->assertDatabaseCount('sertifikat_peserta', 0);
        $this->assertSame(0, RiwayatSertifikat::count());

        // Peserta sendiri tetap bisa dimanipulasi.
        $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/terbitkan", [
            'id_peserta' => $pesertaSendiri->id_peserta,
        ])->assertStatus(200);
        $this->assertSame(1, SertifikatPeserta::where('id_batch', $batch->id_batch)->count());
    }
}
