<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(): JsonResponse
    {
        $users = User::with('pegawai')->get();

        return response()->json([
            'success' => true,
            'message' => 'Data user berhasil diambil',
            'data' => $users,
            'total' => $users->count()
        ]);
    }
}
