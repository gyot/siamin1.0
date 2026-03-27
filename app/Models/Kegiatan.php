<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Pegawai;
use App\Models\User;
use App\Models\SuratTugas;
use App\Models\SuratTugasPegawai;
use App\Models\KegiatanAtk;
use App\Models\Evaluasi;

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
        'template_biodata',
        'peserta_ringkasan',
        'total_peserta',
        'metode_pembayaran',
        'deskripsi',
        'metode_pelaksanaan',
        'status',
        'created_by',
        'id_pegawai',
        'unit_kerja_id',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai', 'id_pegawai');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }

    /**
     * Kegiatan may have multiple surat tugas documents.
     */
    public function suratTugas()
    {
        return $this->hasMany(SuratTugas::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function daftarAtk()
    {
        return $this->hasMany(KegiatanAtk::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function evaluasi()
    {
        return $this->hasMany(Evaluasi::class, 'id_kegiatan', 'id_kegiatan');
    }

    /**
     * Shortcut to access all pegawai entries attached to this kegiatan through surat tugas.
     */
    public function suratTugasPegawais()
    {
        // hasManyThrough(Target, Through, firstKey, secondKey, localKey, secondLocalKey)
        return $this->hasManyThrough(
            SuratTugasPegawai::class,
            SuratTugas::class,
            'id_kegiatan',        // FK on surat_tugas table
            'id_surat_tugas',     // FK on surat_tugas_pegawai table
            'id_kegiatan',        // Local key on kegiatan
            'id_surat_tugas'      // Local key on surat_tugas
        );
    }
}
