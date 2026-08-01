<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\SertifikatBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SertifikatBatchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_with_id_batch_updates_existing_sertifikat_instead_of_creating_a_new_record(): void
    {
        $kegiatan = Kegiatan::create(['nama_kegiatan' => 'Workshop Sertifikat']);
        $batch = SertifikatBatch::create([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'nomor_sertifikat' => 'SERT-001',
            'status' => 'draft',
        ]);

        $response = $this->postJson('/api/v1/sertifikat', [
            'id_batch' => $batch->id_batch,
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'nomor_sertifikat' => 'SERT-001-REVISI',
            'status' => 'terbit',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id_batch', $batch->id_batch)
            ->assertJsonPath('data.nomor_sertifikat', 'SERT-001-REVISI');

        $this->assertDatabaseCount('sertifikat_batch', 1);
        $this->assertDatabaseHas('sertifikat_batch', [
            'id_batch' => $batch->id_batch,
            'nomor_sertifikat' => 'SERT-001-REVISI',
            'status' => 'terbit',
        ]);
    }
}
