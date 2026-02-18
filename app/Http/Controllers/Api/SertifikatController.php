<?php

namespace App\Http\Controllers\Api;

use App\Models\Sertifikat;

class SertifikatController extends BaseApiController
{
    protected $modelClass = Sertifikat::class;
    protected $rules = [
        'nomor_sertifikat' => 'sometimes|string|max:100',
        'id_peserta' => 'sometimes|exists:peserta,id_peserta',
    ];
}
