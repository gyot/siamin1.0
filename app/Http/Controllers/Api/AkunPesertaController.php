<?php

namespace App\Http\Controllers\Api;

use App\Models\AkunPeserta;

class AkunPesertaController extends BaseApiController
{
    protected $modelClass = AkunPeserta::class;
    protected $rules = [
        'id_peserta' => 'required|exists:peserta,id_peserta',
        'username' => 'required|string|max:100|unique:akun_peserta,username',
        'password' => 'sometimes|string',
    ];
}
