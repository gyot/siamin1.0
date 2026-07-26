<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jawaban_peserta', function (Blueprint $table) {
            $table->id('id_jawaban');
            $table->foreignId('id_peserta')->constrained('peserta', 'id_peserta')->cascadeOnDelete();
            $table->foreignId('id_paket_soal')->constrained('paket_soal', 'id_paket_soal')->cascadeOnDelete();
            $table->foreignId('id_soal')->constrained('soal', 'id_soal')->cascadeOnDelete();
            $table->enum('jawaban', ['a', 'b', 'c', 'd']);
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(['id_peserta', 'id_soal'], 'uq_jawaban_peserta_soal');
            $table->index('id_peserta', 'idx_jawaban_id_peserta');
            $table->index('id_paket_soal', 'idx_jawaban_id_paket_soal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jawaban_peserta');
    }
};
