<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\SertifikatBatch;
use App\Models\User;
use App\Models\UserAksesMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SertifikatBatchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_with_id_batch_updates_existing_sertifikat_instead_of_creating_a_new_record(): void
    {
        $user = new User();
        $user->id_user = 99;
        // Mutasi batch = menu proses-sertifikat (lihat peta route di routes/api.php).
        UserAksesMenu::forceCreate(['id_user' => 99, 'menu' => 'proses-sertifikat']);
        $this->actingAs($user, 'sanctum');

        $kegiatan = Kegiatan::create(['nama_kegiatan' => 'Workshop Sertifikat']);
        $batch = SertifikatBatch::create([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'nomor_sertifikat' => 'draft',
            'status' => 'revisi',
        ]);

        $response = $this->postJson('/api/v1/sertifikat', [
            'id_batch' => $batch->id_batch,
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'nomor_sertifikat' => 'SERT-001-REVISI',
            'status' => 'terbit',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id_batch', $batch->id_batch);

        $this->assertDatabaseCount('sertifikat_batch', 1);
        $this->assertDatabaseHas('sertifikat_batch', [
            'id_batch' => $batch->id_batch,
            // Kontrak alur baru: simpan ulang = ajukan ulang, nomor tetap "draft" sampai proses.
            'nomor_sertifikat' => 'draft',
            'status' => 'diajukan',
        ]);
    }
}
