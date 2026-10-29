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
        });

        // Drop the custom index before the column: MySQL drops it automatically,
        // but SQLite rebuilds the table and chokes on an index over a missing column.
        if (Schema::hasIndex('paket_soal', 'idx_paket_soal_id_kegiatan')) {
            Schema::table('paket_soal', function (Blueprint $table) {
                $table->dropIndex('idx_paket_soal_id_kegiatan');
            });
        }

        Schema::table('paket_soal', function (Blueprint $table) {
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
