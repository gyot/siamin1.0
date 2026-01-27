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
        Schema::create('peserta', function (Blueprint $table) {
            $table->id('id_peserta');
            $table->foreignId('id_kegiatan')->nullable()->constrained('kegiatan', 'id_kegiatan')->onDelete('set null');
            $table->string('nama_lengkap', 150)->nullable();
            $table->string('nip', 30)->nullable();
            $table->string('pangkat', 50)->nullable();
            $table->string('gol', 50)->nullable();
            $table->string('jabatan', 150)->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('npwp_nik', 30)->nullable();
            $table->string('tempat_lahir', 255)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin', 15)->nullable();
            $table->string('nama_instansi', 255)->nullable();
            $table->text('alamat_instansi')->nullable();
            $table->string('kab_kota', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->string('telp_instansi', 20)->nullable();
            $table->string('email_instansi', 150)->nullable();
            $table->string('provider_pulsa', 50)->nullable();
            $table->string('nomor_rekening', 50)->nullable();
            $table->string('nama_bank', 100)->nullable();
            $table->string('no_surat_tugas', 255)->nullable();
            $table->date('tanggal_surat_tugas')->nullable();
            $table->string('peran', 100)->nullable();
            $table->string('tanda_tangan', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta');
    }
};
