<?php

namespace App\Http\Controllers\Api;

use App\Models\KegiatanAtk;
use Illuminate\Http\Request;

class KegiatanAtkController extends BaseApiController
{
    protected $modelClass = KegiatanAtk::class;

    protected $rules = [
        'id_kegiatan' => 'required|exists:kegiatan,id_kegiatan',
        'nama_barang' => 'required|string|max:255',
        'spesifikasi' => 'sometimes|nullable|string|max:255',
        'jumlah' => 'sometimes|integer|min:1',
        'satuan' => 'sometimes|nullable|string|max:100',
        'keterangan' => 'sometimes|nullable|string',
    ];

    public function index()
    {
        $query = KegiatanAtk::with('kegiatan')->latest();

        if (request()->filled('id_kegiatan')) {
            $query->where('id_kegiatan', request('id_kegiatan'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function show($id)
    {
        $item = KegiatanAtk::with('kegiatan')->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'ATK not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules);
        $item = KegiatanAtk::create($validated);

        return response()->json([
            'success' => true,
            'data' => $item->load('kegiatan'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $item = KegiatanAtk::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'ATK not found',
            ], 404);
        }

        $validated = $request->validate([
            'id_kegiatan' => 'sometimes|exists:kegiatan,id_kegiatan',
            'nama_barang' => 'sometimes|string|max:255',
            'spesifikasi' => 'sometimes|nullable|string|max:255',
            'jumlah' => 'sometimes|integer|min:1',
            'satuan' => 'sometimes|nullable|string|max:100',
            'keterangan' => 'sometimes|nullable|string',
        ]);

        $item->update($validated);

        return response()->json([
            'success' => true,
            'data' => $item->load('kegiatan'),
        ]);
    }

    public function destroy($id)
    {
        $item = KegiatanAtk::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'ATK not found',
            ], 404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'ATK deleted successfully',
        ]);
    }
}
