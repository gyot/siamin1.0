<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan_paket_soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kegiatan')->constrained('kegiatan', 'id_kegiatan')->cascadeOnDelete();
            $table->foreignId('id_paket_soal')->constrained('paket_soal', 'id_paket_soal')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['id_kegiatan', 'id_paket_soal'], 'uq_kegiatan_paket_soal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_paket_soal');
    }
};
