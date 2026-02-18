<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratTugasPegawai extends Model
{
    use HasFactory;

    protected $table = 'surat_tugas_pegawai';
    // default primary key id
    public $timestamps = false;

    protected $fillable = [
        'id_surat_tugas',
        'id_pegawai',
        'peran',
    ];
}
