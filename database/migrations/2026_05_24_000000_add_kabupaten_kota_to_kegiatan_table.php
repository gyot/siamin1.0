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
        Schema::table('kegiatan', function (Blueprint $table) {
            if (!Schema::hasColumn('kegiatan', 'kabupaten_kota')) {
                $table->string('kabupaten_kota', 255)->nullable()->after('lokasi');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            if (Schema::hasColumn('kegiatan', 'kabupaten_kota')) {
                $table->dropColumn('kabupaten_kota');
            }
        });
    }
};
