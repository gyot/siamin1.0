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
        Schema::create('kegiatan', function (Blueprint $table) {
            $table->id('id_kegiatan');
            $table->string('nama_kegiatan', 255)->nullable();
            $table->text('rincian_kegiatan')->nullable();
            $table->string('dokumentasi_url', 255)->nullable();
            $table->string('materi_url', 255)->nullable();
            $table->string('panduan_url', 255)->nullable();
            $table->string('laporan_url', 255)->nullable();
            $table->string('surat_menyurat_url', 255)->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('lokasi', 255)->nullable();
            $table->string('flyer', 255)->nullable();
            $table->string('peserta_ringkasan', 255)->nullable();
            $table->integer('total_peserta')->nullable();
            $table->enum('metode_pembayaran', [
                'transfer',
                'pulsa',
                'transfer_dan_pulsa',
                'tunai',
                'tidak_dibayar'
            ])->nullable();
            $table->text('deskripsi')->nullable();
            $table->enum('metode_pelaksanaan', ['daring', 'luring', 'hybrid'])->nullable();
            $table->enum('status', ['draft', 'berjalan', 'selesai', 'dibatalkan'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users', 'id_user')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kegiatan');
    }
};
