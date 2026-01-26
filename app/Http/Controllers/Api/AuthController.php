<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AkunPeserta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login for Admin users (Pegawai)
     */
    public function loginAdmin(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            // Find user by email
            $user = User::where('email', $validated['email'])->first();

            // Check if user exists and password is correct
            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email atau password salah',
                    'errors' => ['credentials' => ['Email atau password salah']]
                ], 401);
            }

            // Check if user is active
            if ($user->status !== 'aktif') {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda tidak aktif',
                ], 403);
            }

            // Generate token using Sanctum
            $token = $user->createToken('authToken')->plainTextToken;

            // Update last login
            $user->update(['last_login' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'token' => $token,
                'user' => [
                    'id' => $user->id_user,
                    'name' => $user->pegawai->nama ?? 'Admin',
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login for Peserta
     */
    public function loginPeserta(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validated = $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);

            // Find peserta account by username
            $akunPeserta = AkunPeserta::where('username', $validated['username'])->first();

            // Check if account exists and password is correct
            if (!$akunPeserta || !Hash::check($validated['password'], $akunPeserta->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Username atau password salah',
                    'errors' => ['credentials' => ['Username atau password salah']]
                ], 401);
            }

            // Check if account is active
            if ($akunPeserta->status !== 'aktif') {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda tidak aktif',
                ], 403);
            }

            // Generate token using Sanctum
            $token = $akunPeserta->createToken('pesertaToken')->plainTextToken;

            // Update last login
            $akunPeserta->update(['last_login' => now()]);

            // Get peserta data
            $peserta = $akunPeserta->peserta;

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'token' => $token,
                'user' => [
                    'id' => $akunPeserta->id_akun_peserta,
                    'name' => $peserta->nama_peserta ?? 'Peserta',
                    'email' => $peserta->email_peserta ?? '',
                    'username' => $akunPeserta->username,
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Revoke the token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout berhasil',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat logout',
            ], 500);
        }
    }
}
