<?php

namespace Database\Seeders;

use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create sample pegawai data
        $pegawai1 = Pegawai::create([
            'nip' => '19700101200001100001',
            'nama' => 'Adi Pratama Nugroho',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1970-01-01',
            'tmt_cpns' => '2000-01-01',
            'tmt_pangkat' => '2020-06-01',
            'pangkat' => 'Pembina Utama Madya',
            'golongan' => 'IV/d',
            'nama_jabatan' => 'Kepala Dinas',
            'tmt_jabatan' => '2021-01-01',
            'pendidikan_terakhir' => 'S2',
            'jurusan' => 'Administrasi Publik',
            'tempat_pendidikan' => 'Universitas Indonesia',
            'tahun_lulus' => 1995,
            'latihan_jabatan' => 'Diklat Kepemimpinan Tingkat I',
            'status_kepegawaian' => 'PNS',
            'status' => 'aktif',
        ]);

        $pegawai2 = Pegawai::create([
            'nip' => '19800515200002100002',
            'nama' => 'Siti Nurhaliza',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1980-05-15',
            'tmt_cpns' => '2005-03-01',
            'tmt_pangkat' => '2022-01-01',
            'pangkat' => 'Pembina',
            'golongan' => 'IV/a',
            'nama_jabatan' => 'Kepala Bidang',
            'tmt_jabatan' => '2022-02-01',
            'pendidikan_terakhir' => 'S1',
            'jurusan' => 'Administrasi Negara',
            'tempat_pendidikan' => 'Universitas Padjadjaran',
            'tahun_lulus' => 2002,
            'latihan_jabatan' => 'Diklat Kepemimpinan Tingkat II',
            'status_kepegawaian' => 'PNS',
            'status' => 'aktif',
        ]);

        $pegawai3 = Pegawai::create([
            'nip' => '19920312200003100003',
            'nama' => 'Budi Handoko',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '1992-03-12',
            'tmt_cpns' => '2015-08-01',
            'tmt_pangkat' => '2021-12-01',
            'pangkat' => 'Penata Muda Tingkat I',
            'golongan' => 'III/b',
            'nama_jabatan' => 'Operator',
            'tmt_jabatan' => '2022-06-01',
            'pendidikan_terakhir' => 'S1',
            'jurusan' => 'Sistem Informasi',
            'tempat_pendidikan' => 'Universitas Airlangga',
            'tahun_lulus' => 2014,
            'latihan_jabatan' => 'Diklat Struktural Golongan III',
            'status_kepegawaian' => 'PNS',
            'status' => 'aktif',
        ]);

        // Create sample user data with different roles
        User::create([
            'id_pegawai' => $pegawai1->id_pegawai,
            'email' => 'admin@siamin.test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'aktif',
            'last_login' => now(),
        ]);

        User::create([
            'id_pegawai' => $pegawai2->id_pegawai,
            'email' => 'verifikator@siamin.test',
            'password' => Hash::make('password123'),
            'role' => 'verifikator',
            'status' => 'aktif',
            'last_login' => now(),
        ]);

        User::create([
            'id_pegawai' => $pegawai3->id_pegawai,
            'email' => 'operator@siamin.test',
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'status' => 'aktif',
            'last_login' => now(),
        ]);

        User::create([
            'id_pegawai' => null,
            'email' => 'kepala@siamin.test',
            'password' => Hash::make('password123'),
            'role' => 'kepala',
            'status' => 'aktif',
            'last_login' => null,
        ]);
    }
}

