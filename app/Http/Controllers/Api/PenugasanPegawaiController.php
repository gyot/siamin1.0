<?php

namespace App\Http\Controllers\Api;

use App\Models\PenugasanPegawai;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PenugasanPegawaiController extends BaseApiController
{
    protected $modelClass = PenugasanPegawai::class;

    protected array $peranOptions = [
        'penanggung_jawab',
        'ketua_panitia',
        'panitia',
        'peserta',
        'narasumber',
    ];

    protected $rules = [
        'id_kegiatan' => 'required|exists:kegiatan,id_kegiatan',
        'id_pegawai' => 'required|exists:pegawai,id_pegawai',
        'peran' => 'sometimes|nullable|string',
    ];

    public function index(Request $request)
    {
        $query = PenugasanPegawai::with(['pegawai', 'kegiatan']);

        if ($request->filled('id_kegiatan')) {
            $query->where('id_kegiatan', $request->input('id_kegiatan'));
        }

        if ($request->filled('id_pegawai')) {
            $query->where('id_pegawai', $request->input('id_pegawai'));
        }

        $data = $query->get();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->storeRules());

        if ($this->hasDuplicateAssignment($validated['id_kegiatan'], $validated['id_pegawai'])) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai sudah ditambahkan pada kegiatan ini',
            ], 422);
        }

        $item = PenugasanPegawai::create($validated);

        return response()->json([
            'success' => true,
            'data' => $this->loadResource($item),
        ], 201);
    }

    public function show($id)
    {
        try {
            $item = PenugasanPegawai::with(['pegawai', 'kegiatan'])->findOrFail($id);

            return response()->json(['success' => true, 'data' => $item]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $item = PenugasanPegawai::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $validated = $request->validate($this->updateRules());

        $kegiatanId = $validated['id_kegiatan'] ?? $item->id_kegiatan;
        $pegawaiId = $validated['id_pegawai'] ?? $item->id_pegawai;

        if ($this->hasDuplicateAssignment($kegiatanId, $pegawaiId, $item->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai sudah ditambahkan pada kegiatan ini',
            ], 422);
        }

        $item->update($validated);

        return response()->json([
            'success' => true,
            'data' => $this->loadResource($item),
        ]);
    }

    protected function storeRules(): array
    {
        return [
            'id_kegiatan' => 'required|exists:kegiatan,id_kegiatan',
            'id_pegawai' => 'required|exists:pegawai,id_pegawai',
            'peran' => ['sometimes', 'nullable', Rule::in($this->peranOptions)],
        ];
    }

    protected function updateRules(): array
    {
        return [
            'id_kegiatan' => 'sometimes|exists:kegiatan,id_kegiatan',
            'id_pegawai' => 'sometimes|exists:pegawai,id_pegawai',
            'peran' => ['sometimes', 'nullable', Rule::in($this->peranOptions)],
        ];
    }

    protected function hasDuplicateAssignment(int $kegiatanId, int $pegawaiId, ?int $ignoreId = null): bool
    {
        return PenugasanPegawai::query()
            ->where('id_kegiatan', $kegiatanId)
            ->where('id_pegawai', $pegawaiId)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    protected function loadResource(PenugasanPegawai $item): PenugasanPegawai
    {
        return $item->load(['pegawai', 'kegiatan']);
    }
}
