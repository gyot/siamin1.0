<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_drafts', function (Blueprint $table) {
            $table->id('id_draft');
            $table->foreignId('id_kegiatan')->constrained('kegiatan', 'id_kegiatan')->cascadeOnDelete();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index('id_kegiatan', 'idx_laporan_drafts_id_kegiatan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_drafts');
    }
};
