<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DashboardKegiatanIndexRequest;
use App\Http\Resources\DashboardKegiatanCollection;
use App\Models\KeanggotaanTim;
use App\Models\Kegiatan;
use App\Models\KegiatanAtk;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class KegiatanController extends Controller
{
    /**
     * Display all kegiatan without filter
     */
    public function getAllKegiatan(DashboardKegiatanIndexRequest $request, DashboardService $dashboardService)
    {
        $paginator = $dashboardService->paginateKegiatanForDashboard($request->validated());

        return response()->json((new DashboardKegiatanCollection($paginator))->response()->getData(true));
    }

    public function getAllKegiatanTim($id)
    {
        $data = DB::table('kegiatan_tim')->where('unit_kerja_id', $id)->orderBy('tanggal_mulai', 'desc')->get();
        return response()->json(["success" => true, "data" => $data]);
    }

    public function getAllKegiatanTimKegiatan($id)
    {
        $data = DB::table('kegiatan')
            ->where('kegiatan.unit_kerja_id', $id)
            ->orderBy('kegiatan.tanggal_mulai', 'desc')
            ->get();

        return response()->json(["success" => true, "data" => $data]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Show kegiatan where:
        // 1. kegiatan.id_pegawai = logged-in pegawai, OR
        // 2. logged-in pegawai appears in surat_tugas_pegawai
        $user = auth()->user();
        $pegawaiId = $user?->id_pegawai;

        $query = $this->kegiatanQuery()->orderBy('tanggal_mulai', 'desc');

        if ($pegawaiId) {
            $query->where(function ($q) use ($pegawaiId) {
                $q->where('id_pegawai', $pegawaiId)
                  ->orWhereHas('suratTugasPegawais', function ($subQ) use ($pegawaiId) {
                      $subQ->where('id_pegawai', $pegawaiId);
                  });
            });
        } else {
            // if no pegawai id on user, return empty set
            $query->whereRaw('0 = 1');
        }

        $data = $query->get();

        return response()->json(["success" => true, "data" => $data]);
    }

    /** import.meta.env.VITE_API_BASE_URL
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->kegiatanRules(true));
        $validated = $this->prepareKegiatanPayload($request, $validated);
        $storedFiles = [];

        if ($request->hasFile('flyer')) {
            $path = $request->file('flyer')->store('flyers', 'public');
            $validated['flyer'] = $path;
            $storedFiles[] = $path;
        }
        if ($request->hasFile('template_biodata')) {
            $path = $request->file('template_biodata')->store('template_biodata', 'public');
            $validated['template_biodata'] = $path;
            $storedFiles[] = $path;
        }

        try {
            $kegiatan = DB::transaction(function () use ($validated) {
                $atkItems = $validated['daftar_atk'] ?? [];
                unset($validated['daftar_atk']);

                $kegiatan = Kegiatan::create($validated);

                $this->syncAtkRecords($kegiatan, $atkItems);

                return $this->loadAtkRelation($kegiatan);
            });
        } catch (\Throwable $e) {
            foreach ($storedFiles as $storedFile) {
                if (Storage::disk('public')->exists($storedFile)) {
                    Storage::disk('public')->delete($storedFile);
                }
            }

            throw $e;
        }

        return response()->json(["success" => true, "data" => $kegiatan], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $kegiatan = $this->kegiatanQuery()->find($id);
        if (!$kegiatan) {
            return response()->json(["success" => false, "message" => "Kegiatan not found"], 404);
        }
        return response()->json(["success" => true, "data" => $kegiatan]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $kegiatan = Kegiatan::find($id);
        if (!$kegiatan) {
            return response()->json(["success" => false, "message" => "Kegiatan yang anda cari tidak ditemukan"], 404);
        }

        $validated = $request->validate($this->kegiatanRules(false));
        $validated = $this->prepareKegiatanPayload($request, $validated, $kegiatan);

        if ($request->hasFile('flyer')) {
            $path = $request->file('flyer')->store('flyers', 'public');
            $validated['flyer'] = $path;
        } elseif ($request->exists('flyer') && blank($request->input('flyer'))) {
            $validated['flyer'] = null;
        } else {
            unset($validated['flyer']);
        }

        if ($request->hasFile('template_biodata')) {
            if ($kegiatan->template_biodata && Storage::disk('public')->exists($kegiatan->template_biodata)) {
                Storage::disk('public')->delete($kegiatan->template_biodata);
            }
            $path = $request->file('template_biodata')->store('template_biodata', 'public');
            $validated['template_biodata'] = $path;
        } elseif ($request->exists('template_biodata') && blank($request->input('template_biodata'))) {
            if ($kegiatan->template_biodata && Storage::disk('public')->exists($kegiatan->template_biodata)) {
                Storage::disk('public')->delete($kegiatan->template_biodata);
            }
            $validated['template_biodata'] = null;
        } else {
            unset($validated['template_biodata']);
        }

        DB::transaction(function () use ($kegiatan, $validated, $request) {
            $atkItemsProvided = $request->has('daftar_atk');
            $atkItems = $validated['daftar_atk'] ?? [];
            unset($validated['daftar_atk']);

            $kegiatan->update($validated);

            if ($atkItemsProvided) {
                $this->syncAtkRecords($kegiatan, $atkItems);
            }
        });

        return response()->json(["success" => true, "data" => $this->loadAtkRelation($kegiatan)]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $kegiatan = Kegiatan::find($id);
        if (!$kegiatan) {
            return response()->json(["success" => false, "message" => "Kegiatan not found"], 404);
        }

        if ($kegiatan->flyer && Storage::disk('public')->exists($kegiatan->flyer)) {
            Storage::disk('public')->delete($kegiatan->flyer);
        }

        if ($kegiatan->template_biodata && Storage::disk('public')->exists($kegiatan->template_biodata)) {
            Storage::disk('public')->delete($kegiatan->template_biodata);
        }

        $kegiatan->delete();
        return response()->json(["success" => true, "message" => "Deleted successfully"]);
    }

    private function kegiatanRules(bool $isStore): array
    {
        $requiredRule = $isStore ? 'required' : 'sometimes';

        return [
            'nama_kegiatan' => [$requiredRule, 'string', 'max:255'],
            'rincian_kegiatan' => 'sometimes|string',
            'dokumentasi_url' => 'sometimes|url|max:255',
            'materi_url' => 'sometimes|url|max:255',
            'panduan_url' => 'sometimes|url|max:255',
            'laporan_url' => 'sometimes|url|max:255',
            'surat_menyurat_url' => 'sometimes|url|max:255',
            'tanggal_mulai' => [$requiredRule, 'date'],
            'tanggal_selesai' => [$requiredRule, 'date', 'after_or_equal:tanggal_mulai'],
            'lokasi' => [$requiredRule, 'string', 'max:255'],
            'flyer' => 'sometimes|file|image|max:10048',
            'template_biodata' => 'sometimes|file|mimes:doc,docx|max:10120',
            'peserta_ringkasan' => 'sometimes|string',
            'total_peserta' => 'sometimes|integer|min:0',
            'metode_pembayaran' => [
                'sometimes',
                Rule::in(['transfer','pulsa','transfer_dan_pulsa','tunai','tidak_dibayar']),
            ],
            'deskripsi' => 'sometimes|nullable|string',
            'metode_pelaksanaan' => [
                'sometimes',
                Rule::in(['daring','luring','hybrid']),
            ],
            'status' => [
                $requiredRule,
                Rule::in(['draft','berjalan','selesai','dibatalkan']),
            ],
            'created_by' => 'sometimes|nullable|exists:users,id_user',
            'id_pegawai' => [$requiredRule, 'nullable', 'exists:pegawai,id_pegawai'],
            'unit_kerja_id' => 'sometimes|nullable|exists:unit_kerja,id',
            'daftar_atk' => 'sometimes|array',
            'daftar_atk.*.nama_barang' => [$requiredRule, 'string', 'max:255'],
            'daftar_atk.*.spesifikasi' => 'sometimes|nullable|string|max:255',
            'daftar_atk.*.jumlah' => 'sometimes|integer|min:1',
            'daftar_atk.*.satuan' => 'sometimes|nullable|string|max:100',
            'daftar_atk.*.keterangan' => 'sometimes|nullable|string',
        ];
    }

    private function syncAtkRecords(Kegiatan $kegiatan, array $atkItems): void
    {
        if (!$this->hasAtkTable()) {
            return;
        }

        $kegiatan->daftarAtk()->delete();

        if (empty($atkItems)) {
            return;
        }

        $payload = collect($atkItems)->map(function ($item) use ($kegiatan) {
            return [
                'id_kegiatan' => $kegiatan->id_kegiatan,
                'nama_barang' => $item['nama_barang'],
                'spesifikasi' => $item['spesifikasi'] ?? null,
                'jumlah' => $item['jumlah'] ?? 1,
                'satuan' => $item['satuan'] ?? null,
                'keterangan' => $item['keterangan'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        KegiatanAtk::insert($payload);
    }

    private function kegiatanQuery()
    {
        $query = Kegiatan::query();

        if ($this->hasAtkTable()) {
            $query->with('daftarAtk');
        }

        return $query;
    }

    private function loadAtkRelation(Kegiatan $kegiatan): Kegiatan
    {
        if ($this->hasAtkTable()) {
            return $kegiatan->load('daftarAtk');
        }

        $kegiatan->setRelation('daftarAtk', collect());

        return $kegiatan;
    }

    private function hasAtkTable(): bool
    {
        static $hasAtkTable = null;

        if ($hasAtkTable === null) {
            $hasAtkTable = Schema::hasTable('kegiatan_atk');
        }

        return $hasAtkTable;
    }

    private function prepareKegiatanPayload(Request $request, array $validated, ?Kegiatan $existing = null): array
    {
        $validated['id_pegawai'] = $validated['id_pegawai']
            ?? $existing?->id_pegawai
            ?? $request->user()?->id_pegawai;

        if (array_key_exists('created_by', $validated) === false && $request->user()) {
            $validated['created_by'] = $request->user()->getKey();
        }

        if (!empty($validated['unit_kerja_id'])) {
            return $validated;
        }

        if ($existing?->unit_kerja_id) {
            $validated['unit_kerja_id'] = $existing->unit_kerja_id;

            return $validated;
        }

        $pegawaiId = $validated['id_pegawai'] ?? null;

        if (!$pegawaiId) {
            throw ValidationException::withMessages([
                'id_pegawai' => ['ID pegawai wajib dikirim untuk menyimpan kegiatan.'],
            ]);
        }

        $unitKerjaIds = KeanggotaanTim::query()
            ->where('id_pegawai', $pegawaiId)
            ->pluck('unit_kerja_id')
            ->filter()
            ->unique()
            ->values();

        if ($unitKerjaIds->count() === 1) {
            $validated['unit_kerja_id'] = (int) $unitKerjaIds->first();

            return $validated;
        }

        if ($unitKerjaIds->count() > 1) {
            throw ValidationException::withMessages([
                'unit_kerja_id' => ['Pegawai memiliki lebih dari satu unit kerja. Frontend wajib mengirim unit_kerja_id.'],
            ]);
        }

        throw ValidationException::withMessages([
            'unit_kerja_id' => ['Unit kerja tidak ditemukan untuk pegawai ini. Frontend wajib mengirim unit_kerja_id yang valid.'],
        ]);
    }
}
