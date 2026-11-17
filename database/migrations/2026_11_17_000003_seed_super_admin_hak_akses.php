<?php

use Database\Seeders\SuperAdminSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Seeder lanjutan: super admin hak akses (lookup via NIP, skip aman bila
 * user belum ada). Ditaruh sebagai migration agar ikut ter-eksekusi saat
 * deploy menjalankan `php artisan migrate`.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new SuperAdminSeeder())->run();
    }

    public function down(): void
    {
        // Data-only migration; baris user_peran dibiarkan (bisa diubah via endpoint).
    }
};
