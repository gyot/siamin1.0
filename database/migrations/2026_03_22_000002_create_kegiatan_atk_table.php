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
        Schema::create('kegiatan_atk', function (Blueprint $table) {
            $table->id('id_kegiatan_atk');
            $table->foreignId('id_kegiatan')->constrained('kegiatan', 'id_kegiatan')->onDelete('cascade');
            $table->string('nama_barang', 255);
            $table->string('spesifikasi', 255)->nullable();
            $table->unsignedInteger('jumlah')->default(1);
            $table->string('satuan', 100)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kegiatan_atk');
    }
};
