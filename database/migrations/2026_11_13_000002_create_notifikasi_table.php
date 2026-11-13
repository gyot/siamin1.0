<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
            $table->string('tipe', 30); // revisi | ditolak | disetujui | pengajuan_baru | penugasan
            $table->string('judul', 150);
            $table->text('pesan');
            $table->string('tautan', 255)->nullable();
            $table->string('terkait_tipe', 50)->nullable();
            $table->unsignedBigInteger('terkait_id')->nullable();
            $table->timestamp('dibaca_pada')->nullable();
            $table->timestamps();

            $table->index(['id_user', 'dibaca_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
