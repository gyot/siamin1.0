<?php
require __DIR__ . "/vendor/autoload.php";
$app = require __DIR__ . "/bootstrap/app.php";
$app->make("Illuminate\Contracts\Http\Kernel")->handle($request = Illuminate\Http\Request::capture());

use App\Models\User, App\Models\Pegawai, Illuminate\Support\Facades\Hash;

$p = Pegawai::firstOrCreate(["nip" => "199001011990011001"], ["nama" => "Admin Test", "tempat_lahir" => "Jakarta", "tanggal_lahir" => "1990-01-01", "tmt_cpns" => "1990-01-01", "pangkat" => "Pembina Utama", "golongan" => "IV/c", "nama_jabatan" => "Admin", "pendidikan_terakhir" => "Sarjana", "jurusan" => "IT", "tempat_pendidikan" => "ITB", "tahun_lulus" => 2012, "perkiraan_pensiun" => "2030-01-01", "status_kepegawaian" => "PNS", "status" => "aktif"]);

User::firstOrCreate(["email" => "admin@kemkominfo.go.id"], ["id_pegawai" => $p->id_pegawai, "password" => Hash::make("password123"), "role" => "admin", "status" => "aktif"]);

echo "Created!";

