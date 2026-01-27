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
        Schema::create('surat_tugas_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_surat_tugas')->constrained('surat_tugas', 'id_surat_tugas')->onDelete('cascade');
            $table->foreignId('id_pegawai')->constrained('pegawai', 'id_pegawai')->onDelete('cascade');
            $table->enum('peran', [
                'penanggung_jawab',
                'ketua_panitia',
                'panitia',
                'peserta',
                'narasumber'
            ])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_tugas_pegawai');
    }
};
