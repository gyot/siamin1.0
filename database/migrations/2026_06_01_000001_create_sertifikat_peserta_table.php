<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sertifikat_peserta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_batch')->constrained('sertifikat_batch', 'id_batch')->cascadeOnDelete();
            $table->foreignId('id_peserta')->constrained('peserta', 'id_peserta')->cascadeOnDelete();
            $table->string('qr_token')->nullable();
            $table->enum('status', ['draft', 'terbit', 'dicabut'])->default('draft');
            $table->timestamps();

            $table->unique(['id_batch', 'id_peserta'], 'uk_batch_peserta');
            $table->index(['id_peserta', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifikat_peserta');
    }
};
