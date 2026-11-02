<?php

namespace App\Services;

use App\Models\KeanggotaanTim;
use App\Models\Kegiatan;
use App\Models\KegiatanAtk;
use App\Models\Kelas;
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

        $query->with('daftarKelas');
        $query->with('paketSoals');

        return $query;
    }

    public function create(array $validated): Kegiatan
    {
        return DB::transaction(function () use ($validated) {
            $atkItems = $validated['daftar_atk'] ?? [];
            $tpkItems = $validated['daftar_tpk'] ?? [];
            $kelasItems = $validated['daftar_kelas'] ?? [];
            unset($validated['daftar_atk'], $validated['daftar_tpk'], $validated['daftar_kelas']);

            $kegiatan = Kegiatan::create($validated);

            $this->syncAtkRecords($kegiatan, $atkItems);
            $this->syncTpkRecords($kegiatan, $tpkItems);
            // Kelas: jika tidak ada kelas yang ditambahkan -> otomatis satu "Kelas Umum"
            $this->syncKelasRecords($kegiatan, $kelasItems, true);

            return $this->loadRelations($kegiatan);
        });
    }

    public function update(Kegiatan $kegiatan, array $validated, bool $atkItemsProvided, bool $tpkItemsProvided, bool $paketSoalProvided = false, bool $kelasItemsProvided = false): Kegiatan
    {
        return DB::transaction(function () use ($kegiatan, $validated, $atkItemsProvided, $tpkItemsProvided, $paketSoalProvided, $kelasItemsProvided) {
            $atkItems = $validated['daftar_atk'] ?? [];
            $tpkItems = $validated['daftar_tpk'] ?? [];
            $kelasItems = $validated['daftar_kelas'] ?? [];
            $paketSoalIds = $validated['daftar_paket_soal'] ?? [];
            unset($validated['daftar_atk'], $validated['daftar_tpk'], $validated['daftar_kelas'], $validated['daftar_paket_soal']);

            $kegiatan->update($validated);

            if ($atkItemsProvided) {
                $this->syncAtkRecords($kegiatan, $atkItems);
            }

            if ($tpkItemsProvided) {
                $this->syncTpkRecords($kegiatan, $tpkItems);
            }

            if ($kelasItemsProvided) {
                $this->syncKelasRecords($kegiatan, $kelasItems, true);
            }

            if ($paketSoalProvided) {
                $this->syncPaketSoal($kegiatan, $paketSoalIds);
            }

            return $this->loadRelations($kegiatan);
        });
    }

    /**
     * Sinkronkan daftar kelas kegiatan.
     * - item dengan id_kelas -> update
     * - item tanpa id_kelas -> create
     * - kelas lama yang tidak dikirim -> hapus
     * - $ensureDefault: jika hasilnya kosong, buat satu "Kelas Umum"
     */
    private function syncKelasRecords(Kegiatan $kegiatan, array $kelasItems, bool $ensureDefault = false): void
    {
        $existing = $kegiatan->daftarKelas()->get();
        $submittedIds = collect();

        foreach ($kelasItems as $item) {
            $nama = trim((string) ($item['nama_kelas'] ?? ''));
            if ($nama === '') {
                continue;
            }

            $kelas = null;
            if (!empty($item['id_kelas'])) {
                $kelas = $existing->firstWhere('id_kelas', (int) $item['id_kelas']);
            }
            if (!$kelas) {
                $kelas = $existing->firstWhere('nama_kelas', $nama);
            }

            if ($kelas) {
                $kelas->update([
                    'nama_kelas' => $nama,
                    'deskripsi' => $item['deskripsi'] ?? null,
                ]);
                $submittedIds->push($kelas->id_kelas);
            } else {
                $baru = Kelas::create([
                    'id_kegiatan' => $kegiatan->id_kegiatan,
                    'nama_kelas' => $nama,
                    'deskripsi' => $item['deskripsi'] ?? null,
                ]);
                $submittedIds->push($baru->id_kelas);
            }
        }

        $kegiatan->daftarKelas()
            ->whereNotIn('id_kelas', $submittedIds->all())
            ->delete();

        if ($ensureDefault && $kegiatan->daftarKelas()->count() === 0) {
            Kelas::create([
                'id_kegiatan' => $kegiatan->id_kegiatan,
                'nama_kelas' => 'Kelas Umum',
                'deskripsi' => null,
            ]);
        }
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

            $kegiatan->daftarKelas()->delete();

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

        $relations[] = 'daftarKelas';

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
        $kegiatan->paketSoals()->sync($paketSoalIds);
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
