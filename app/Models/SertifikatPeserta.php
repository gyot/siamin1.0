<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SertifikatPeserta extends Model
{
    use HasFactory;

    protected $table = 'sertifikat_peserta';

    protected $fillable = [
        'id_batch',
        'id_peserta',
        'qr_token',
        'status',
    ];

    public function batch()
    {
        return $this->belongsTo(SertifikatBatch::class, 'id_batch', 'id_batch');
    }

    public function peserta()
    {
        return $this->belongsTo(Peserta::class, 'id_peserta', 'id_peserta');
    }

    public function kegiatan()
    {
        return $this->hasOneThrough(
            Kegiatan::class,
            SertifikatBatch::class,
            'id_batch',
            'id_kegiatan',
            'id_batch',
            'id_kegiatan'
        );
    }

    public function penandatangan()
    {
        return $this->hasOneThrough(
            Pegawai::class,
            SertifikatBatch::class,
            'id_batch',
            'id_pegawai',
            'id_batch',
            'id_penandatangan'
        );
    }
}
