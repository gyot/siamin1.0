<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Http\Request;

/**
 * Lonceng notifikasi user login:
 * - GET    /api/v1/notifikasi               -> 20 notifikasi terbaru + jumlah_belum_dibaca
 * - POST   /api/v1/notifikasi/{id}/baca     -> tandai satu notifikasi dibaca
 * - POST   /api/v1/notifikasi/baca-semua    -> tandai semua notifikasi belum dibaca
 */
class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $idUser = $request->user()->getAuthIdentifier();

        $data = Notifikasi::where('id_user', $idUser)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'jumlah_belum_dibaca' => $this->jumlahBelumDibaca($idUser),
        ]);
    }

    public function baca(Request $request, $id)
    {
        $idUser = $request->user()->getAuthIdentifier();

        $notifikasi = Notifikasi::where('id_user', $idUser)->find($id);
        if (!$notifikasi) {
            return response()->json(['success' => false, 'message' => 'Notifikasi tidak ditemukan.'], 404);
        }

        if ($notifikasi->dibaca_pada === null) {
            $notifikasi->update(['dibaca_pada' => now()]);
        }

        return response()->json([
            'success' => true,
            'data' => $notifikasi->fresh(),
            'jumlah_belum_dibaca' => $this->jumlahBelumDibaca($idUser),
        ]);
    }

    public function bacaSemua(Request $request)
    {
        $idUser = $request->user()->getAuthIdentifier();

        Notifikasi::where('id_user', $idUser)
            ->whereNull('dibaca_pada')
            ->update(['dibaca_pada' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi ditandai sudah dibaca.',
            'jumlah_belum_dibaca' => 0,
        ]);
    }

    private function jumlahBelumDibaca(int $idUser): int
    {
        return Notifikasi::where('id_user', $idUser)->whereNull('dibaca_pada')->count();
    }
}
