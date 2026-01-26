<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UnitKerja;
use Illuminate\Http\JsonResponse;

class UnitKerjaController extends Controller
{
    /**
     * Display a listing of unit kerja.
     */
    public function index(): JsonResponse
    {
        $unitKerja = UnitKerja::with('subUnitKerja')->get();

        return response()->json([
            'success' => true,
            'message' => 'Data unit kerja berhasil diambil',
            'data' => $unitKerja,
            'total' => $unitKerja->count()
        ]);
    }
}
