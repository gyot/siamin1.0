<?php

namespace App\Services;

use App\Models\KeanggotaanTim;
use App\Models\Kegiatan;
use App\Models\KegiatanAtk;
use App\Models\Tpk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class KegiatanService
{
    public function query()
    {
        $query = Kegiatan::query();

        if ($this->hasAtkTable()) {
            $query->with('daftarAtk');
        }

        if ($this->hasTpkTable()) {
            $query->with('daftarTpk');
        }

        return $query;
    }

    public function create(array $validated): Kegiatan
    {
        return DB::transaction(function () use ($validated) {
            $atkItems = $validated['daftar_atk'] ?? [];
            $tpkItems = $validated['daftar_tpk'] ?? [];
            unset($validated['daftar_atk'], $validated['daftar_tpk']);

            $kegiatan = Kegiatan::create($validated);

            $this->syncAtkRecords($kegiatan, $atkItems);
            $this->syncTpkRecords($kegiatan, $tpkItems);

            return $this->loadRelations($kegiatan);
        });
    }

    public function update(Kegiatan $kegiatan, array $validated, bool $atkItemsProvided, bool $tpkItemsProvided): Kegiatan
    {
        return DB::transaction(function () use ($kegiatan, $validated, $atkItemsProvided, $tpkItemsProvided) {
            $atkItems = $validated['daftar_atk'] ?? [];
            $tpkItems = $validated['daftar_tpk'] ?? [];
            unset($validated['daftar_atk'], $validated['daftar_tpk']);

            $kegiatan->update($validated);

            if ($atkItemsProvided) {
                $this->syncAtkRecords($kegiatan, $atkItems);
            }

            if ($tpkItemsProvided) {
                $this->syncTpkRecords($kegiatan, $tpkItems);
            }

            return $this->loadRelations($kegiatan);
        });
    }

    public function delete(Kegiatan $kegiatan): void
    {
        DB::transaction(function () use ($kegiatan) {
            if ($this->hasAtkTable()) {
                $kegiatan->daftarAtk()->delete();
            }

            if ($this->hasTpkTable()) {
                $kegiatan->daftarTpk()->delete();
            }

            $kegiatan->delete();
        });
    }

    public function preparePayload(Request $request, array $validated, ?Kegiatan $existing = null): array
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

    public function loadRelations(Kegiatan $kegiatan): Kegiatan
    {
        $relations = [];

        if ($this->hasAtkTable()) {
            $relations[] = 'daftarAtk';
        } else {
            $kegiatan->setRelation('daftarAtk', collect());
        }

        if ($this->hasTpkTable()) {
            $relations[] = 'daftarTpk';
        } else {
            $kegiatan->setRelation('daftarTpk', collect());
        }

        if (!empty($relations)) {
            return $kegiatan->load($relations);
        }

        return $kegiatan;
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

    private function syncTpkRecords(Kegiatan $kegiatan, array $tpkItems): void
    {
        if (!$this->hasTpkTable()) {
            return;
        }

        $kegiatan->daftarTpk()->delete();

        if (empty($tpkItems)) {
            return;
        }

        $payload = collect($tpkItems)->map(function ($item) use ($kegiatan) {
            return [
                'id_kegiatan' => $kegiatan->id_kegiatan,
                'lokasi' => $item['lokasi'],
                'kabupaten_kota' => $item['kabupaten_kota'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        Tpk::insert($payload);
    }

    private function hasAtkTable(): bool
    {
        static $hasAtkTable = null;

        if ($hasAtkTable === null) {
            $hasAtkTable = Schema::hasTable('kegiatan_atk');
        }

        return $hasAtkTable;
    }

    private function hasTpkTable(): bool
    {
        static $hasTpkTable = null;

        if ($hasTpkTable === null) {
            $hasTpkTable = Schema::hasTable('tpk');
        }

        return $hasTpkTable;
    }

}
