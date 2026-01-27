<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    use HasFactory;

    protected $table = 'pegawai';
    protected $primaryKey = 'id_pegawai';
    public $incrementing = true;
    protected $keyType = 'int';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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
        'status',
    ];

    /**
     * Get the users associated with this pegawai.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'id_pegawai', 'id_pegawai');
    }
}
