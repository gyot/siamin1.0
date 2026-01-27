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
        Schema::create('sertifikat', function (Blueprint $table) {
            $table->id('id_sertifikat');
            $table->foreignId('id_kegiatan')->nullable()->constrained('kegiatan', 'id_kegiatan')->onDelete('set null');
            $table->foreignId('id_peserta')->nullable()->constrained('peserta', 'id_peserta')->onDelete('set null');
            $table->string('nomor_sertifikat', 100)->unique();
            $table->date('tanggal_ttd')->nullable();
            $table->foreignId('id_penandatangan')->nullable()->constrained('pegawai', 'id_pegawai')->onDelete('set null');
            $table->string('template', 255)->nullable();
            $table->string('peran', 255)->nullable();
            $table->enum('status', ['draft', 'terbit', 'dicabut'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sertifikat');
    }
};
