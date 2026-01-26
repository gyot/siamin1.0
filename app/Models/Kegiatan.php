<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kegiatan extends Model
{
    use HasFactory;

    protected $table = 'kegiatan';
    protected $primaryKey = 'id_kegiatan';
    public $timestamps = true;

    protected $fillable = [
        'nama_kegiatan', 'rincian_kegiatan', 'dokumentasi_url', 'materi_url',
        'panduan_url', 'laporan_url', 'surat_menyurat_url', 'tanggal_mulai',
        'tanggal_selesai', 'lokasi', 'flyer', 'peserta_ringkasan', 'total_peserta',
        'metode_pembayaran', 'deskripsi', 'metode_pelaksanaan', 'status', 'created_by'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }

    public function peserta()
    {
        return $this->hasMany(Peserta::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function sertifikat()
    {
        return $this->hasMany(Sertifikat::class, 'id_kegiatan', 'id_kegiatan');
    }
}
