<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OgMetaApiTest extends TestCase
{
    use RefreshDatabase;

    private string $spaPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Minimal SPA shell fixture so tests do not depend on the built frontend.
        $this->spaPath = storage_path('framework/testing/og-spa-index.html');
        File::ensureDirectoryExists(dirname($this->spaPath));
        File::put($this->spaPath, <<<'HTML'
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <title>SIMAIK - Sistem Informasi Administrasi Manajemen Kegiatan</title>
  </head>
  <body>
    <div id="app"></div>
  </body>
</html>
HTML);
        config(['og.spa_index' => $this->spaPath]);
    }

    private function makeKegiatan(array $overrides = []): Kegiatan
    {
        return Kegiatan::create(array_merge([
            'nama_kegiatan' => 'Koordinasi Data Pokok Pendidikan',
            'deskripsi' => 'Koordinasi data pokok pendidikan dengan pemerintah daerah dalam rangka pemutakhiran data.',
            'tanggal_mulai' => '2026-10-21',
            'tanggal_selesai' => '2026-10-22',
            'status' => 'draft',
        ], $overrides));
    }

    public function test_og_meta_for_kegiatan_with_flyer(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('flyers/flyer-test.png', 'fake-image-bytes');

        $kegiatan = $this->makeKegiatan(['flyer' => 'flyers/flyer-test.png']);

        $response = $this->get("/kegiatan/{$kegiatan->id_kegiatan}/koordinasi-data-pokok");

        $response->assertStatus(200);
        $this->assertStringContainsString('text/html', $response->headers->get('Content-Type'));

        $html = $response->getContent();
        $this->assertStringContainsString('property="og:site_name" content="SIMAIK"', $html);
        $this->assertStringContainsString('property="og:title"', $html);
        $this->assertStringContainsString('Koordinasi Data Pokok Pendidikan', $html);
        $this->assertStringContainsString('property="og:type" content="article"', $html);
        $this->assertStringContainsString('property="og:url"', $html);
        $this->assertStringContainsString("/kegiatan/{$kegiatan->id_kegiatan}/koordinasi-data-pokok", $html);
        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $html);
        // og:image points at the public storage URL of the flyer
        $this->assertStringContainsString('property="og:image"', $html);
        $this->assertStringContainsString('storage/flyers/flyer-test.png', $html);
        // <title> replaced with kegiatan title
        $this->assertMatchesRegularExpression('/<title>[^<]*Koordinasi Data Pokok Pendidikan[^<]*<\/title>/', $html);
        // the SPA shell itself is preserved
        $this->assertStringContainsString('<div id="app"></div>', $html);
    }

    public function test_og_meta_falls_back_to_default_image_without_flyer(): void
    {
        $kegiatan = $this->makeKegiatan();

        $response = $this->get("/kegiatan/{$kegiatan->id_kegiatan}/slug");
        $html = $response->getContent();

        $response->assertStatus(200);
        $this->assertStringContainsString('og-default.png', $html);
        $this->assertStringContainsString('Koordinasi Data Pokok Pendidikan', $html);
    }

    public function test_og_meta_for_daftar_peserta_page(): void
    {
        $kegiatan = $this->makeKegiatan();

        $response = $this->get("/daftar-peserta/{$kegiatan->id_kegiatan}/slug");
        $html = $response->getContent();

        $response->assertStatus(200);
        $this->assertStringContainsString('Daftar Pengisi Biodata', $html);
        $this->assertStringContainsString('Koordinasi Data Pokok Pendidikan', $html);
    }

    public function test_og_meta_for_unknown_kegiatan_falls_back_to_site_default(): void
    {
        $response = $this->get('/kegiatan/99999/tidak-ada');
        $html = $response->getContent();

        $response->assertStatus(200);
        $this->assertStringContainsString('SIMAIK', $html);
        $this->assertStringContainsString('og-default.png', $html);
    }

    public function test_og_urls_respect_forwarded_https_scheme(): void
    {
        $kegiatan = $this->makeKegiatan();

        $response = $this->get("/kegiatan/{$kegiatan->id_kegiatan}/slug", [
            'X-Forwarded-Proto' => 'https',
        ]);
        $html = $response->getContent();

        $response->assertStatus(200);
        $this->assertStringContainsString('content="https://', $html);
        $this->assertStringNotContainsString('content="http://', $html);
    }

    public function test_og_meta_description_is_truncated_and_stripped(): void
    {
        $kegiatan = $this->makeKegiatan([
            'deskripsi' => '<p>' . str_repeat('Koordinasi data pokok pendidikan. ', 40) . '</p>',
        ]);

        $response = $this->get("/kegiatan/{$kegiatan->id_kegiatan}/slug");
        $html = $response->getContent();

        $response->assertStatus(200);
        $this->assertStringNotContainsString('<p>', $html);
        $this->assertMatchesRegularExpression('/content="[^"]{1,203}\.\.\."/', $html);
    }
}
