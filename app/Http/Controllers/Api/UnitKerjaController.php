<?php

namespace App\Http\Controllers\Api;

use App\Models\UnitKerja;
use Illuminate\Http\Request;

class UnitKerjaController extends BaseApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = UnitKerja::where('kode_unit', '!=', '000')->get();

        return response()->json($data);
    }
}