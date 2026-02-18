<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
