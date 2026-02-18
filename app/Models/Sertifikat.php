<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sertifikat extends Model
{
    use HasFactory;

    protected $table = 'sertifikat';
    protected $primaryKey = 'id_sertifikat';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_kegiatan',
        'id_peserta',
        'nomor_sertifikat',
        'tanggal_ttd',
        'id_penandatangan',
        'template',
        'peran',
        'status',
    ];
}
