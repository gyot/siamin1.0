<?php

namespace App\Http\Controllers\Api;

use App\Models\AkunPeserta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AkunPesertaController extends BaseApiController
{
    protected $modelClass = AkunPeserta::class;
    protected $rules = [
        'id_peserta' => 'required|exists:peserta,id_peserta',
        'username' => 'required|string|max:100|unique:akun_peserta,username',
        'password' => 'sometimes|string',
    ];

    /**
     * Password wajib di-hash saat disimpan (jangan pernah plaintext).
     */
    public function store(Request $request)
    {
        if ($request->filled('password')) {
            $request->merge(['password' => Hash::make($request->input('password'))]);
        }
        return parent::store($request);
    }

    public function update(Request $request, $id)
    {
        if ($request->filled('password')) {
            $request->merge(['password' => Hash::make($request->input('password'))]);
        }
        return parent::update($request, $id);
    }
}
