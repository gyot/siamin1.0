<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratTugas extends Model
{
    use HasFactory;

    protected $table = 'surat_tugas';
    protected $primaryKey = 'id_surat_tugas';
    public $timestamps = true;

    protected $fillable = [
        'id_kegiatan', 'nomor_surat', 'tanggal_surat', 'id_penandatangan', 'status', 'file_surat'
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function penandatangan()
    {
        return $this->belongsTo(Pegawai::class, 'id_penandatangan', 'id_pegawai');
    }

    public function pegawaiList()
    {
        return $this->hasMany(SuratTugasPegawai::class, 'id_surat_tugas', 'id_surat_tugas');
    }
}
