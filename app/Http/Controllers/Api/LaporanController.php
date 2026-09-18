<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaporanDraft;
use App\Models\JadwalHarian;
use App\Models\Kelas;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function getDraft($id_kegiatan)
    {
        $draft = LaporanDraft::where('id_kegiatan', $id_kegiatan)->first();

        if (!$draft) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $draft->data ?? [],
            'updated_at' => $draft->updated_at,
        ]);
    }

    public function saveDraft(Request $request, $id_kegiatan)
    {
        $request->validate([
            'data' => 'required|array',
        ]);

        $draft = LaporanDraft::updateOrCreate(
            ['id_kegiatan' => $id_kegiatan],
            ['data' => $request->input('data')]
        );

        return response()->json([
            'success' => true,
            'data' => $draft->data,
            'updated_at' => $draft->updated_at,
        ]);
    }

    public function getJadwal($id_kegiatan)
    {
        $jadwal = JadwalHarian::where('id_kegiatan', $id_kegiatan)
            ->orderBy('urutan')
            ->orderBy('tanggal')
            ->get();

        $kelas = Kelas::where('id_kegiatan', $id_kegiatan)
            ->orderBy('nama_kelas')
            ->get();

        $jadwalPerKelas = [];

        if ($kelas->count() > 0) {
            foreach ($kelas as $k) {
                $jadwalKelas = $jadwal
                    ->where('id_kelas', $k->id_kelas)
                    ->values()
                    ->map(function ($j) {
                        return [
                            'hari' => $j->hari,
                            'tanggal' => $j->tanggal->format('Y-m-d'),
                            'items' => $j->items ?? [],
                        ];
                    });

                $jadwalPerKelas[] = [
                    'id_kelas' => $k->id_kelas,
                    'nama_kelas' => $k->nama_kelas,
                    'jadwal' => $jadwalKelas,
                ];
            }
        }

        $jadwalTanpaKelas = $jadwal
            ->whereNull('id_kelas')
            ->values()
            ->map(function ($j) {
                return [
                    'hari' => $j->hari,
                    'tanggal' => $j->tanggal->format('Y-m-d'),
                    'items' => $j->items ?? [],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'jadwal_per_kelas' => $jadwalPerKelas,
                'jadwal_harian' => $jadwalTanpaKelas,
                'kelas' => $kelas,
            ],
        ]);
    }

    public function saveJadwal(Request $request, $id_kegiatan)
    {
        $request->validate([
            'jadwal' => 'required|array',
            'jadwal.*.hari' => 'required|string|max:50',
            'jadwal.*.tanggal' => 'required|date',
            'jadwal.*.items' => 'nullable|array',
            'jadwal.*.id_kelas' => 'nullable|integer|exists:kelas,id_kelas',
            'jadwal.*.urutan' => 'nullable|integer',
        ]);

        JadwalHarian::where('id_kegiatan', $id_kegiatan)->delete();

        $created = [];
        foreach ($request->input('jadwal') as $index => $item) {
            $created[] = JadwalHarian::create([
                'id_kegiatan' => $id_kegiatan,
                'id_kelas' => $item['id_kelas'] ?? null,
                'hari' => $item['hari'],
                'tanggal' => $item['tanggal'],
                'items' => $item['items'] ?? [],
                'urutan' => $item['urutan'] ?? $index,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $created,
            'message' => 'Jadwal berhasil disimpan',
        ]);
    }
}
