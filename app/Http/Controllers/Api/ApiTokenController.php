<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Manajemen API token (Sanctum) untuk integrasi aplikasi lain.
 * Hanya untuk admin. Nilai token hanya dikirim SEKALI saat dibuat.
 */
class ApiTokenController extends Controller
{
    private function ensureAdmin(Request $request): ?\Illuminate\Http\JsonResponse
    {
        if (strtolower($request->user()->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Hanya admin yang dapat mengelola API token.'], 403);
        }
        return null;
    }

    /** GET /api/v1/api-tokens — daftar token (tanpa nilai token) */
    public function index(Request $request)
    {
        if ($denied = $this->ensureAdmin($request)) return $denied;

        $tokens = PersonalAccessToken::orderByDesc('created_at')
            ->get(['id', 'name', 'last_used_at', 'created_at'])
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'last_used_at' => $t->last_used_at,
                'created_at' => $t->created_at,
            ]);

        return response()->json(['success' => true, 'data' => $tokens]);
    }

    /** POST /api/v1/api-tokens — buat token baru (nilai token tampil sekali) */
    public function store(Request $request)
    {
        if ($denied = $this->ensureAdmin($request)) return $denied;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $plainTextToken = $request->user()->createToken($validated['name'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token berhasil dibuat. Simpan sekarang — nilai token hanya tampil sekali.',
            'data' => [
                'name' => $validated['name'],
                'token' => $plainTextToken,
            ],
        ], 201);
    }

    /** DELETE /api/v1/api-tokens/{id} — cabut token */
    public function destroy(Request $request, $id)
    {
        if ($denied = $this->ensureAdmin($request)) return $denied;

        $token = PersonalAccessToken::find($id);
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token tidak ditemukan.'], 404);
        }

        $token->delete();

        return response()->json(['success' => true, 'message' => 'Token berhasil dicabut.']);
    }
}
