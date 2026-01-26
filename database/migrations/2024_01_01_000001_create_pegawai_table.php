<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pegawai', function (Blueprint $table) {
            $table->id('id_pegawai');
            $table->string('nip', 30)->nullable()->unique();
            $table->string('nama', 150)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->date('tmt_cpns')->nullable();
            $table->date('tmt_pangkat')->nullable();
            $table->string('pangkat', 50)->nullable();
            $table->string('golongan', 10)->nullable();
            $table->string('nama_jabatan', 150)->nullable();
            $table->date('tmt_jabatan')->nullable();
            $table->string('pendidikan_terakhir', 50)->nullable();
            $table->string('jurusan', 150)->nullable();
            $table->string('tempat_pendidikan', 150)->nullable();
            $table->year('tahun_lulus')->nullable();
            $table->string('latihan_jabatan', 150)->nullable();
            $table->enum('status_kepegawaian', ['PNS', 'PPPK'])->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai');
    }
};
