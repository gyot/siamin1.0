<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\RiwayatSertifikat;
use App\Models\SertifikatBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur pengajuan penerbitan sertifikat (naskah):
 * diajukan -> revisi | ditolak | disetujui -> terbit (proses nomor).
 */
class AlurPengajuanSertifikatTest extends TestCase
{
    use RefreshDatabase;

    private function login()
    {
        $user = new User();
        $user->id = 99;
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    private function buatBatch(array $overrides = []): SertifikatBatch
    {
        $kegiatan = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan Uji Alur']);

        return SertifikatBatch::forceCreate(array_merge([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'nomor_sertifikat' => 'draft',
            'status' => 'diajukan',
            'tanggal_diajukan' => now(),
        ], $overrides));
    }

    public function test_store_membuat_batch_diajukan_dengan_nomor_draft(): void
    {
        $this->login();
        $kegiatan = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan Simpan']);

        $response = $this->postJson('/api/v1/sertifikat-batch', [
            'id_kegiatan' => $kegiatan->id_kegiatan,
        ]);

        $response->assertStatus(201);
        $data = $response->json('data');
        $this->assertSame('draft', $data['nomor_sertifikat']);
        $this->assertSame('diajukan', $data['status']);
        $this->assertNotNull($data['tanggal_diajukan']);
        $this->assertSame(now()->toDateString(), substr($data['tanggal_ttd'], 0, 10));
    }

    public function test_setujui_dari_diajukan(): void
    {
        $this->login();
        $batch = $this->buatBatch();

        $response = $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/setujui");

        $response->assertStatus(200);
        $this->assertSame('disetujui', $response->json('data.status'));
        $this->assertNotNull($batch->fresh()->tanggal_persetujuan);
        $this->assertSame(1, RiwayatSertifikat::where('tipe', 'naskah')->where('id_target', $batch->id_batch)->count());
    }

    public function test_aksi_peninjauan_hanya_dari_diajukan(): void
    {
        $this->login();
        $batch = $this->buatBatch(['status' => 'disetujui']);

        foreach (['setujui', 'revisi', 'tolak'] as $aksi) {
            $body = $aksi === 'revisi' ? ['pesan_revisi' => 'x'] : ($aksi === 'tolak' ? ['alasan_penolakan' => 'x'] : []);
            $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/{$aksi}", $body)->assertStatus(422);
        }
    }

    public function test_revisi_wajib_pesan_dan_menyimpan_pesan(): void
    {
        $this->login();
        $batch = $this->buatBatch();

        $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/revisi", [])->assertStatus(422);

        $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/revisi", ['pesan_revisi' => 'Perbaiki judul kegiatan'])
            ->assertStatus(200);

        $batch->refresh();
        $this->assertSame('revisi', $batch->status);
        $this->assertSame('Perbaiki judul kegiatan', $batch->pesan_revisi);
    }

    public function test_tolak_wajib_alasan_dan_menyimpan_alasan(): void
    {
        $this->login();
        $batch = $this->buatBatch();

        $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/tolak", [])->assertStatus(422);

        $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/tolak", ['alasan_penolakan' => 'Data peserta tidak valid'])
            ->assertStatus(200);

        $batch->refresh();
        $this->assertSame('ditolak', $batch->status);
        $this->assertSame('Data peserta tidak valid', $batch->alasan_penolakan);
    }

    public function test_proses_mengisi_nomor_dan_tanggal_proses(): void
    {
        $this->login();
        $batch = $this->buatBatch(['status' => 'disetujui', 'tanggal_persetujuan' => now()]);

        $response = $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/proses", [
            'nomor_sertifikat' => '001/BB/2026',
        ]);

        $response->assertStatus(200);
        $batch->refresh();
        $this->assertSame('terbit', $batch->status);
        $this->assertSame('001/BB/2026', $batch->nomor_sertifikat);
        $this->assertSame(now()->toDateString(), $batch->tanggal_ttd->toDateString());
        $this->assertNotNull($batch->tanggal_proses);
    }

    public function test_proses_nomor_kembar_ditolak(): void
    {
        $this->login();
        $this->buatBatch(['nomor_sertifikat' => '001/BB/2026', 'status' => 'terbit']);
        $batch = $this->buatBatch(['status' => 'disetujui']);

        $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/proses", [
            'nomor_sertifikat' => '001/BB/2026',
        ])->assertStatus(422);
    }

    public function test_proses_hanya_dari_disetujui(): void
    {
        $this->login();
        $batch = $this->buatBatch();

        $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/proses", [
            'nomor_sertifikat' => '002/BB/2026',
        ])->assertStatus(422);
    }

    public function test_update_terkunci_saat_diajukan(): void
    {
        $this->login();
        $batch = $this->buatBatch();

        $this->putJson("/api/v1/sertifikat-batch/{$batch->id_batch}", [
            'id_kegiatan' => $batch->id_kegiatan,
            'nomor_sertifikat' => 'draft',
        ])->assertStatus(422);

        $this->assertSame('diajukan', $batch->fresh()->status);
    }

    public function test_update_dari_revisi_mengajukan_ulang(): void
    {
        $this->login();
        $batch = $this->buatBatch(['status' => 'revisi', 'pesan_revisi' => 'perbaiki']);

        $this->putJson("/api/v1/sertifikat-batch/{$batch->id_batch}", [
            'id_kegiatan' => $batch->id_kegiatan,
            'nomor_sertifikat' => 'draft',
        ])->assertStatus(200);

        $batch->refresh();
        $this->assertSame('diajukan', $batch->status);
    }

    public function test_ajukan_ulang_dari_ditolak(): void
    {
        $this->login();
        $batch = $this->buatBatch(['status' => 'ditolak', 'alasan_penolakan' => 'x']);

        $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/ajukan-ulang")->assertStatus(200);
        $this->assertSame('diajukan', $batch->fresh()->status);

        // dari diajukan tidak bisa ajukan ulang
        $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/ajukan-ulang")->assertStatus(422);
    }
}
