<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubUnitKerja;
use Illuminate\Http\JsonResponse;

class SubUnitKerjaController extends Controller
{
    /**
     * Display a listing of sub unit kerja.
     */
    public function index(): JsonResponse
    {
        $subUnitKerja = SubUnitKerja::with('unitKerja')->get();

        return response()->json([
            'success' => true,
            'message' => 'Data sub unit kerja berhasil diambil',
            'data' => $subUnitKerja,
            'total' => $subUnitKerja->count()
        ]);
    }
}
