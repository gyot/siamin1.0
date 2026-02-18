<?php

namespace App\Http\Controllers\Api;

use App\Models\Pegawai;

class PegawaiController extends BaseApiController
{
    protected $modelClass = Pegawai::class;
    protected $rules = [
        'nip' => 'sometimes|string|max:30',
        'nama' => 'sometimes|string|max:255',
        // other field rules can be added as needed
    ];
}
