<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Peserta extends Model
{
    use HasFactory;

    protected $table = 'peserta';
    protected $primaryKey = 'id_peserta';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_kegiatan',
        'nama_lengkap',
        'nip',
        'pangkat',
        'gol',
        'jabatan',
        'no_hp',
        'email',
        'npwp_nik',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'nama_instansi',
        'alamat_instansi',
        'kab_kota',
        'provinsi',
        'telp_instansi',
        'email_instansi',
        'provider_pulsa',
        'nomor_rekening',
        'nama_bank',
        'no_surat_tugas',
        'tanggal_surat_tugas',
        'peran',
        'tanda_tangan',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function akun()
    {
        return $this->hasOne(AkunPeserta::class, 'id_peserta', 'id_peserta');
    }
}
