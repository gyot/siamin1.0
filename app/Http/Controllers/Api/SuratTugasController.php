<?php

namespace App\Http\Controllers\Api;

use App\Models\SuratTugas;

class SuratTugasController extends BaseApiController
{
    protected $modelClass = SuratTugas::class;
    protected $rules = [
        'id_kegiatan' => 'sometimes|exists:kegiatan,id_kegiatan',
        'nomor_surat' => 'sometimes|string|max:100',
    ];
}
