<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AkunPeserta;
use Illuminate\Http\JsonResponse;

class AkunPesertaController extends Controller
{
    /**
     * Display a listing of akun peserta.
     */
    public function index(): JsonResponse
    {
        $akunPeserta = AkunPeserta::with('peserta')->get();

        return response()->json([
            'success' => true,
            'message' => 'Data akun peserta berhasil diambil',
            'data' => $akunPeserta,
            'total' => $akunPeserta->count()
        ]);
    }
}
