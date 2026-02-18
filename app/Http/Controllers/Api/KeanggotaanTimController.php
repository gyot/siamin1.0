<?php

namespace App\Http\Controllers\Api;

use App\Models\KeanggotaanTim;

class KeanggotaanTimController extends BaseApiController
{
    protected $modelClass = KeanggotaanTim::class;
    protected $rules = [
        'id_pegawai' => 'required|exists:pegawai,id_pegawai',
        'unit_kerja_id' => 'required|exists:unit_kerja,id',
    ];
}
