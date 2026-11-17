<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Peran hak akses user (milik SIMAIK). id_user adalah PK integer tanpa
 * foreign key — di produksi `users` adalah VIEW baca-saja milik KANDI
 * sehingga tidak bisa menjadi target FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_peran', function (Blueprint $table) {
            $table->integer('id_user')->primary();
            $table->string('peran', 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_peran');
    }
};
