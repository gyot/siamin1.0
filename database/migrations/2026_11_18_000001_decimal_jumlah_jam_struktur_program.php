<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * JP struktur program boleh pecahan (mis. 1,5 / 2,5 sesuai panduan kegiatan).
     */
    public function up(): void
    {
        Schema::table('struktur_program', function (Blueprint $table) {
            $table->decimal('jumlah_jam', 5, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('struktur_program', function (Blueprint $table) {
            $table->integer('jumlah_jam')->default(0)->change();
        });
    }
};
