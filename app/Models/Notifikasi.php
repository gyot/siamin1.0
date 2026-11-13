<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Notifikasi lonceng untuk user admin (penerima = users.id_user).
 * Dibuat otomatis oleh NotifikasiService saat kejadian penting terjadi.
 */
class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasi';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_user',
        'tipe',
        'judul',
        'pesan',
        'tautan',
        'terkait_tipe',
        'terkait_id',
        'dibaca_pada',
    ];

    protected $casts = [
        'dibaca_pada' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function sudahDibaca(): bool
    {
        return $this->dibaca_pada !== null;
    }
}
