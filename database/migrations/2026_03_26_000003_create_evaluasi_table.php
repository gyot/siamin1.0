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
        Schema::create('evaluasi', function (Blueprint $table) {
            $table->string('id_evaluasi', 50)->primary();
            $table->foreignId('id_kegiatan')->constrained('kegiatan', 'id_kegiatan')->cascadeOnDelete();

            $table->unsignedTinyInteger('program_tujuan');
            $table->unsignedTinyInteger('program_bahan_ajar');
            $table->unsignedTinyInteger('program_alokasi_waktu');

            $table->json('fasilitator');

            $table->unsignedTinyInteger('layanan_panitia');
            $table->unsignedTinyInteger('layanan_fasilitas');
            $table->unsignedTinyInteger('layanan_konsumsi');

            $table->text('saran')->nullable();
            $table->dateTime('tanggal_evaluasi');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index('id_kegiatan', 'idx_evaluasi_id_kegiatan');
            $table->index('tanggal_evaluasi', 'idx_evaluasi_tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluasi');
    }
};
