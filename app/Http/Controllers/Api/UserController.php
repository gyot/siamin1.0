<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\UnitKerja;
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
     * Tampilan user (read-only, manajemen user ada di aplikasi KANDI).
     * View 'users' menghasilkan 1 baris per kombinasi tim x role —
     * digabung jadi 1 baris per user dengan keterangan lengkap.
     */
    public function index(Request $request)
    {
        $rows = User::query()->get();
        $unitNames = UnitKerja::pluck('nama_unit', 'id');

        $data = $rows->groupBy('id_user')->map(function ($g) use ($unitNames) {
            $base = $g->first();
            $roles = $g->pluck('role')->filter()->unique()->values();
            $timIds = $g->pluck('id_tim')->filter()->unique()->values();
            $timNames = $timIds
                ->map(fn ($id) => $unitNames[$id] ?? ('Tim #' . $id))
                ->unique()->values();

            return [
                'id_user' => $base->id_user,
                'id_pegawai' => $base->id_pegawai,
                'name' => $base->name,
                'user' => $base->user,
                'email' => $base->email,
                'role' => $roles->first(),
                'roles_detail' => $roles->implode(', '),
                'id_tim' => $timIds->first(),
                'tim_ids' => $timIds->values(),
                'tim_detail' => $timNames->implode(', '),
                'status' => $base->status,
                'last_login' => $base->last_login,
                'created_at' => $base->created_at,
                'updated_at' => $base->updated_at,
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

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
