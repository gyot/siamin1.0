<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StrukturProgram extends Model
{
    protected $table = 'struktur_program';
    protected $primaryKey = 'id_struktur';

    protected $fillable = [
        'id_batch',
        'id_kegiatan',
        'id_kelas',
        'materi',
        'jumlah_jam',
        'urutan',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function batch()
    {
        return $this->belongsTo(SertifikatBatch::class, 'id_batch', 'id_batch');
    }
}
