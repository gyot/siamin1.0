<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->index('status', 'kegiatan_status_index');
            $table->index('tanggal_mulai', 'kegiatan_tanggal_mulai_index');
            $table->index('unit_kerja_id', 'kegiatan_unit_kerja_id_index');
            $table->index('nama_kegiatan', 'kegiatan_nama_kegiatan_index');
            $table->index(['status', 'tanggal_mulai', 'unit_kerja_id'], 'kegiatan_dashboard_filter_index');
        });

        Schema::table('peserta', function (Blueprint $table) {
            $table->index('id_kegiatan', 'peserta_id_kegiatan_index');
        });

        Schema::table('sertifikat', function (Blueprint $table) {
            $table->index('status', 'sertifikat_status_index');
            $table->index('id_kegiatan', 'sertifikat_id_kegiatan_index');
        });
    }

    public function down(): void
    {
        Schema::table('sertifikat', function (Blueprint $table) {
            $table->dropIndex('sertifikat_status_index');
            $table->dropIndex('sertifikat_id_kegiatan_index');
        });

        Schema::table('peserta', function (Blueprint $table) {
            $table->dropIndex('peserta_id_kegiatan_index');
        });

        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropIndex('kegiatan_status_index');
            $table->dropIndex('kegiatan_tanggal_mulai_index');
            $table->dropIndex('kegiatan_unit_kerja_id_index');
            $table->dropIndex('kegiatan_nama_kegiatan_index');
            $table->dropIndex('kegiatan_dashboard_filter_index');
        });
    }
};
