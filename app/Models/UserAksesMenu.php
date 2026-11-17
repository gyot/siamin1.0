<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Konfigurasi menu per user (milik SIMAIK, bukan view KANDI).
 * Satu baris = satu menu; unik gabungan id_user + menu.
 * Tanpa foreign key ke `users` (VIEW baca-saja di produksi).
 */
class UserAksesMenu extends Model
{
    protected $table = 'user_akses_menu';

    protected $fillable = [
        'id_user',
        'menu',
    ];
}
