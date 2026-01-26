<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KeanggotatanTim;
use Illuminate\Http\JsonResponse;

class KeanggotatanTimController extends Controller
{
    /**
     * Display a listing of keanggotan tim.
     */
    public function index(): JsonResponse
    {
        $keanggotatanTim = KeanggotatanTim::with(['pegawai', 'unitKerja', 'subUnitKerja'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Data keanggotan tim berhasil diambil',
            'data' => $keanggotatanTim,
            'total' => $keanggotatanTim->count()
        ]);
    }
}
