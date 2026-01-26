<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sertifikat;
use Illuminate\Http\JsonResponse;

class SertifikatController extends Controller
{
    /**
     * Display a listing of sertifikat.
     */
    public function index(): JsonResponse
    {
        $sertifikat = Sertifikat::with(['kegiatan', 'peserta', 'penandatangan'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Data sertifikat berhasil diambil',
            'data' => $sertifikat,
            'total' => $sertifikat->count()
        ]);
    }
}
