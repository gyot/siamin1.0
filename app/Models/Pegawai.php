<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    use HasFactory;

    protected $table = 'pegawai';
    protected $primaryKey = 'id_pegawai';
    public $timestamps = true;

    protected $fillable = [
        'nip', 
        'nama', 
        'tempat_lahir', 
        'tanggal_lahir', 
        'tmt_cpns', 
        'tmt_pangkat',
        'pangkat', 
        'golongan', 
        'nama_jabatan', 
        'tmt_jabatan',
        'pendidikan_terakhir', 
        'jurusan', 
        'tempat_pendidikan', 
        'tahun_lulus',
        'latihan_jabatan', 
        'status_kepegawaian', 
        'status'
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tmt_cpns' => 'date',
        'tmt_pangkat' => 'date',
        'tmt_jabatan' => 'date',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id_pegawai', 'id_pegawai');
    }
}
