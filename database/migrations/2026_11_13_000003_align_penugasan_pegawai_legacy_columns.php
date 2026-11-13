<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Paritas skema test (SQLite) dengan produksi (MySQL).
 *
 * Migrasi 2026_05_20_130000 membuang kolom warisan id_surat_tugas/id_penugasan
 * dari penugasan_pegawai HANYA pada jalur MySQL (butuh bedah FK via
 * information_schema) dan early-return di driver lain, sehingga skema SQLite
 * menyisakan kolom NOT NULL yang membuat INSERT penugasan_pegawai selalu gagal
 * (padahal di produksi kolom itu sudah tidak ada). Kolom warisan dilonggarkan
 * menjadi nullable di non-MySQL agar jalur insert di test identik dengan
 * produksi — tanpa menyentuh jalur MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql' || !Schema::hasTable('penugasan_pegawai')) {
            return;
        }

        Schema::table('penugasan_pegawai', function (Blueprint $table) {
            if (Schema::hasColumn('penugasan_pegawai', 'id_surat_tugas')) {
                $table->unsignedBigInteger('id_surat_tugas')->nullable()->change();
            }
            if (Schema::hasColumn('penugasan_pegawai', 'id_penugasan')) {
                $table->unsignedBigInteger('id_penugasan')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Sengaja tidak dibalik: kolom warisan ini memang sudah tidak dipakai
        // di produksi (MySQL membuangnya lewat 2026_05_20_130000).
    }
};
