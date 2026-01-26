<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kegiatan;
use Illuminate\Http\JsonResponse;

class KegiatanController extends Controller
{
    /**
     * Display a listing of kegiatan.
     */
    public function index(): JsonResponse
    {
        $kegiatan = Kegiatan::with('creator')->get();

        return response()->json([
            'success' => true,
            'message' => 'Data kegiatan berhasil diambil',
            'data' => $kegiatan,
            'total' => $kegiatan->count()
        ]);
    }
}
