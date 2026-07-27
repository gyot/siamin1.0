<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';
    protected $primaryKey = 'id_kelas';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_kegiatan',
        'nama_kelas',
        'deskripsi',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function anggotas()
    {
        return $this->hasMany(KelasAnggota::class, 'id_kelas', 'id_kelas');
    }

    public function pesertas()
    {
        return $this->belongsToMany(Peserta::class, 'kelas_anggota', 'id_kelas', 'id_peserta', 'id_kelas', 'id_peserta')
            ->withTimestamps();
    }
}
