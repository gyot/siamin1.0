<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use Illuminate\Http\Request;

/**
 * API integrasi untuk aplikasi lain.
 * Autentikasi: Bearer token (Sanctum personal access token).
 * Data sensitif (rekening, kontak, NIK) tidak pernah dikirim.
 */
class IntegrasiPesertaController extends Controller
{
    /** Kolom sensitif yang tidak boleh dikirim ke aplikasi lain */
    private array $hidden = [
        'npwp_nik', 'nomor_rekening', 'nama_bank', 'provider_pulsa',
        'no_hp', 'telp_instansi', 'email', 'email_instansi',
    ];

    private array $kegiatanCols = [
        'id_kegiatan', 'nama_kegiatan', 'tanggal_mulai', 'tanggal_selesai',
        'metode_pelaksanaan', 'status',
    ];

    private function relasi()
    {
        return [
            'kegiatan:' . implode(',', $this->kegiatanCols),
            'tpk:id_tpk,lokasi,kabupaten_kota',
        ];
    }

    /**
     * GET /api/v1/integrasi/peserta
     * Filter opsional: q (nama/NIP), id_kegiatan, per_page (maks 100)
     */
    public function index(Request $request)
    {
        $query = Peserta::with($this->relasi())->orderBy('nama_lengkap');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('nama_lengkap', 'like', "%{$q}%")
                    ->orWhere('nip', 'like', "%{$q}%");
            });
        }

        if ($request->filled('id_kegiatan')) {
            $query->where('id_kegiatan', (int) $request->id_kegiatan);
        }

        $perPage = max(1, min((int) ($request->per_page ?? 20), 100));
        $paginator = $query->paginate($perPage);
        $paginator->getCollection()->makeHidden($this->hidden);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/integrasi/peserta/{id}
     * Detail peserta + kegiatan yang diikuti (semua keikutsertaan dengan NIP sama)
     */
    public function show($id)
    {
        $peserta = Peserta::with($this->relasi())->find($id);

        if (!$peserta) {
            return response()->json(['success' => false, 'message' => 'Peserta tidak ditemukan.'], 404);
        }

        $kegiatanDiikuti = collect();
        if (!empty($peserta->nip)) {
            $kegiatanDiikuti = Peserta::with('kegiatan:' . implode(',', $this->kegiatanCols))
                ->where('nip', $peserta->nip)
                ->orderByDesc('id_peserta')
                ->get()
                ->map(fn ($p) => [
                    'id_peserta' => $p->id_peserta,
                    'id_kegiatan' => $p->id_kegiatan,
                    'peran' => $p->peran,
                    'kegiatan' => $p->kegiatan,
                ])
                ->values();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'peserta' => $peserta->makeHidden($this->hidden),
                'kegiatan' => $peserta->kegiatan,
                'kegiatan_diikuti' => $kegiatanDiikuti,
            ],
        ]);
    }
}
