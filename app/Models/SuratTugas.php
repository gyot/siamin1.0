<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Kegiatan;
use App\Models\Pegawai;
use App\Models\SuratTugasPegawai;

class SuratTugas extends Model
{
    use HasFactory;

    protected $table = 'surat_tugas';
    protected $primaryKey = 'id_surat_tugas';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_kegiatan',
        'nomor_surat',
        'tanggal_surat',
        'id_penandatangan',
        'status',
        'file_surat',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function penandatangan()
    {
        return $this->belongsTo(Pegawai::class, 'id_penandatangan', 'id_pegawai');
    }

    public function suratTugasPegawais()
    {
        return $this->hasMany(SuratTugasPegawai::class, 'id_surat_tugas', 'id_surat_tugas');
    }
}
