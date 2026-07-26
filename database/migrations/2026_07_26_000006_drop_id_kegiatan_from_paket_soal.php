<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paket_soal', function (Blueprint $table) {
            $table->dropForeign(['id_kegiatan']);
            $table->dropColumn('id_kegiatan');
        });
    }

    public function down(): void
    {
        Schema::table('paket_soal', function (Blueprint $table) {
            $table->foreignId('id_kegiatan')->nullable()->constrained('kegiatan', 'id_kegiatan')->cascadeOnDelete();
        });
    }
};
