<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AkunPeserta extends Model
{
    use HasFactory;

    protected $table = 'akun_peserta';
    protected $primaryKey = 'id_akun_peserta';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_peserta',
        'id_profil',
        'username',
        'password',
        'last_login',
        'status',
    ];

    public function peserta()
    {
        return $this->belongsTo(Peserta::class, 'id_peserta', 'id_peserta');
    }

    public function profil()
    {
        return $this->belongsTo(ProfilPeserta::class, 'id_profil', 'id_profil');
    }
}
