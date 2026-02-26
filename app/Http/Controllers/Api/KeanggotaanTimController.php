<?php

namespace App\Http\Controllers\Api;

use App\Models\KeanggotaanTim;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class KeanggotaanTimController extends BaseApiController
{
    protected $modelClass = KeanggotaanTim::class;

    protected $rules = [
        'id_pegawai' => 'required|exists:pegawai,id_pegawai',
        'unit_kerja_id' => 'required|exists:unit_kerja,id',
        'sub_unit_kerja_id' => 'nullable|exists:sub_unit_kerja,id',
        'peran' => 'nullable|string|max:100',
        'tahun' => 'nullable|digits:4|integer',
    ];

    /**
     * List resources with optional filters and pagination.
     */
    public function index()
    {
        $request = request();
        $query = KeanggotaanTim::with(['pegawai', 'unit', 'subUnit']);

        if ($request->filled('id_pegawai')) {
            $query->where('id_pegawai', $request->input('id_pegawai'));
        }
        if ($request->filled('unit_kerja_id')) {
            $query->where('unit_kerja_id', $request->input('unit_kerja_id'));
        }
        if ($request->filled('tahun')) {
            $query->where('tahun', $request->input('tahun'));
        }
        if ($request->filled('peran')) {
            $query->where('peran', 'like', '%'.$request->input('peran').'%');
        }

        // pagination support
        if ($request->has('page') || $request->has('limit')) {
            $limit = (int) $request->input('limit', 15);
            $data = $query->paginate($limit);
            return response()->json(['success' => true, 'data' => $data]);
        }

        $data = $query->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules);
        $item = KeanggotaanTim::create($validated);
        $item->load(['pegawai', 'unit', 'subUnit']);
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function show($id)
    {
        try {
            $item = KeanggotaanTim::with(['pegawai', 'unit', 'subUnit'])->findOrFail($id);
            return response()->json(['success' => true, 'data' => $item]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $item = KeanggotaanTim::findOrFail($id);
            $validated = $request->validate($this->rules);
            $item->update($validated);
            $item->load(['pegawai', 'unit', 'subUnit']);
            return response()->json(['success' => true, 'data' => $item]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $item = KeanggotaanTim::findOrFail($id);
            $item->delete();
            return response()->json(['success' => true, 'message' => 'Deleted']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
    }
}
