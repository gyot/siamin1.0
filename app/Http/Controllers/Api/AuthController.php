<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user and return token
     *
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * Get authenticated user profile
     *
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * Logout user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout berhasil',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 500);
        }
    }

    private function attemptLogin(Request $request, array $roles)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $identity = $request->email;
        $field = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'user';

        $user = User::with('pegawai')
            ->where($field, $identity)
            ->whereIn('role', $roles)
            ->where('status', 'aktif')
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email/username atau password tidak valid.'],
            ]);
        }

        // $user->update(['last_login' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => $user,
                'token' => $user->createToken('siamin-api')->plainTextToken,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function loginAdmin(Request $request)
    {
        return $this->attemptLogin($request, ['admin', 'operator', 'verifikator', 'kepala']);
    }

    public function loginPeserta(Request $request)
    {
        return $this->attemptLogin($request, ['peserta']);
    }

    // public function me(Request $request)
    // {
    //     $user = $request->user()->load('pegawai');

    //     return response()->json([
    //         'success' => true,
    //         'data' => [
    //             'user' => [
    //                 'id_user' => $user->id_user,
    //                 'email' => $user->email,
    //                 'role' => $user->role,
    //                 'status' => $user->status,
    //                 'last_login' => $user->last_login,
    //                 'created_at' => $user->created_at,
    //             ],
    //             'pegawai' => $user->pegawai ? [
    //                 'id_pegawai' => $user->pegawai->id_pegawai,
    //                 'nip' => $user->pegawai->nip,
    //                 'nama' => $user->pegawai->nama,
    //                 'tempat_lahir' => $user->pegawai->tempat_lahir,
    //                 'tanggal_lahir' => $user->pegawai->tanggal_lahir,
    //                 'nama_jabatan' => $user->pegawai->nama_jabatan,
    //                 'golongan' => $user->pegawai->golongan,
    //                 'pangkat' => $user->pegawai->pangkat,
    //                 'tmt_cpns' => $user->pegawai->tmt_cpns,
    //                 'pendidikan_terakhir' => $user->pegawai->pendidikan_terakhir,
    //                 'status_kepegawaian' => $user->pegawai->status_kepegawaian,
    //             ] : null,
    //         ],
    //     ]);
    // }

    public function me(Request $request)
    {
        $user = $request->user()->load([
            'pegawai.keanggotaanTim.unitKerja',
        ]);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan',
            ], 401);
        }

        $pegawai = $user->pegawai;

        $unitKerja = [];

        if ($pegawai && $pegawai->keanggotaanTim) {
            $unitKerja = $pegawai->keanggotaanTim
                ->filter(fn ($kt) => $kt->unitKerja)
                ->map(function ($kt) {
                    return [
                        'id_unit' => $kt->unitKerja->id,
                        'nama_unit' => $kt->unitKerja->nama_unit,
                    ];
                })
                ->values();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id_user' => $user->id_user,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                ],
                'pegawai' => $pegawai ? [
                    'id_pegawai' => $pegawai->id_pegawai,
                    'nip' => $pegawai->nip,
                    'nama' => $pegawai->nama,
                ] : null,
                'unit_kerja' => $unitKerja,
            ],
        ]);
    }
}
