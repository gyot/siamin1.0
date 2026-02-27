<?php

namespace App\Http\Controllers\Api;

use App\Models\UnitKerja;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitKerjaController extends BaseApiController
{
    protected $modelClass = UnitKerja::class;

    public function index()
    {
        $data = UnitKerja::where('kode_unit', '!=', '000')->orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_unit' => 'required|string|max:50|unique:unit_kerja,kode_unit',
            'nama_unit' => 'required|string|max:255',
            'jenis_unit' => 'nullable|string|max:50',
            'tahun' => 'nullable|integer|digits:4',
            'keterangan' => 'nullable|string',
        ]);

        $item = UnitKerja::create($validated);
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function show($id)
    {
        try {
            $item = UnitKerja::findOrFail($id);
            return response()->json(['success' => true, 'data' => $item]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Unit Kerja not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $item = UnitKerja::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Unit Kerja not found'], 404);
        }

        $validated = $request->validate([
            'kode_unit' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('unit_kerja', 'kode_unit')->ignore($item->id),
            ],
            'nama_unit' => 'sometimes|string|max:255',
            'jenis_unit' => 'nullable|string|max:50',
            'tahun' => 'nullable|integer|digits:4',
            'keterangan' => 'nullable|string',
        ]);

        $item->update($validated);
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function destroy($id)
    {
        try {
            $item = UnitKerja::findOrFail($id);
            $item->delete();

            return response()->json(['success' => true, 'message' => 'Unit Kerja deleted']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Unit Kerja not found'], 404);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unit Kerja tidak bisa dihapus karena masih digunakan data lain',
            ], 422);
        }
    }
}
