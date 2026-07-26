<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soal', function (Blueprint $table) {
            $table->id('id_soal');
            $table->foreignId('id_paket_soal')->constrained('paket_soal', 'id_paket_soal')->cascadeOnDelete();
            $table->text('pertanyaan');
            $table->text('pilihan_a');
            $table->text('pilihan_b');
            $table->text('pilihan_c');
            $table->text('pilihan_d');
            $table->enum('jawaban_benar', ['a', 'b', 'c', 'd']);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index('id_paket_soal', 'idx_soal_id_paket_soal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soal');
    }
};
