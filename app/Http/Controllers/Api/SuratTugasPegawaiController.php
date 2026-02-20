<?php

namespace App\Http\Controllers\Api;

use App\Models\SuratTugasPegawai;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Pegawai;

class SuratTugasPegawaiController extends BaseApiController
{
    protected $modelClass = SuratTugasPegawai::class;
    protected $rules = [
        'id_surat_tugas' => 'required|exists:surat_tugas,id_surat_tugas',
        'id_pegawai' => 'required|exists:pegawai,id_pegawai',
    ];

    // Prevent duplicate assignment and return created resource with pegawai info
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules);

        // check duplicate
        $exists = SuratTugasPegawai::where('id_surat_tugas', $validated['id_surat_tugas'])
            ->where('id_pegawai', $validated['id_pegawai'])
            ->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Pegawai sudah ditambahkan pada surat tugas ini'], 422);
        }

        $item = SuratTugasPegawai::create($validated);
        $pegawai = Pegawai::find($validated['id_pegawai']);
        $data = array_merge($item->toArray(), ['pegawai' => $pegawai]);
        return response()->json(['success' => true, 'data' => $data], 201);
    }

    // include pegawai relation on show
    public function show($id)
    {
        try {
            $item = SuratTugasPegawai::findOrFail($id);
            $pegawai = Pegawai::find($item->id_pegawai);
            $data = array_merge($item->toArray(), ['pegawai' => $pegawai]);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
    }
}
