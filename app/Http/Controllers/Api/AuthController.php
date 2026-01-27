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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    // public function login(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'email' => 'required|email',
    //             'password' => 'required|string',
    //         ]);

    //         $user = User::with('pegawai')
    //             ->where('email', $request->email)
    //             ->first();

    //         if (!$user || !Hash::check($request->password, $user->password)) {
    //             throw ValidationException::withMessages([
    //                 'email' => ['Email atau password tidak valid.'],
    //             ]);
    //         }

    //         if ($user->status === 'nonaktif') {
    //             throw ValidationException::withMessages([
    //                 'email' => ['User tidak aktif.'],
    //             ]);
    //         }

    //         // Update last login
    //         $user->update(['last_login' => now()]);

    //         // Generate token
    //         $token = $user->createToken('auth_token')->plainTextToken;

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Login berhasil',
    //             'data' => [
    //                 'user' => [
    //                     'id_user' => $user->id_user,
    //                     'email' => $user->email,
    //                     'role' => $user->role,
    //                     'status' => $user->status,
    //                     'last_login' => $user->last_login,
    //                     'pegawai' => $user->pegawai ? [
    //                         'id_pegawai' => $user->pegawai->id_pegawai,
    //                         'nip' => $user->pegawai->nip,
    //                         'nama' => $user->pegawai->nama,
    //                         'nama_jabatan' => $user->pegawai->nama_jabatan,
    //                         'golongan' => $user->pegawai->golongan,
    //                         'pangkat' => $user->pegawai->pangkat,
    //                     ] : null,
    //                 ],
    //                 'token' => $token,
    //                 'token_type' => 'Bearer',
    //             ],
    //         ], 200);
    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validasi gagal',
    //             'errors' => $e->errors(),
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    /**
     * Get authenticated user profile
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    // public function profile(Request $request)
    // {
    //     try {
    //         $user = $request->user()->load('pegawai');

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Data profil berhasil diambil',
    //             'data' => [
    //                 'id_user' => $user->id_user,
    //                 'email' => $user->email,
    //                 'role' => $user->role,
    //                 'status' => $user->status,
    //                 'last_login' => $user->last_login,
    //                 'pegawai' => $user->pegawai ? [
    //                     'id_pegawai' => $user->pegawai->id_pegawai,
    //                     'nip' => $user->pegawai->nip,
    //                     'nama' => $user->pegawai->nama,
    //                     'tempat_lahir' => $user->pegawai->tempat_lahir,
    //                     'tanggal_lahir' => $user->pegawai->tanggal_lahir,
    //                     'nama_jabatan' => $user->pegawai->nama_jabatan,
    //                     'golongan' => $user->pegawai->golongan,
    //                     'pangkat' => $user->pegawai->pangkat,
    //                     'status_kepegawaian' => $user->pegawai->status_kepegawaian,
    //                 ] : null,
    //             ],
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // /**
    //  * Logout user
    //  *
    //  * @param Request $request
    //  * @return \Illuminate\Http\JsonResponse
    //  */
    // public function logout(Request $request)
    // {
    //     try {
    //         $request->user()->currentAccessToken()->delete();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Logout berhasil',
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    private function attemptLogin(Request $request, array $roles)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $user = User::with('pegawai')
        ->where('email', $request->email)
        ->whereIn('role', $roles)
        ->where('status','aktif')
        ->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['Email atau password tidak valid.'],
        ]);
    }

    $user->update(['last_login' => now()]);

    return response()->json([
        'success' => true,
        'message' => 'Login berhasil',
        'data' => [
            'user' => $user,
            'token' => $user->createToken('siamin-api')->plainTextToken,
            'token_type' => 'Bearer'
        ]
    ]);
}

public function loginAdmin(Request $request)
{
    return $this->attemptLogin($request, ['admin','operator','verifikator','kepala']);
}

public function loginPeserta(Request $request)
{
    return $this->attemptLogin($request, ['peserta']);
}

}