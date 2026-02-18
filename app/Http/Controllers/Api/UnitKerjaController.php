<?php

namespace App\Http\Controllers\Api;

use App\Models\UnitKerja;

class UnitKerjaController extends BaseApiController
{
    protected $modelClass = UnitKerja::class;
    protected $rules = [
        'kode_unit' => 'sometimes|string|max:50',
        'nama_unit' => 'sometimes|string|max:255',
    ];
}
