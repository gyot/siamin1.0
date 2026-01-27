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
        Schema::create('akun_peserta', function (Blueprint $table) {
            $table->id('id_akun_peserta');
            $table->foreignId('id_peserta')->constrained('peserta', 'id_peserta')->onDelete('cascade');
            $table->string('username', 100)->unique();
            $table->string('password');
            $table->timestamp('last_login')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akun_peserta');
    }
};
