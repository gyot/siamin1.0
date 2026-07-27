<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KelasAnggota extends Model
{
    use HasFactory;

    protected $table = 'kelas_anggota';
    protected $primaryKey = 'id_kelas_anggota';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_kelas',
        'id_peserta',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function peserta()
    {
        return $this->belongsTo(Peserta::class, 'id_peserta', 'id_peserta');
    }
}
