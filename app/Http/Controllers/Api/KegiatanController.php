<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DashboardKegiatanIndexRequest;
use App\Http\Requests\Api\StoreKegiatanRequest;
use App\Http\Requests\Api\UpdateKegiatanRequest;
use App\Http\Resources\DashboardKegiatanCollection;
use App\Http\Resources\KegiatanResource;
use App\Models\KeanggotaanTim;
use App\Models\Kegiatan;
use App\Models\UnitKerja;
use App\Services\DashboardService;
use App\Services\KegiatanService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class KegiatanController extends Controller
{
    /**
     * Display all kegiatan without filter
     */
    public function statistik()
    {
        $data = DB::table('info_dashboard')->get();

        return response()->json(["success" => true, "data" => $data]);
    }
    public function getAllKegiatan(DashboardKegiatanIndexRequest $request, DashboardService $dashboardService)
    {
        $paginator = $dashboardService->paginateKegiatanForDashboard($request->validated());

        return response()->json((new DashboardKegiatanCollection($paginator))->response()->getData(true));
    }

    public function getAllKegiatanTim($id)
    {
        $user = auth('sanctum')->user();
        $pegawaiId = $user?->id_pegawai;

        if (!$pegawaiId) {
            return response()->json(["success" => false, "message" => "Unauthenticated."], 401);
        }

        $isAnggotaUnit = KeanggotaanTim::query()
            ->where('id_pegawai', $pegawaiId)
            ->where('unit_kerja_id', $id)
            ->exists();

        $query = DB::table('kegiatan_tim')
            ->where('kegiatan_tim.unit_kerja_id', $id);

        if (!$isAnggotaUnit) {
            $query->whereExists(function ($subQuery) use ($pegawaiId) {
                $subQuery->select(DB::raw(1))
                    ->from('penugasan_pegawai')
                    ->whereColumn('penugasan_pegawai.id_kegiatan', 'kegiatan_tim.id_kegiatan')
                    ->where('penugasan_pegawai.id_pegawai', $pegawaiId);
            });
        }

        $data = $query
            ->orderBy('kegiatan_tim.tanggal_mulai', 'desc')
            ->get();

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

    public function getKegiatanTimSaya(KegiatanService $kegiatanService)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(["success" => false, "message" => "Unauthenticated."], 401);
        }

        $pegawaiId = $user->id_pegawai;
        $unitKerjaIds = $this->resolveUserUnitKerjaIds($user);

        if (!$pegawaiId && $unitKerjaIds->isEmpty()) {
            return response()->json(["success" => true, "data" => []]);
        }

        $query = $kegiatanService->query()
            ->where(function ($q) use ($unitKerjaIds, $pegawaiId) {
                if ($unitKerjaIds->isNotEmpty()) {
                    $q->whereIn('unit_kerja_id', $unitKerjaIds->all());
                }

                if ($pegawaiId) {
                    $q->orWhereHas('penugasanPegawais', function ($subQ) use ($pegawaiId) {
                        $subQ->where('id_pegawai', $pegawaiId);
                    });
                }
            })
            ->orderBy('tanggal_mulai', 'desc');

        return response()->json(["success" => true, "data" => KegiatanResource::collection($query->get())]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(KegiatanService $kegiatanService)
    {
        // Show kegiatan where:
        // 1. kegiatan.id_pegawai = logged-in pegawai, OR
        // 2. logged-in pegawai appears in penugasan_pegawai
        $user = auth('sanctum')->user();
        $pegawaiId = $user?->id_pegawai;

        $query = $kegiatanService->query()->orderBy('tanggal_mulai', 'desc');

        if ($pegawaiId) {
            $query->where(function ($q) use ($pegawaiId) {
                $q->where('id_pegawai', $pegawaiId)
                  ->orWhereHas('penugasanPegawais', function ($subQ) use ($pegawaiId) {
                      $subQ->where('id_pegawai', $pegawaiId);
                  });
            });
        } else {
            // if no pegawai id on user, return empty set
            $query->whereRaw('0 = 1');
        }

        $data = $query->get();

        return response()->json(["success" => true, "data" => KegiatanResource::collection($data)]);
    }

    /** import.meta.env.VITE_API_BASE_URL
     * Store a newly created resource in storage.
     */
    public function store(StoreKegiatanRequest $request, KegiatanService $kegiatanService)
    {
        $validated = $request->validated();
        $validated = $kegiatanService->preparePayload($request, $validated);
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
            $kegiatan = $kegiatanService->create($validated);
        } catch (\Throwable $e) {
            foreach ($storedFiles as $storedFile) {
                if (Storage::disk('public')->exists($storedFile)) {
                    Storage::disk('public')->delete($storedFile);
                }
            }

            throw $e;
        }

        return response()->json(["success" => true, "data" => new KegiatanResource($kegiatan)], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id, KegiatanService $kegiatanService)
    {
        $kegiatan = $kegiatanService->query()->find($id);
        if (!$kegiatan) {
            return response()->json(["success" => false, "message" => "Kegiatan not found"], 404);
        }
        return response()->json(["success" => true, "data" => new KegiatanResource($kegiatan)]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateKegiatanRequest $request, $id, KegiatanService $kegiatanService)
    {
        $kegiatan = Kegiatan::find($id);
        if (!$kegiatan) {
            return response()->json(["success" => false, "message" => "Kegiatan yang anda cari tidak ditemukan"], 404);
        }

        $validated = $request->validated();
        $validated = $kegiatanService->preparePayload($request, $validated, $kegiatan);
        $atkItemsProvided = $request->has('daftar_atk');
        $tpkItemsProvided = $request->has('daftar_tpk');
        $storedFiles = [];

        if ($request->hasFile('flyer')) {
            $path = $request->file('flyer')->store('flyers', 'public');
            $validated['flyer'] = $path;
            $storedFiles[] = $path;
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
            $storedFiles[] = $path;
        } elseif ($request->exists('template_biodata') && blank($request->input('template_biodata'))) {
            if ($kegiatan->template_biodata && Storage::disk('public')->exists($kegiatan->template_biodata)) {
                Storage::disk('public')->delete($kegiatan->template_biodata);
            }
            $validated['template_biodata'] = null;
        } else {
            unset($validated['template_biodata']);
        }

        try {
            $kegiatan = $kegiatanService->update($kegiatan, $validated, $atkItemsProvided, $tpkItemsProvided);
        } catch (\Throwable $e) {
            foreach ($storedFiles as $storedFile) {
                if (Storage::disk('public')->exists($storedFile)) {
                    Storage::disk('public')->delete($storedFile);
                }
            }

            throw $e;
        }

        return response()->json(["success" => true, "data" => new KegiatanResource($kegiatan)]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id, KegiatanService $kegiatanService)
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

        $kegiatanService->delete($kegiatan);
        return response()->json(["success" => true, "message" => "Deleted successfully"]);
    }

    private function resolveUserUnitKerjaIds($user)
    {
        $idsFromTim = KeanggotaanTim::query()
            ->where('id_pegawai', $user->id_pegawai)
            ->pluck('unit_kerja_id');

        $rawIdTim = array_key_exists('id_tim', $user->getAttributes())
            ? $user->getAttributes()['id_tim']
            : null;

        $rawIds = $this->normalizeRawIds($rawIdTim);
        $numericRawIds = $rawIds
            ->filter(fn ($value) => preg_match('/^\d+$/', (string) $value))
            ->map(fn ($value) => (int) $value);

        $mappedUnitIds = collect();

        if ($rawIds->isNotEmpty()) {
            $mappedUnitIds = UnitKerja::query()
                ->whereIn('kode_unit', $rawIds->all())
                ->orWhereIn('id', $numericRawIds->all())
                ->pluck('id');
        }

        return $idsFromTim
            ->merge($numericRawIds)
            ->merge($mappedUnitIds)
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();
    }

    private function normalizeRawIds($value)
    {
        if (is_null($value)) {
            return collect();
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->values();
        }

        $raw = trim((string) $value);

        if ($raw === '') {
            return collect();
        }

        if (str_starts_with($raw, '[') && str_ends_with($raw, ']')) {
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                return collect($decoded)
                    ->map(fn ($item) => trim((string) $item))
                    ->filter()
                    ->values();
            }
        }

        return collect(explode(',', $raw))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values();
    }

}
