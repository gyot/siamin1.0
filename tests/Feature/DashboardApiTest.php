<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Peserta;
use App\Models\Sertifikat;
use App\Models\UnitKerja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_kegiatan_all_supports_filters_and_pagination(): void
    {
        $unitA = UnitKerja::create([
            'kode_unit' => 'BPMP',
            'nama_unit' => 'BPMP',
        ]);

        $unitB = UnitKerja::create([
            'kode_unit' => 'GTK',
            'nama_unit' => 'GTK',
        ]);

        Kegiatan::create([
            'nama_kegiatan' => 'Workshop A',
            'tanggal_mulai' => '2026-04-01',
            'tanggal_selesai' => '2026-04-02',
            'lokasi' => 'Mataram',
            'status' => 'berjalan',
            'total_peserta' => 50,
            'peserta_ringkasan' => '50 peserta',
            'unit_kerja_id' => $unitA->id,
        ]);

        Kegiatan::create([
            'nama_kegiatan' => 'Rapat B',
            'tanggal_mulai' => '2025-02-01',
            'tanggal_selesai' => '2025-02-02',
            'lokasi' => 'Bima',
            'status' => 'draft',
            'total_peserta' => 10,
            'peserta_ringkasan' => '10 peserta',
            'unit_kerja_id' => $unitB->id,
        ]);

        $response = $this->getJson('/api/v1/kegiatan/all?search=Workshop&tahun=2026&status=berjalan&unit_kerja=BPMP&page=1&limit=9');

        $response
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 9)
            ->assertJsonPath('last_page', 1)
            ->assertJsonPath('data.0.nama_kegiatan', 'Workshop A')
            ->assertJsonPath('data.0.unit_kerja', 'BPMP');
    }

    public function test_dashboard_stats_returns_aggregated_counts_and_filters(): void
    {
        $unitA = UnitKerja::create([
            'kode_unit' => 'BPMP',
            'nama_unit' => 'BPMP',
        ]);

        $unitB = UnitKerja::create([
            'kode_unit' => 'GTK',
            'nama_unit' => 'GTK',
        ]);

        $berjalan = Kegiatan::create([
            'nama_kegiatan' => 'Kegiatan Berjalan',
            'tanggal_mulai' => now()->subDay()->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
            'lokasi' => 'Mataram',
            'status' => 'berjalan',
            'total_peserta' => 100,
            'unit_kerja_id' => $unitA->id,
        ]);

        $selesai = Kegiatan::create([
            'nama_kegiatan' => 'Kegiatan Selesai',
            'tanggal_mulai' => '2025-01-10',
            'tanggal_selesai' => '2025-01-11',
            'lokasi' => 'Bima',
            'status' => 'selesai',
            'total_peserta' => 80,
            'unit_kerja_id' => $unitB->id,
        ]);

        Peserta::create([
            'id_kegiatan' => $berjalan->id_kegiatan,
            'nama_lengkap' => 'Peserta 1',
        ]);

        Peserta::create([
            'id_kegiatan' => $selesai->id_kegiatan,
            'nama_lengkap' => 'Peserta 2',
        ]);

        Sertifikat::create([
            'id_kegiatan' => $berjalan->id_kegiatan,
            'nomor_sertifikat' => 'SERT-001',
            'status' => 'terbit',
        ]);

        Sertifikat::create([
            'id_kegiatan' => $selesai->id_kegiatan,
            'nomor_sertifikat' => 'SERT-002',
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/v1/dashboard/stats');

        $response
            ->assertOk()
            ->assertJsonPath('total_kegiatan', 2)
            ->assertJsonPath('kegiatan_berjalan', 1)
            ->assertJsonPath('total_peserta', 2)
            ->assertJsonPath('total_sertifikat_terbit', 1)
            ->assertJsonFragment(['2026'])
            ->assertJsonFragment(['2025'])
            ->assertJsonFragment(['BPMP'])
            ->assertJsonFragment(['GTK']);
    }
}
