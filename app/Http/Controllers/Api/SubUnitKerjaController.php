<?php

namespace App\Http\Controllers\Api;

use App\Models\SubUnitKerja;

class SubUnitKerjaController extends BaseApiController
{
    protected $modelClass = SubUnitKerja::class;
    protected $rules = [
        'unit_kerja_id' => 'required|exists:unit_kerja,id',
        'nama_sub_unit' => 'sometimes|string|max:255',
    ];
}
