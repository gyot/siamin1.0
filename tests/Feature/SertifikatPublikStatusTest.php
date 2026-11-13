<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Peserta;
use App\Models\SertifikatBatch;
use App\Models\SertifikatPeserta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Aturan status untuk jalur PUBLIK unduh sertifikat — hanya 'terbit' yang dilayani:
 * - draft / belum ada baris sertifikat → 404 "Sertifikat belum tersedia"
 * - dicabut → 410 "Sertifikat telah dicabut"
 *
 * Jalur yang diuji:
 * - GET /api/v1/sertifikat/peserta/{id_peserta}  (byPeserta)
 * - GET /api/v1/sertifikat/cek/{qr_token}        (showByUUID)
 * - GET /api/v1/sertifikat/{id_peserta}          (show — sumber data generator file PDF)
 */
class SertifikatPublikStatusTest extends TestCase
{
    use RefreshDatabase;

    private function buatFixtures(?string $statusSertifikat): array
    {
        $kegiatan = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan Publik', 'status' => 'berjalan']);
        $batch = SertifikatBatch::forceCreate([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'nomor_sertifikat' => 'draft',
            'status' => 'diajukan',
            'tanggal_diajukan' => now(),
        ]);
        $peserta = Peserta::forceCreate([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'nama_lengkap' => 'Peserta Publik',
            'nama_instansi' => 'SMP Negeri 1 Contoh',
        ]);

        $sertifikat = null;
        if ($statusSertifikat !== null) {
            $sertifikat = SertifikatPeserta::forceCreate([
                'id_batch' => $batch->id_batch,
                'id_peserta' => $peserta->id_peserta,
                'qr_token' => (string) Str::uuid(),
                'status' => $statusSertifikat,
            ]);
        }

        return [$peserta, $sertifikat];
    }

    // ------------------------------------------------------------------
    // GET sertifikat/peserta/{id_peserta} (byPeserta)
    // ------------------------------------------------------------------

    public function test_by_peserta_tanpa_baris_sertifikat_mengembalikan_404(): void
    {
        [$peserta] = $this->buatFixtures(null);

        $response = $this->getJson("/api/v1/sertifikat/peserta/{$peserta->id_peserta}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Sertifikat belum tersedia');
    }

    public function test_by_peserta_status_draft_mengembalikan_404(): void
    {
        [$peserta] = $this->buatFixtures('draft');

        $response = $this->getJson("/api/v1/sertifikat/peserta/{$peserta->id_peserta}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Sertifikat belum tersedia');
    }

    public function test_by_peserta_status_dicabut_mengembalikan_410(): void
    {
        [$peserta] = $this->buatFixtures('dicabut');

        $response = $this->getJson("/api/v1/sertifikat/peserta/{$peserta->id_peserta}");

        $response->assertStatus(410)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Sertifikat telah dicabut');
    }

    public function test_by_peserta_status_terbit_mengembalikan_200(): void
    {
        [$peserta, $sertifikat] = $this->buatFixtures('terbit');

        $response = $this->getJson("/api/v1/sertifikat/peserta/{$peserta->id_peserta}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $sertifikat->id)
            ->assertJsonPath('data.0.status', 'terbit');
    }

    // ------------------------------------------------------------------
    // GET sertifikat/cek/{qr_token} (showByUUID)
    // ------------------------------------------------------------------

    public function test_show_by_uuid_status_draft_mengembalikan_404(): void
    {
        [, $sertifikat] = $this->buatFixtures('draft');

        $response = $this->getJson("/api/v1/sertifikat/cek/{$sertifikat->qr_token}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Sertifikat belum tersedia');
    }

    public function test_show_by_uuid_status_dicabut_mengembalikan_410(): void
    {
        [, $sertifikat] = $this->buatFixtures('dicabut');

        $response = $this->getJson("/api/v1/sertifikat/cek/{$sertifikat->qr_token}");

        $response->assertStatus(410)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Sertifikat telah dicabut');
    }

    public function test_show_by_uuid_status_terbit_mengembalikan_200(): void
    {
        [, $sertifikat] = $this->buatFixtures('terbit');

        $response = $this->getJson("/api/v1/sertifikat/cek/{$sertifikat->qr_token}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $sertifikat->id)
            ->assertJsonPath('data.0.status', 'terbit');
    }

    // ------------------------------------------------------------------
    // GET sertifikat/{id_peserta} (show — sumber data generator file PDF)
    // ------------------------------------------------------------------

    public function test_show_file_generator_status_draft_mengembalikan_404(): void
    {
        [$peserta] = $this->buatFixtures('draft');

        $response = $this->getJson("/api/v1/sertifikat/{$peserta->id_peserta}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Sertifikat belum tersedia');
    }

    public function test_show_file_generator_status_dicabut_mengembalikan_410(): void
    {
        [$peserta] = $this->buatFixtures('dicabut');

        $response = $this->getJson("/api/v1/sertifikat/{$peserta->id_peserta}");

        $response->assertStatus(410)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Sertifikat telah dicabut');
    }

    public function test_show_file_generator_status_terbit_mengembalikan_200(): void
    {
        [$peserta, $sertifikat] = $this->buatFixtures('terbit');

        $response = $this->getJson("/api/v1/sertifikat/{$peserta->id_peserta}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $sertifikat->id)
            ->assertJsonPath('data.0.status', 'terbit');
    }
}
