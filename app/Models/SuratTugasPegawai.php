<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratTugasPegawai extends Model
{
    use HasFactory;

    protected $table = 'surat_tugas_pegawai';
    public $timestamps = false;

    protected $fillable = [
        'id_surat_tugas', 'id_pegawai', 'peran'
    ];

    public function suratTugas()
    {
        return $this->belongsTo(SuratTugas::class, 'id_surat_tugas', 'id_surat_tugas');
    }

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai', 'id_pegawai');
    }
}
