<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use Illuminate\Http\JsonResponse;

class PesertaController extends Controller
{
    /**
     * Display a listing of peserta.
     */
    public function index(): JsonResponse
    {
        $peserta = Peserta::with('kegiatan')->get();

        return response()->json([
            'success' => true,
            'message' => 'Data peserta berhasil diambil',
            'data' => $peserta,
            'total' => $peserta->count()
        ]);
    }
}
