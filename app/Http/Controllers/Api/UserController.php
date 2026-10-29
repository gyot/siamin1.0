<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;

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

    /**
     * Hanya admin yang boleh membuat/mengubah/menghapus user
     * (mencegah privilege escalation dari role operator/verifikator/kepala).
     */
    private function ensureAdmin(): void
    {
        if (request()->user()?->role !== 'admin') {
            abort(response()->json([
                'message' => 'Hanya admin yang boleh mengelola user.',
            ], 403));
        }
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();
        return parent::store($request);
    }

    public function update(Request $request, $id)
    {
        $this->ensureAdmin();
        return parent::update($request, $id);
    }

    public function destroy($id)
    {
        $this->ensureAdmin();
        return parent::destroy($id);
    }
}
