<?php

namespace App\Http\Controllers\Api;

use App\Models\SuratTugasPegawai;

class SuratTugasPegawaiController extends BaseApiController
{
    protected $modelClass = SuratTugasPegawai::class;
    protected $rules = [
        'id_surat_tugas' => 'required|exists:surat_tugas,id_surat_tugas',
        'id_pegawai' => 'required|exists:pegawai,id_pegawai',
    ];
}
