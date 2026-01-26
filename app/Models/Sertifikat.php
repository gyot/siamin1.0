<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sertifikat extends Model
{
    use HasFactory;

    protected $table = 'sertifikat';
    protected $primaryKey = 'id_sertifikat';
    public $timestamps = true;

    protected $fillable = [
        'id_kegiatan', 'id_peserta', 'nomor_sertifikat', 'tanggal_ttd', 'id_penandatangan',
        'template', 'peran', 'status'
    ];

    protected $casts = [
        'tanggal_ttd' => 'date',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function peserta()
    {
        return $this->belongsTo(Peserta::class, 'id_peserta', 'id_peserta');
    }

    public function penandatangan()
    {
        return $this->belongsTo(Pegawai::class, 'id_penandatangan', 'id_pegawai');
    }
}
