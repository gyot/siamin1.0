<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_anggota', function (Blueprint $table) {
            $table->id('id_kelas_anggota');
            $table->foreignId('id_kelas')->constrained('kelas', 'id_kelas')->cascadeOnDelete();
            $table->foreignId('id_peserta')->constrained('peserta', 'id_peserta')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['id_kelas', 'id_peserta'], 'uq_kelas_anggota');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_anggota');
    }
};
