<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tpk extends Model
{
    use HasFactory;

    protected $table = 'tpk';
    protected $primaryKey = 'id_tpk';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_kegiatan',
        'lokasi',
        'kabupaten_kota',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function peserta()
    {
        return $this->hasMany(Peserta::class, 'id_tpk', 'id_tpk');
    }
}
