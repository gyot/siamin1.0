<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SertifikatBatch extends Model
{
    use HasFactory;

    protected $table = 'sertifikat_batch';
    protected $primaryKey = 'id_batch';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_kegiatan',
        'nomor_sertifikat',
        'id_penandatangan',
        'tanggal_ttd',
        'template_file',
        'status',
    ];

    protected $casts = [
        'tanggal_ttd' => 'date',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function penandatangan()
    {
        return $this->belongsTo(Pegawai::class, 'id_penandatangan', 'id_pegawai');
    }

    public function sertifikatPeserta()
    {
        return $this->hasMany(SertifikatPeserta::class, 'id_batch', 'id_batch');
    }
}
