<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SuratTugas;
use Illuminate\Http\JsonResponse;

class SuratTugasController extends Controller
{
    /**
     * Display a listing of surat tugas.
     */
    public function index(): JsonResponse
    {
        $suratTugas = SuratTugas::with(['kegiatan', 'penandatangan', 'pegawaiList'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Data surat tugas berhasil diambil',
            'data' => $suratTugas,
            'total' => $suratTugas->count()
        ]);
    }
}
