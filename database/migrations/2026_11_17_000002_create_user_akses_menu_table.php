<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konfigurasi menu per user (milik SIMAIK). Unik gabungan id_user + menu.
 * Tanpa foreign key ke `users` (VIEW baca-saja milik KANDI di produksi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_akses_menu', function (Blueprint $table) {
            $table->id();
            $table->integer('id_user');
            $table->string('menu', 50);
            $table->timestamps();
            $table->unique(['id_user', 'menu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_akses_menu');
    }
};
