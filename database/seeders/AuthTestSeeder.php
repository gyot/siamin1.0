<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Peserta;
use App\Models\AkunPeserta;
use App\Models\Pegawai;
use App\Models\Kegiatan;

class AuthTestSeeder extends Seeder
{
    public function run(): void
    {
        $pegawai = Pegawai::firstOrCreate(
            ["nip" => "199001011990011001"],
            [
                "nama" => "Admin Test User",
                "tempat_lahir" => "Jakarta",
                "tanggal_lahir" => "1990-01-01",
                "tmt_cpns" => "1990-01-01",
                "pangkat" => "Pembina Utama",
                "golongan" => "IV/c",
                "nama_jabatan" => "Administrator",
                "pendidikan_terakhir" => "Sarjana",
                "jurusan" => "Teknik Informatika",
                "tempat_pendidikan" => "Institut Teknologi",
                "tahun_lulus" => 2012,
                "perkiraan_pensiun" => "2030-01-01",
                "status_kepegawaian" => "PNS",
                "status" => "aktif",
            ]
        );

        User::firstOrCreate(
            ["email" => "admin@kemkominfo.go.id"],
            [
                "id_pegawai" => $pegawai->id_pegawai,
                "password" => Hash::make("password123"),
                "role" => "admin",
                "status" => "aktif"
            ]
        );

        $kegiatan = Kegiatan::first() ?? Kegiatan::create([
            "nama_kegiatan" => "Test Activity",
            "deskripsi" => "Test kegiatan untuk peserta",
            "tanggal_mulai" => "2026-01-25",
            "tanggal_selesai" => "2026-02-25",
            "tempat" => "Jakarta",
            "status" => "aktif"
        ]);

        $peserta = Peserta::firstOrCreate(
            ["nip" => "199005011990051001"],
            [
                "id_kegiatan" => $kegiatan->id_kegiatan,
                "nama_lengkap" => "Test Peserta",
                "pangkat" => "Pengatur",
                "gol" => "II/c",
                "jabatan" => "Test Position",
                "no_hp" => "081234567890",
                "email" => "peserta@test.com",
                "jenis_kelamin" => "Laki-laki",
                "nama_instansi" => "Test Institution",
                "kab_kota" => "Jakarta",
                "provinsi" => "Jakarta",
                "status" => "aktif"
            ]
        );

        AkunPeserta::firstOrCreate(
            ["username" => "testpeserta"],
            [
                "id_peserta" => $peserta->id_peserta,
                "password" => Hash::make("password123"),
                "status" => "aktif"
            ]
        );

        echo "Auth seeder OK!\n";
    }
}