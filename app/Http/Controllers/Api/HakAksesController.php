<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAksesMenu;
use App\Models\UserPeran;
use App\Support\HakAkses;
use Illuminate\Http\Request;

/**
 * Hak akses menu (user privilege).
 *
 * - GET  /api/v1/hak-akses/saya       — semua user login.
 * - GET  /api/v1/hak-akses            — khusus super admin.
 * - PUT  /api/v1/hak-akses/{id_user}  — khusus super admin.
 */
class HakAksesController extends Controller
{
    /**
     * Hak akses milik user yang sedang login.
     * User tanpa konfigurasi mendapat menu DEFAULT.
     */
    public function saya(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'super_admin' => HakAkses::isSuperAdmin($user),
                'menus' => HakAkses::menusFor($user),
            ],
        ]);
    }

    /**
     * Daftar semua user + hak aksesnya (khusus super admin).
     * `users` adalah VIEW (bisa >1 baris per user) — digabung per id_user.
     */
    public function index(Request $request)
    {
        $this->ensureSuperAdmin();

        $peranPerUser = UserPeran::query()->pluck('peran', 'id_user');
        $menuPerUser = UserAksesMenu::query()
            ->orderBy('id')
            ->get()
            ->groupBy('id_user')
            ->map(fn ($rows) => $rows->pluck('menu')->values()->all());

        $data = User::query()->get()->groupBy('id_user')->map(function ($group) use ($peranPerUser, $menuPerUser) {
            $user = $group->first();
            $idUser = (int) $user->id_user;
            $peran = $peranPerUser[$idUser] ?? null;
            $menus = $menuPerUser[$idUser] ?? [];
            $superAdmin = $peran === 'super_admin';
            $pegawai = $user->pegawai;

            return [
                'id_user' => $idUser,
                'nama' => $user->name ?? $user->nama ?? $pegawai?->nama,
                'nip' => $user->nip ?? $pegawai?->nip,
                'peran' => $peran,
                'menus' => $menus !== []
                    ? array_values(array_unique($menus))
                    : ($superAdmin ? HakAkses::MENU_KEYS : HakAkses::DEFAULT_MENUS),
                'default' => ! $superAdmin && $menus === [],
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Simpan hak akses satu user (khusus super admin).
     * Body: { menus: string[], peran?: 'admin'|'super_admin' }.
     * Menu lama user itu DI-REPLACE seluruhnya.
     */
    public function update(Request $request, $id_user)
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            // 'present' (bukan 'required') agar `menus: []` sah = hapus semua
            // konfigurasi -> user kembali memakai menu DEFAULT.
            'menus' => 'present|array',
            'menus.*' => 'string|max:50|distinct',
            'peran' => 'sometimes|nullable|in:admin,super_admin',
        ]);

        $idUser = (int) $id_user;
        $user = User::query()->where('id_user', $idUser)->first();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        HakAkses::replaceMenus($idUser, $validated['menus']);

        if (array_key_exists('peran', $validated)) {
            if ($validated['peran'] === null) {
                UserPeran::query()->where('id_user', $idUser)->delete();
            } else {
                UserPeran::query()->updateOrCreate(
                    ['id_user' => $idUser],
                    ['peran' => $validated['peran']],
                );
            }
        }

        $peran = UserPeran::query()->where('id_user', $idUser)->value('peran');
        $menus = UserAksesMenu::query()
            ->where('id_user', $idUser)
            ->orderBy('id')
            ->pluck('menu')
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Hak akses berhasil diperbarui.',
            'data' => [
                'id_user' => $idUser,
                'peran' => $peran,
                'menus' => $menus,
                'default' => $menus === [],
            ],
        ]);
    }

    /** Hanya super admin (peran di user_peran) yang boleh mengelola hak akses. */
    private function ensureSuperAdmin(): void
    {
        if (! HakAkses::isSuperAdmin(request()->user())) {
            abort(response()->json([
                'success' => false,
                'message' => 'Hanya super admin yang boleh mengelola hak akses.',
            ], 403));
        }
    }
}
