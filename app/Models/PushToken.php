<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushToken extends Model
{
    use HasFactory;

    protected $table = 'push_tokens';
    protected $primaryKey = 'id_push_token';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_pegawai',
        'id_akun_peserta',
        'token',
        'device_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai', 'id_pegawai');
    }

    public function akunPeserta()
    {
        return $this->belongsTo(AkunPeserta::class, 'id_akun_peserta', 'id_akun_peserta');
    }
}
