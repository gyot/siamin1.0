<?php

namespace App\Http\Controllers\Api;

use App\Models\Tpk;
use Illuminate\Http\Request;

class TpkController extends BaseApiController
{
    protected $modelClass = Tpk::class;

    protected $rules = [
        'id_kegiatan' => 'required|exists:kegiatan,id_kegiatan',
        'lokasi' => 'required|string|max:255',
        'kabupaten_kota' => 'sometimes|nullable|string|max:255',
    ];

    public function index(Request $request)
    {
        $query = Tpk::with('kegiatan')->latest();

        if ($request->filled('id_kegiatan')) {
            $query->where('id_kegiatan', $request->input('id_kegiatan'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function show($id)
    {
        $item = Tpk::with('kegiatan')->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'TPK not found',
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
        $item = Tpk::create($validated);

        return response()->json([
            'success' => true,
            'data' => $item->load('kegiatan'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $item = Tpk::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'TPK not found',
            ], 404);
        }

        $validated = $request->validate([
            'id_kegiatan' => 'sometimes|exists:kegiatan,id_kegiatan',
            'lokasi' => 'sometimes|string|max:255',
            'kabupaten_kota' => 'sometimes|nullable|string|max:255',
        ]);

        $item->update($validated);

        return response()->json([
            'success' => true,
            'data' => $item->load('kegiatan'),
        ]);
    }

    public function destroy($id)
    {
        $item = Tpk::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'TPK not found',
            ], 404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'TPK deleted successfully',
        ]);
    }
}
