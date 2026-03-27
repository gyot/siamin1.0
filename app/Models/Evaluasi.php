<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluasi extends Model
{
    use HasFactory;

    protected $table = 'evaluasi';
    protected $primaryKey = 'id_evaluasi';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_evaluasi',
        'id_kegiatan',
        'program_tujuan',
        'program_bahan_ajar',
        'program_alokasi_waktu',
        'fasilitator',
        'layanan_panitia',
        'layanan_fasilitas',
        'layanan_konsumsi',
        'saran',
        'tanggal_evaluasi',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'fasilitator' => 'array',
            'tanggal_evaluasi' => 'datetime',
        ];
    }

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan', 'id_kegiatan');
    }
}
