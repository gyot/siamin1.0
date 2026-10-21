<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_tokens', function (Blueprint $table) {
            $table->id('id_push_token');
            $table->unsignedBigInteger('id_pegawai')->nullable();
            $table->unsignedBigInteger('id_akun_peserta')->nullable();
            $table->string('token', 500)->unique();
            $table->enum('device_type', ['android', 'ios', 'web'])->default('android');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('id_pegawai', 'idx_push_tokens_id_pegawai');
            $table->index('id_akun_peserta', 'idx_push_tokens_id_akun_peserta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_tokens');
    }
};
