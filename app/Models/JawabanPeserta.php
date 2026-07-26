<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JawabanPeserta extends Model
{
    use HasFactory;

    protected $table = 'jawaban_peserta';
    protected $primaryKey = 'id_jawaban';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_peserta',
        'id_paket_soal',
        'id_soal',
        'jawaban',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function peserta()
    {
        return $this->belongsTo(Peserta::class, 'id_peserta', 'id_peserta');
    }

    public function paketSoal()
    {
        return $this->belongsTo(PaketSoal::class, 'id_paket_soal', 'id_paket_soal');
    }

    public function soal()
    {
        return $this->belongsTo(Soal::class, 'id_soal', 'id_soal');
    }
}
