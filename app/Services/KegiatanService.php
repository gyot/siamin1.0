<?php

namespace App\Services;

use App\Models\KeanggotaanTim;
use App\Models\Kegiatan;
use App\Models\KegiatanAtk;
use App\Models\PaketSoal;
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

        $query->with('paketSoals');

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

    public function update(Kegiatan $kegiatan, array $validated, bool $atkItemsProvided, bool $tpkItemsProvided, bool $paketSoalProvided = false): Kegiatan
    {
        return DB::transaction(function () use ($kegiatan, $validated, $atkItemsProvided, $tpkItemsProvided, $paketSoalProvided) {
            $atkItems = $validated['daftar_atk'] ?? [];
            $tpkItems = $validated['daftar_tpk'] ?? [];
            $paketSoalIds = $validated['daftar_paket_soal'] ?? [];
            unset($validated['daftar_atk'], $validated['daftar_tpk'], $validated['daftar_paket_soal']);

            $kegiatan->update($validated);

            if ($atkItemsProvided) {
                $this->syncAtkRecords($kegiatan, $atkItems);
            }

            if ($tpkItemsProvided) {
                $this->syncTpkRecords($kegiatan, $tpkItems);
            }

            if ($paketSoalProvided) {
                $this->syncPaketSoal($kegiatan, $paketSoalIds);
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
        $relations = ['paketSoals'];

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

        return $kegiatan->load($relations);
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

        $existingTpk = $kegiatan->daftarTpk()->get();
        $submittedIds = collect();

        foreach ($tpkItems as $item) {
            $tpk = null;

            if (!empty($item['id_tpk'])) {
                $tpk = $existingTpk->firstWhere('id_tpk', (int) $item['id_tpk']);
            }

            if (!$tpk) {
                $tpk = $existingTpk->first(function ($existing) use ($item) {
                    return $existing->lokasi === ($item['lokasi'] ?? '')
                        && $existing->kabupaten_kota === ($item['kabupaten_kota'] ?? null);
                });
            }

            if ($tpk) {
                $tpk->update([
                    'lokasi' => $item['lokasi'],
                    'kabupaten_kota' => $item['kabupaten_kota'] ?? null,
                ]);
                $submittedIds->push($tpk->id_tpk);
            } else {
                $newTpk = Tpk::create([
                    'id_kegiatan' => $kegiatan->id_kegiatan,
                    'lokasi' => $item['lokasi'],
                    'kabupaten_kota' => $item['kabupaten_kota'] ?? null,
                ]);
                $submittedIds->push($newTpk->id_tpk);
            }
        }

        $kegiatan->daftarTpk()
            ->whereNotIn('id_tpk', $submittedIds->all())
            ->delete();
    }

    private function syncPaketSoal(Kegiatan $kegiatan, array $paketSoalIds): void
    {
        $currentIds = PaketSoal::where('id_kegiatan', $kegiatan->id_kegiatan)
            ->pluck('id_paket_soal')
            ->all();

        $newIds = array_map('intval', $paketSoalIds);
        $toRemove = array_diff($currentIds, $newIds);
        $toAdd = array_diff($newIds, $currentIds);

        if (!empty($toRemove)) {
            PaketSoal::whereIn('id_paket_soal', $toRemove)
                ->where('id_kegiatan', $kegiatan->id_kegiatan)
                ->update(['id_kegiatan' => null]);
        }

        if (!empty($toAdd)) {
            PaketSoal::whereIn('id_paket_soal', $toAdd)
                ->whereNull('id_kegiatan')
                ->update(['id_kegiatan' => $kegiatan->id_kegiatan]);
        }
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
