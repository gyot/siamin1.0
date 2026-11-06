<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW daftar_sekolah AS
            SELECT
                MAX(NULLIF(TRIM(npsn), '')) AS npsn,
                SUBSTRING_INDEX(GROUP_CONCAT(nama_instansi ORDER BY CHAR_LENGTH(nama_instansi) DESC, nama_instansi SEPARATOR '||'), '||', 1) AS nama_sekolah,
                MIN(kab_kota) AS kab_kota,
                MIN(provinsi) AS provinsi,
                COUNT(*) AS jumlah_peserta,
                COUNT(DISTINCT id_kegiatan) AS jumlah_kegiatan
            FROM peserta
            WHERE nama_instansi IS NOT NULL AND TRIM(nama_instansi) != ''
            GROUP BY NULLIF(TRIM(npsn), ''), CASE WHEN NULLIF(TRIM(npsn), '') IS NULL THEN LOWER(TRIM(nama_instansi)) END
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS daftar_sekolah");
    }
};
