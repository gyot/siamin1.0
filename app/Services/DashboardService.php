<?php

namespace App\Services;

use App\Models\Kegiatan;
use App\Models\Peserta;
use App\Models\Sertifikat;
use App\Models\UnitKerja;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function paginateKegiatanForDashboard(array $filters): LengthAwarePaginator
    {
        $page = (int) ($filters['page'] ?? 1);
        $limit = (int) ($filters['limit'] ?? 9);

        return Kegiatan::query()
            ->leftJoin('unit_kerja', 'unit_kerja.id', '=', 'kegiatan.unit_kerja_id')
            ->select([
                'kegiatan.id_kegiatan',
                'kegiatan.nama_kegiatan',
                'kegiatan.tanggal_mulai',
                'kegiatan.tanggal_selesai',
                'kegiatan.lokasi',
                'kegiatan.status',
                'kegiatan.total_peserta',
                'kegiatan.peserta_ringkasan',
                'kegiatan.unit_kerja_id',
                'unit_kerja.nama_unit as unit_kerja',
            ])
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where('kegiatan.nama_kegiatan', 'like', "%{$search}%");
            })
            ->when(! empty($filters['tahun']), function ($query) use ($filters) {
                $query->whereYear('kegiatan.tanggal_mulai', (int) $filters['tahun']);
            })
            ->when(! empty($filters['status']), function ($query) use ($filters) {
                $query->where('kegiatan.status', $filters['status']);
            })
            ->when(! empty($filters['unit_kerja']), function ($query) use ($filters) {
                $unitKerja = $filters['unit_kerja'];

                $query->where(function ($subQuery) use ($unitKerja) {
                    if (ctype_digit((string) $unitKerja)) {
                        $subQuery->orWhere('kegiatan.unit_kerja_id', (int) $unitKerja);
                    }

                    $subQuery
                        ->orWhere('unit_kerja.nama_unit', 'like', "%{$unitKerja}%")
                        ->orWhere('unit_kerja.kode_unit', 'like', "%{$unitKerja}%");
                });
            })
            ->orderByDesc('kegiatan.tanggal_mulai')
            ->orderByDesc('kegiatan.id_kegiatan')
            ->paginate($limit, ['*'], 'page', $page);
    }

    public function getDashboardStats(): array
    {
        $today = Carbon::today()->toDateString();

        return [
            'total_kegiatan' => Kegiatan::query()->count(),
            'kegiatan_berjalan' => Kegiatan::query()
                ->whereDate('tanggal_mulai', '<=', $today)
                ->whereDate('tanggal_selesai', '>=', $today)
                ->where('status', '!=', 'dibatalkan')
                ->count(),
            'total_peserta' => Peserta::query()->count(),
            'total_sertifikat_terbit' => Sertifikat::query()
                ->where('status', 'terbit')
                ->count(),
            'available_tahun' => $this->getAvailableTahun(),
            'available_unit_kerja' => $this->getAvailableUnitKerja(),
        ];
    }

    private function getAvailableTahun(): array
    {
        $driver = DB::connection()->getDriverName();
        $yearExpression = $driver === 'sqlite'
            ? "strftime('%Y', tanggal_mulai)"
            : 'YEAR(tanggal_mulai)';

        return Kegiatan::query()
            ->whereNotNull('tanggal_mulai')
            ->selectRaw("{$yearExpression} as tahun")
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun')
            ->map(fn ($tahun) => (string) $tahun)
            ->values()
            ->all();
    }

    private function getAvailableUnitKerja(): array
    {
        return UnitKerja::query()
            ->join('kegiatan', 'kegiatan.unit_kerja_id', '=', 'unit_kerja.id')
            ->select('unit_kerja.nama_unit')
            ->distinct()
            ->orderBy('unit_kerja.nama_unit')
            ->pluck('unit_kerja.nama_unit')
            ->filter()
            ->values()
            ->all();
    }
}
