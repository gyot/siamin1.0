<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\KelasAnggota;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    public function index(Request $request, $id_kegiatan)
    {
        $kelas = Kelas::where('id_kegiatan', $id_kegiatan)
            ->withCount('anggotas')
            ->orderBy('nama_kelas')
            ->get();

        return response()->json(['success' => true, 'data' => $kelas]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_kegiatan' => 'required|exists:kegiatan,id_kegiatan',
            'nama_kelas' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:1000',
        ]);

        $kelas = Kelas::create($validated);

        return response()->json(['success' => true, 'data' => $kelas], 201);
    }

    public function show($id_kelas)
    {
        $kelas = Kelas::with([
            'pesertas' => fn ($q) => $q->select('peserta.id_peserta', 'id_kegiatan', 'nama_lengkap', 'nip', 'nama_instansi', 'kab_kota', 'tanda_tangan')
                ->orderBy('nama_lengkap'),
        ])->withCount('anggotas')->find($id_kelas);

        if (!$kelas) {
            return response()->json(['success' => false, 'message' => 'Kelas tidak ditemukan'], 404);
        }

        return response()->json(['success' => true, 'data' => $kelas]);
    }

    public function update(Request $request, $id_kelas)
    {
        $kelas = Kelas::find($id_kelas);

        if (!$kelas) {
            return response()->json(['success' => false, 'message' => 'Kelas tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'nama_kelas' => 'sometimes|string|max:255',
            'deskripsi' => 'nullable|string|max:1000',
        ]);

        $kelas->update($validated);

        return response()->json(['success' => true, 'data' => $kelas]);
    }

    public function destroy($id_kelas)
    {
        $kelas = Kelas::find($id_kelas);

        if (!$kelas) {
            return response()->json(['success' => false, 'message' => 'Kelas tidak ditemukan'], 404);
        }

        $kelas->delete();

        return response()->json(['success' => true, 'message' => 'Kelas berhasil dihapus']);
    }

    public function addAnggota(Request $request, $id_kelas)
    {
        $kelas = Kelas::find($id_kelas);

        if (!$kelas) {
            return response()->json(['success' => false, 'message' => 'Kelas tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'id_peserta' => 'required|exists:peserta,id_peserta',
        ]);

        $exists = KelasAnggota::where('id_kelas', $id_kelas)
            ->where('id_peserta', $validated['id_peserta'])
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Peserta sudah ada di kelas ini'], 422);
        }

        KelasAnggota::create([
            'id_kelas' => $id_kelas,
            'id_peserta' => $validated['id_peserta'],
        ]);

        $kelas->loadCount('anggotas');

        return response()->json(['success' => true, 'data' => $kelas], 201);
    }

    public function removeAnggota($id_kelas, $id_peserta)
    {
        $deleted = KelasAnggota::where('id_kelas', $id_kelas)
            ->where('id_peserta', $id_peserta)
            ->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Anggota tidak ditemukan'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Anggota berhasil dihapus']);
    }
}
