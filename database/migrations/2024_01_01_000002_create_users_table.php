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
        Schema::create('users', function (Blueprint $table) {
            $table->id('id_user');
            $table->foreignId('id_pegawai')->nullable()->constrained('pegawai', 'id_pegawai')->onDelete('set null');
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'operator', 'verifikator', 'kepala'])->default('operator');
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
        Schema::dropIfExists('users');
    }
};
