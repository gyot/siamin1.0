<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KegiatanAtk extends Model
{
    use HasFactory;

    protected $table = 'kegiatan_atk';
    protected $primaryKey = 'id_kegiatan_atk';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_kegiatan',
        'nama_barang',
        'spesifikasi',
        'jumlah',
        'satuan',
        'keterangan',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan', 'id_kegiatan');
    }
}
