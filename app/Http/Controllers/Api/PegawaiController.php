<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use Illuminate\Http\JsonResponse;

class PegawaiController extends Controller
{
    /**
     * Display a listing of pegawai.
     */
    public function index(): JsonResponse
    {
        $pegawai = Pegawai::all();

        return response()->json([
            'success' => true,
            'message' => 'Data pegawai berhasil diambil',
            'data' => $pegawai,
            'total' => $pegawai->count()
        ]);
    }
}
