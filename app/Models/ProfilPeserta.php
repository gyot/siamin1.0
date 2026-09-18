<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfilPeserta extends Model
{
    use HasFactory;

    protected $table = 'profil_peserta';
    protected $primaryKey = 'id_profil';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nama_lengkap',
        'nip',
        'email',
        'no_hp',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'npwp_nik',
        'pangkat',
        'jabatan',
        'nama_instansi',
        'npsn',
        'kab_kota',
    ];

    public function akun()
    {
        return $this->hasOne(AkunPeserta::class, 'id_profil', 'id_profil');
    }
}
