<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paket_soal', function (Blueprint $table) {
            $table->id('id_paket_soal');
            $table->foreignId('id_kegiatan')->constrained('kegiatan', 'id_kegiatan')->cascadeOnDelete();
            $table->string('nama_paket', 255);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('id_kegiatan', 'idx_paket_soal_id_kegiatan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paket_soal');
    }
};
