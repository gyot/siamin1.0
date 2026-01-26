<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use Illuminate\Http\JsonResponse;

class LogAktivitasController extends Controller
{
    /**
     * Display a listing of log aktivitas.
     */
    public function index(): JsonResponse
    {
        $logAktivitas = LogAktivitas::with('user')->get();

        return response()->json([
            'success' => true,
            'message' => 'Data log aktivitas berhasil diambil',
            'data' => $logAktivitas,
            'total' => $logAktivitas->count()
        ]);
    }
}
