<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenugasanPegawai extends Model
{
    use HasFactory;

    protected $table = 'penugasan_pegawai';
    public $timestamps = false;

    protected $fillable = [
        'id_kegiatan',
        'id_pegawai',
        'peran',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai', 'id_pegawai');
    }
}
