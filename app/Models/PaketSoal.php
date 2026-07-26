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
        'nama_paket',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function kegiatans()
    {
        return $this->belongsToMany(Kegiatan::class, 'kegiatan_paket_soal', 'id_paket_soal', 'id_kegiatan', 'id_paket_soal', 'id_kegiatan')
            ->withTimestamps();
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
