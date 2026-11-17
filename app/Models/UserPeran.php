<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Peran hak akses user (milik SIMAIK, bukan view KANDI).
 * PK id_user — tanpa foreign key karena `users` adalah VIEW baca-saja
 * di produksi dan tidak bisa menjadi target FK.
 */
class UserPeran extends Model
{
    protected $table = 'user_peran';

    protected $primaryKey = 'id_user';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id_user',
        'peran',
    ];
}
