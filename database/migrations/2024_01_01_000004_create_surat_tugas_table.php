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
        Schema::create('surat_tugas', function (Blueprint $table) {
            $table->id('id_surat_tugas');
            $table->foreignId('id_kegiatan')->nullable()->constrained('kegiatan', 'id_kegiatan')->onDelete('set null');
            $table->string('nomor_surat', 100)->nullable();
            $table->date('tanggal_surat')->nullable();
            $table->foreignId('id_penandatangan')->nullable()->constrained('pegawai', 'id_pegawai')->onDelete('set null');
            $table->enum('status', ['draft', 'diterbitkan', 'dibatalkan'])->default('draft');
            $table->string('file_surat', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_tugas');
    }
};
