<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SekolahController extends Controller
{
    /**
     * Daftar sekolah (dedupe by NPSN via view daftar_sekolah)
     * dengan pagination, pencarian, dan pengurutan sisi server.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $allowedSort = ['npsn', 'nama_sekolah', 'kab_kota', 'provinsi', 'jumlah_peserta', 'jumlah_kegiatan'];
        $sortBy = $request->input('sort_by', 'nama_sekolah');
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'nama_sekolah';
        }
        $sortDir = strtolower((string) $request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->input('per_page', 10);
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 10;
        }
        $page = max((int) $request->input('page', 1), 1);

        $query = DB::table('daftar_sekolah');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('nama_sekolah', 'like', "%{$q}%")
                    ->orWhere('npsn', 'like', "%{$q}%")
                    ->orWhere('kab_kota', 'like', "%{$q}%")
                    ->orWhere('provinsi', 'like', "%{$q}%");
            });
        }

        $total = (clone $query)->count();
        $rows = $query->orderBy($sortBy, $sortDir)
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max((int) ceil($total / $perPage), 1),
            ],
        ]);
    }
}
