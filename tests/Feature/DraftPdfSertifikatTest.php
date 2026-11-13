<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Pegawai;
use App\Models\SertifikatBatch;
use App\Models\SertifikatPeserta;
use App\Models\StrukturProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * POST /api/v1/sertifikat-batch/{id}/draft-pdf — PDF contoh (draft) dari template
 * batch untuk pratinjau peninjau: data contoh bertanda jelas, materi/JP dari
 * struktur_program (filter id_kelas opsional), penandatangan dari data batch.
 * Endpoint tidak mengubah data dan tidak memerlukan data peserta.
 */
class DraftPdfSertifikatTest extends TestCase
{
    use RefreshDatabase;

    private function login(): void
    {
        $user = new User();
        $user->id = 99;
        $this->actingAs($user, 'sanctum');
    }

    /**
     * Template docx minimal berisi tag docxtemplater (termasuk loop baris tabel).
     */
    private function templateDocxBinary(): string
    {
        $docXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            . '<w:p><w:r><w:t>SERTIFIKAT {nomor_sertifikat}</w:t></w:r></w:p>'
            . '<w:p><w:r><w:t>{nama} - {instansi} - {tpk}</w:t></w:r></w:p>'
            . '<w:tbl>'
            . '<w:tr><w:tc><w:p><w:r><w:t>{#struktur_program}{materi}</w:t></w:r></w:p></w:tc>'
            . '<w:tc><w:p><w:r><w:t>{jumlah_jam}{/struktur_program}</w:t></w:r></w:p></w:tc></w:tr>'
            . '</w:tbl>'
            . '<w:p><w:r><w:t>Total Jam: {total_jam} JP</w:t></w:r></w:p>'
            . '<w:p><w:r><w:t>{penanda_tangan} - {jabatan_penandatangan} - {penanda_tangan_nip}</w:t></w:r></w:p>'
            . '</w:body></w:document>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';

        $path = tempnam(sys_get_temp_dir(), 'tpl-draft-');
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('word/document.xml', $docXml);
        $zip->close();

        $binary = (string) file_get_contents($path);
        @unlink($path);

        return $binary;
    }

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

    public function test_draft_pdf_mengembalikan_pdf_untuk_batch_valid(): void
    {
        $this->login();
        Storage::fake('public');
        Storage::disk('public')->put('template_sertifikat/contoh.docx', $this->templateDocxBinary());

        $batch = $this->buatBatch();
        $this->buatStruktur($batch, null, 'Materi Umum', 2, 0);

        $response = $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/draft-pdf");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $content);

        // Endpoint tidak menulis/mengubah data apa pun.
        $this->assertSame(0, SertifikatPeserta::count());
        $this->assertSame('diajukan', $batch->fresh()->status);
    }

    public function test_draft_pdf_menerima_filter_id_kelas(): void
    {
        $this->login();
        Storage::fake('public');
        Storage::disk('public')->put('template_sertifikat/contoh.docx', $this->templateDocxBinary());

        $batch = $this->buatBatch();
        $this->buatStruktur($batch, null, 'Materi Umum', 2, 0);
        $this->buatStruktur($batch, 77, 'Materi Kelas 77', 3, 1);

        $response = $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/draft-pdf", [
            'id_kelas' => 77,
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_batch_tidak_ada_memberi_404(): void
    {
        $this->login();

        $response = $this->postJson('/api/v1/sertifikat-batch/999/draft-pdf');

        $response->assertStatus(404);
    }

    public function test_template_tidak_ada_memberi_404(): void
    {
        $this->login();
        Storage::fake('public');

        $batch = $this->buatBatch();

        $response = $this->postJson("/api/v1/sertifikat-batch/{$batch->id_batch}/draft-pdf");

        $response->assertStatus(404);
    }
}
