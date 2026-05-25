<?php

namespace App\Http\Controllers\Api;

use App\Models\PenugasanPegawai;
use App\Http\Resources\PenugasanDetailResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class PenugasanPegawaiController extends BaseApiController
{
    protected $modelClass = PenugasanPegawai::class;

    protected array $peranOptions = [
        
    ];

    public function peran()
    {
        $column = DB::table('peran')->get();
        $this->peranOptions = $column->pluck('value')->toArray();
        return response()->json([
            'success' => true,
            'data' => $column,
        ]);
    }
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

    /**
     * Get penugasan data with details (pagination enabled)
     * Includes pegawai name, kegiatan details, dates, and location
     */
    public function indexWithDetails(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $perPage = min((int) $perPage, 100); // Max 100 items per page

        $query = PenugasanPegawai::with([
            'pegawai:id_pegawai,nama',
            'kegiatan:id_kegiatan,nama_kegiatan,tanggal_mulai,tanggal_selesai,kabupaten_kota,lokasi'
        ]);

        // Optional filters
        if ($request->filled('id_kegiatan')) {
            $query->where('id_kegiatan', $request->input('id_kegiatan'));
        }

        if ($request->filled('id_pegawai')) {
            $query->where('id_pegawai', $request->input('id_pegawai'));
        }

        if ($request->filled('peran')) {
            $query->where('peran', $request->input('peran'));
        }

        // Search by pegawai name or kegiatan name
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('pegawai', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            })->orWhereHas('kegiatan', function ($q) use ($search) {
                $q->where('nama_kegiatan', 'like', "%{$search}%");
            });
        }

        // Sort options
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');

        // Validate sort_order to prevent injection
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        if ($sortBy === 'tanggal_mulai') {
            $query->leftJoin('kegiatan', 'penugasan_pegawai.id_kegiatan', '=', 'kegiatan.id_kegiatan')
                  ->select('penugasan_pegawai.*')
                  ->orderBy('kegiatan.tanggal_mulai', $sortOrder);
        } else {
            $query->orderBy("penugasan_pegawai.{$sortBy}", $sortOrder);
        }

        $data = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => PenugasanDetailResource::collection($data),
            'pagination' => [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
        ]);
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
