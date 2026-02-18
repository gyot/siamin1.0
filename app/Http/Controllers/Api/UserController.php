<?php

namespace App\Http\Controllers\Api;

use App\Models\User;

class UserController extends BaseApiController
{
    protected $modelClass = User::class;
    // additional rules may be added here
    protected $rules = [
        'email' => 'required|email|unique:users,email',
        'password' => 'sometimes|string|min:6',
        'role' => 'sometimes|string',
        'status' => 'sometimes|string',
        'id_pegawai' => 'sometimes|exists:pegawai,id_pegawai',
    ];
}
