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
}
