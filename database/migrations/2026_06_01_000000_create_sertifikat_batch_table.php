<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sertifikat_batch', function (Blueprint $table) {
            $table->id('id_batch');
            $table->foreignId('id_kegiatan')->constrained('kegiatan', 'id_kegiatan')->cascadeOnDelete();
            $table->string('nomor_sertifikat', 150);
            $table->foreignId('id_penandatangan')->nullable()->constrained('pegawai', 'id_pegawai')->nullOnDelete();
            $table->date('tanggal_ttd')->nullable();
            $table->string('template_file')->nullable();
            $table->enum('status', ['draft', 'terbit', 'dicabut'])->default('draft');
            $table->timestamps();

            $table->index(['id_kegiatan', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifikat_batch');
    }
};
