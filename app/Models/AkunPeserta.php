<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class AkunPeserta extends Model
{
    use HasFactory, HasApiTokens;

    protected $table = 'akun_peserta';
    protected $primaryKey = 'id_akun_peserta';
    public $timestamps = true;

    protected $fillable = [
        'id_peserta', 'username', 'password', 'last_login', 'status'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'last_login' => 'datetime',
    ];

    public function peserta()
    {
        return $this->belongsTo(Peserta::class, 'id_peserta', 'id_peserta');
    }
}
