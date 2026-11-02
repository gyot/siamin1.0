<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('struktur_program', function (Blueprint $table) {
            $table->id('id_struktur');
            $table->foreignId('id_batch')->constrained('sertifikat_batch', 'id_batch')->cascadeOnDelete();
            $table->unsignedBigInteger('id_kegiatan');
            $table->unsignedBigInteger('id_kelas')->nullable(); // null = kelas umum
            $table->string('materi');
            $table->integer('jumlah_jam')->default(0);
            $table->integer('urutan')->default(0);
            $table->timestamps();

            $table->index('id_kegiatan', 'idx_struktur_id_kegiatan');
            $table->index('id_kelas', 'idx_struktur_id_kelas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('struktur_program');
    }
};
