<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profil_peserta', function (Blueprint $table) {
            $table->id('id_profil');
            $table->string('nama_lengkap', 255);
            $table->string('nip', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->enum('jenis_kelamin', ['laki-laki', 'perempuan'])->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('npwp_nik', 30)->nullable();
            $table->string('pangkat', 100)->nullable();
            $table->string('jabatan', 255)->nullable();
            $table->string('nama_instansi', 255)->nullable();
            $table->string('npsn', 8)->nullable();
            $table->string('kab_kota', 100)->nullable();
            $table->timestamps();
        });

        Schema::table('akun_peserta', function (Blueprint $table) {
            $table->foreignId('id_profil')->nullable()->constrained('profil_peserta', 'id_profil')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('akun_peserta', function (Blueprint $table) {
            $table->dropForeign(['id_profil']);
            $table->dropColumn('id_profil');
        });
        Schema::dropIfExists('profil_peserta');
    }
};
