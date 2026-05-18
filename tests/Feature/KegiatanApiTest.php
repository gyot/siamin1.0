<?php

namespace Tests\Feature;

use App\Models\KeanggotaanTim;
use App\Models\Pegawai;
use App\Models\UnitKerja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KegiatanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_kegiatan_auto_resolves_unit_kerja_id_from_keanggotaan_tim(): void
    {
        $pegawai = Pegawai::create([
            'nama' => 'Pegawai Test',
        ]);

        $unitKerja = UnitKerja::create([
            'kode_unit' => 'UK-001',
            'nama_unit' => 'Unit Test',
        ]);

        KeanggotaanTim::create([
            'id_pegawai' => $pegawai->id_pegawai,
            'unit_kerja_id' => $unitKerja->id,
        ]);

        $response = $this->postJson('/api/v1/kegiatan', [
            'nama_kegiatan' => 'Workshop Integrasi',
            'tanggal_mulai' => '2026-04-23',
            'tanggal_selesai' => '2026-04-24',
            'lokasi' => 'Mataram',
            'status' => 'draft',
            'id_pegawai' => $pegawai->id_pegawai,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.unit_kerja_id', $unitKerja->id);

        $this->assertDatabaseHas('kegiatan', [
            'nama_kegiatan' => 'Workshop Integrasi',
            'id_pegawai' => $pegawai->id_pegawai,
            'unit_kerja_id' => $unitKerja->id,
        ]);
    }

    public function test_store_kegiatan_returns_validation_error_when_pegawai_has_multiple_units_and_frontend_omits_unit_kerja_id(): void
    {
        $pegawai = Pegawai::create([
            'nama' => 'Pegawai Multi Unit',
        ]);

        $unitKerjaA = UnitKerja::create([
            'kode_unit' => 'UK-101',
            'nama_unit' => 'Unit A',
        ]);

        $unitKerjaB = UnitKerja::create([
            'kode_unit' => 'UK-102',
            'nama_unit' => 'Unit B',
        ]);

        KeanggotaanTim::create([
            'id_pegawai' => $pegawai->id_pegawai,
            'unit_kerja_id' => $unitKerjaA->id,
        ]);

        KeanggotaanTim::create([
            'id_pegawai' => $pegawai->id_pegawai,
            'unit_kerja_id' => $unitKerjaB->id,
        ]);

        $response = $this->postJson('/api/v1/kegiatan', [
            'nama_kegiatan' => 'Workshop Multi Unit',
            'tanggal_mulai' => '2026-04-23',
            'tanggal_selesai' => '2026-04-24',
            'lokasi' => 'Mataram',
            'status' => 'draft',
            'id_pegawai' => $pegawai->id_pegawai,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['unit_kerja_id']);
    }

    public function test_protected_api_returns_unauthorized_instead_of_route_login_error(): void
    {
        $this->get('/api/v1/me')
            ->assertUnauthorized();
    }
}
