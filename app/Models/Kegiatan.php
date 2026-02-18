<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kegiatan extends Model
{
    use HasFactory;

    // table name is singular in migrations, disable pluralization
    protected $table = 'kegiatan';

    protected $primaryKey = 'id_kegiatan';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nama_kegiatan',
        'rincian_kegiatan',
        'dokumentasi_url',
        'materi_url',
        'panduan_url',
        'laporan_url',
        'surat_menyurat_url',
        'tanggal_mulai',
        'tanggal_selesai',
        'lokasi',
        'flyer',
        'peserta_ringkasan',
        'total_peserta',
        'metode_pembayaran',
        'deskripsi',
        'metode_pelaksanaan',
        'status',
        'created_by',
        'id_pegawai',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai', 'id_pegawai');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }
}
