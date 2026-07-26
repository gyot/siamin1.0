<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaketSoal extends Model
{
    use HasFactory;

    protected $table = 'paket_soal';
    protected $primaryKey = 'id_paket_soal';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_kegiatan',
        'nama_paket',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function soals()
    {
        return $this->hasMany(Soal::class, 'id_paket_soal', 'id_paket_soal')->orderBy('urutan');
    }

    public function jawabanPesertas()
    {
        return $this->hasMany(JawabanPeserta::class, 'id_paket_soal', 'id_paket_soal');
    }
}
