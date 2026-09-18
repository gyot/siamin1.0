<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_harian', function (Blueprint $table) {
            $table->id('id_jadwal');
            $table->foreignId('id_kegiatan')->constrained('kegiatan', 'id_kegiatan')->cascadeOnDelete();
            $table->unsignedBigInteger('id_kelas')->nullable();
            $table->string('hari', 50);
            $table->date('tanggal');
            $table->json('items')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();

            $table->index('id_kegiatan', 'idx_jadwal_id_kegiatan');
            $table->index('id_kelas', 'idx_jadwal_id_kelas');

            $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_harian');
    }
};
