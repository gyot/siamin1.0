<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * POST /api/v1/sertifikat-batch/template-file — unduh file template sertifikat
 * batch yang sudah diunggah, dengan pola yang sama seperti unduh sertifikat
 * (request POST → server mengirim file).
 */
class TemplateFileDownloadTest extends TestCase
{

    public function test_template_file_terunduh_via_post(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('template_sertifikat/abc123.docx', 'PK-isi-file-docx-palsu');

        $response = $this->postJson('/api/v1/sertifikat-batch/template-file', [
            'template_file' => 'template_sertifikat/abc123.docx',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $response->assertDownload('abc123.docx');
        ob_start();
        $response->sendContent();
        $this->assertSame('PK-isi-file-docx-palsu', ob_get_clean());
    }

    public function test_prefix_storage_dibersihkan(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('template_sertifikat/xyz.docx', 'PK-isi');

        $response = $this->postJson('/api/v1/sertifikat-batch/template-file', [
            'template_file' => 'storage/template_sertifikat/xyz.docx',
        ]);

        $response->assertStatus(200);
        ob_start();
        $response->sendContent();
        $this->assertSame('PK-isi', ob_get_clean());
    }

    public function test_path_tidak_valid_ditolak(): void
    {
        foreach (['../../.env', '/etc/passwd', 'template_sertifikat/../../app/keys', 'scripts/evil.php'] as $path) {
            $response = $this->postJson('/api/v1/sertifikat-batch/template-file', [
                'template_file' => $path,
            ]);
            $response->assertStatus(422);
        }
    }

    public function test_file_tidak_ada_memberi_404(): void
    {
        Storage::fake('public');

        $response = $this->postJson('/api/v1/sertifikat-batch/template-file', [
            'template_file' => 'template_sertifikat/tidak-ada.docx',
        ]);

        $response->assertStatus(404);
    }

    public function test_tanpa_payload_memberi_422(): void
    {
        $response = $this->postJson('/api/v1/sertifikat-batch/template-file', []);

        $response->assertStatus(422);
    }
}
