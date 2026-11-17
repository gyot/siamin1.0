<?php

namespace Database\Seeders;

use App\Support\HakAkses;
use Illuminate\Database\Seeder;

/**
 * Jadikan pemilik NIP 199502102025211047 sebagai super admin hak akses.
 * Lookup via NIP; dilewati dengan aman (tanpa error) bila user belum ada
 * di tabel users (view KANDI belum memuatnya).
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        HakAkses::seedSuperAdmin();
    }
}
