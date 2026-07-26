<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Soal extends Model
{
    use HasFactory;

    protected $table = 'soal';
    protected $primaryKey = 'id_soal';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_paket_soal',
        'pertanyaan',
        'pilihan_a',
        'pilihan_b',
        'pilihan_c',
        'pilihan_d',
        'jawaban_benar',
        'urutan',
    ];

    public function paketSoal()
    {
        return $this->belongsTo(PaketSoal::class, 'id_paket_soal', 'id_paket_soal');
    }

    public function jawabanPesertas()
    {
        return $this->hasMany(JawabanPeserta::class, 'id_soal', 'id_soal');
    }
}
