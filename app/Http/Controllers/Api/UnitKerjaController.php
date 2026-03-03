<?php

namespace App\Http\Controllers\Api;

use App\Models\UnitKerja;
use Illuminate\Http\Request;
use App\Models\KeanggotaanTim;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

    public function unit_user($id)
    {
        $data = KeanggotaanTim::where('id_pegawai', $id)->get();
    
        return response()->json($data);
    }
}