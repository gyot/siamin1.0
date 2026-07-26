<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Pegawai;
use App\Models\User;
use App\Models\PenugasanPegawai;
use App\Models\KegiatanAtk;
use App\Models\Tpk;
use App\Models\Evaluasi;
use App\Models\Peserta;
use App\Models\PaketSoal;
use App\Models\SertifikatBatch;

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

    public function daftarAtk()
    {
        return $this->hasMany(KegiatanAtk::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function daftarTpk()
    {
        return $this->hasMany(Tpk::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function evaluasi()
    {
        return $this->hasMany(Evaluasi::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function penugasanPegawais()
    {
        return $this->hasMany(PenugasanPegawai::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function peserta()
    {
        return $this->hasMany(Peserta::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function sertifikatBatch()
    {
        return $this->hasMany(SertifikatBatch::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function paketSoals()
    {
        return $this->belongsToMany(PaketSoal::class, 'kegiatan_paket_soal', 'id_kegiatan', 'id_paket_soal', 'id_kegiatan', 'id_paket_soal')
            ->withTimestamps();
    }
}
