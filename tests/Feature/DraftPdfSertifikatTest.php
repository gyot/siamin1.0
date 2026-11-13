<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Pegawai;
use App\Models\SertifikatBatch;
use App\Models\SertifikatPeserta;
use App\Models\StrukturProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * GET /api/v1/sertifikat-batch/{id}/draft-data — data untuk PDF contoh (draft)
 * dari template batch: diambil layanan generator Node (POST /generate-certificate/draft)
 * yang merender docx -> PDF dengan pipeline yang sama dengan sertifikat peserta.
 * Berisi template_file_url, struktur_program (filter id_kelas opsional, fallback
 * kelas umum), penandatangan, dan data contoh berpenanda CONTOH/DRAFT.
 * Endpoint tidak mengubah data, tidak memerlukan data peserta, dan tidak memakai
 * shell_exec — konversi PDF sepenuhnya di layanan generator.
 */
class DraftPdfSertifikatTest extends TestCase
{
    use RefreshDatabase;

    private function buatBatch(array $overrides = []): SertifikatBatch
    {
        $kegiatan = Kegiatan::forceCreate(['nama_kegiatan' => 'Kegiatan Draft PDF']);
        $penandatangan = Pegawai::forceCreate([
            'nama' => 'Budi Penandatangan',
            'nip' => '198001012010011001',
            'nama_jabatan' => 'Kepala BPMP',
        ]);

        return SertifikatBatch::forceCreate(array_merge([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'id_penandatangan' => $penandatangan->id_pegawai,
            'nomor_sertifikat' => 'draft',
            'status' => 'diajukan',
            'template_file' => 'template_sertifikat/contoh.docx',
            'tanggal_diajukan' => now(),
        ], $overrides));
    }

    private function buatStruktur(SertifikatBatch $batch, ?int $idKelas, string $materi, int $jam, int $urutan): void
    {
        StrukturProgram::forceCreate([
            'id_batch' => $batch->id_batch,
            'id_kegiatan' => $batch->id_kegiatan,
            'id_kelas' => $idKelas,
            'materi' => $materi,
            'jumlah_jam' => $jam,
            'urutan' => $urutan,
        ]);
    }

    public function test_draft_data_mengembalikan_data_contoh_untuk_batch_valid(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('template_sertifikat/contoh.docx', 'isi template');

        $batch = $this->buatBatch();
        $this->buatStruktur($batch, null, 'Materi Umum', 2, 0);

        $response = $this->getJson("/api/v1/sertifikat-batch/{$batch->id_batch}/draft-data");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.id_batch', $batch->id_batch);
        $this->assertStringContainsString(
            'template_sertifikat/contoh.docx',
            (string) $response->json('data.template_file_url')
        );

        // Data contoh berpenanda CONTOH/DRAFT.
        $response->assertJsonPath('data.contoh.nama', 'CONTOH PESERTA - DRAFT');
        $response->assertJsonPath('data.contoh.instansi', 'CONTOH INSTANSI');
        $response->assertJsonPath('data.contoh.status', 'DRAFT');

        // Penandatangan dari data batch (pegawai).
        $response->assertJsonPath('data.penandatangan.nama', 'Budi Penandatangan');
        $response->assertJsonPath('data.penandatangan.nama_jabatan', 'Kepala BPMP');
        $response->assertJsonPath('data.penandatangan.nip', '198001012010011001');

        // Struktur program: kelas umum (id_kelas null).
        $response->assertJsonPath('data.struktur_program.0.materi', 'Materi Umum');
        $response->assertJsonPath('data.struktur_program.0.jumlah_jam', 2);
        $response->assertJsonPath('data.total_jam', 2);

        // Endpoint tidak menulis/mengubah data apa pun.
        $this->assertSame(0, SertifikatPeserta::count());
        $this->assertSame('diajukan', $batch->fresh()->status);
    }

    public function test_draft_data_menerima_filter_id_kelas(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('template_sertifikat/contoh.docx', 'isi template');

        $batch = $this->buatBatch();
        $this->buatStruktur($batch, null, 'Materi Umum', 2, 0);
        $this->buatStruktur($batch, 77, 'Materi Kelas 77', 3, 1);

        $response = $this->getJson("/api/v1/sertifikat-batch/{$batch->id_batch}/draft-data?id_kelas=77");

        $response->assertStatus(200);
        $rows = $response->json('data.struktur_program');
        $this->assertCount(1, $rows);
        $this->assertSame('Materi Kelas 77', $rows[0]['materi']);
        $response->assertJsonPath('data.total_jam', 3);
    }

    public function test_batch_tidak_ada_memberi_404(): void
    {
        $response = $this->getJson('/api/v1/sertifikat-batch/999/draft-data');

        $response->assertStatus(404);
    }

    public function test_template_tidak_ada_memberi_404(): void
    {
        Storage::fake('public');
        $batch = $this->buatBatch();

        $response = $this->getJson("/api/v1/sertifikat-batch/{$batch->id_batch}/draft-data");

        $response->assertStatus(404);
        $response->assertJsonPath('message', 'File template tidak ditemukan.');
    }
}
