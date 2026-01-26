<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SuratTugasPegawai;
use Illuminate\Http\JsonResponse;

class SuratTugasPegawaiController extends Controller
{
    /**
     * Display a listing of surat tugas pegawai.
     */
    public function index(): JsonResponse
    {
        $suratTugasPegawai = SuratTugasPegawai::with(['suratTugas', 'pegawai'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Data surat tugas pegawai berhasil diambil',
            'data' => $suratTugasPegawai,
            'total' => $suratTugasPegawai->count()
        ]);
    }
}
