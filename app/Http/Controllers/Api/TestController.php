<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JawabanPeserta;
use App\Models\Kegiatan;
use App\Models\PaketSoal;
use App\Models\Peserta;
use App\Models\Soal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TestController extends Controller
{
    // ========================
    // PUBLIC: Peserta & Test
    // ========================

    public function pesertaByKegiatan(Request $request, $id_kegiatan)
    {
        $query = Peserta::query()
            ->where('id_kegiatan', $id_kegiatan)
            ->select('id_peserta', 'id_tpk', 'nama_lengkap', 'nip', 'pangkat', 'gol', 'jabatan', 'nama_instansi', 'kab_kota', 'provinsi');

        if ($request->filled('id_tpk')) {
            $query->where('id_tpk', $request->input('id_tpk'));
        }

        $peserta = $query->orderBy('nama_lengkap')->get();

        return response()->json(['success' => true, 'data' => $peserta]);
    }

    public function pesertaDetail($id_peserta)
    {
        $peserta = Peserta::with(['kegiatan:id_kegiatan,nama_kegiatan', 'tpk:id_tpk,lokasi,kabupaten_kota'])
            ->select('id_peserta', 'id_kegiatan', 'id_tpk', 'nama_lengkap', 'nip', 'pangkat', 'gol', 'jabatan', 'no_hp', 'email', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'nama_instansi', 'alamat_instansi', 'kab_kota', 'provinsi')
            ->find($id_peserta);

        if (!$peserta) {
            return response()->json(['success' => false, 'message' => 'Peserta tidak ditemukan'], 404);
        }

        return response()->json(['success' => true, 'data' => $peserta]);
    }

    public function paketByKegiatan($id_kegiatan)
    {
        $paket = PaketSoal::where('id_kegiatan', $id_kegiatan)
            ->where('is_active', true)
            ->withCount('soals')
            ->orderBy('nama_paket')
            ->get();

        return response()->json(['success' => true, 'data' => $paket]);
    }

    public function soalByPaket($id_paket_soal)
    {
        $paket = PaketSoal::with([
            'soals' => fn ($q) => $q->orderBy('urutan')->select('id_soal', 'id_paket_soal', 'pertanyaan', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d', 'jawaban_benar', 'urutan'),
        ])->find($id_paket_soal);

        if (!$paket) {
            return response()->json(['success' => false, 'message' => 'Paket soal tidak ditemukan'], 404);
        }

        return response()->json(['success' => true, 'data' => $paket]);
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'id_peserta' => 'required|exists:peserta,id_peserta',
            'id_paket_soal' => 'required|exists:paket_soal,id_paket_soal',
            'jawaban' => 'required|array|min:1',
            'jawaban.*.id_soal' => 'required|exists:soal,id_soal',
            'jawaban.*.jawaban' => 'required|in:a,b,c,d',
        ]);

        $existing = JawabanPeserta::where('id_peserta', $validated['id_peserta'])
            ->where('id_paket_soal', $validated['id_paket_soal'])
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta sudah mengerjakan paket soal ini.',
            ], 422);
        }

        $soalIds = collect($validated['jawaban'])->pluck('id_soal');
        $soalMap = Soal::whereIn('id_soal', $soalIds)
            ->pluck('jawaban_benar', 'id_soal');

        $records = [];
        $benar = 0;
        $salah = 0;

        foreach ($validated['jawaban'] as $item) {
            $correct = $soalMap[$item['id_soal']] === $item['jawaban'];
            if ($correct) {
                $benar++;
            } else {
                $salah++;
            }

            $records[] = [
                'id_peserta' => $validated['id_peserta'],
                'id_paket_soal' => $validated['id_paket_soal'],
                'id_soal' => $item['id_soal'],
                'jawaban' => $item['jawaban'],
                'is_correct' => $correct,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        JawabanPeserta::insert($records);

        $totalSoal = $benar + $salah;
        $skor = $totalSoal > 0 ? round(($benar / $totalSoal) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'message' => 'Jawaban berhasil disimpan',
            'data' => [
                'total_soal' => $totalSoal,
                'jawaban_benar' => $benar,
                'jawaban_salah' => $salah,
                'skor' => $skor,
            ],
        ], 201);
    }

    public function hasil($id_peserta, $id_paket_soal)
    {
        $jawaban = JawabanPeserta::with([
            'soal' => fn ($q) => $q->select('id_soal', 'pertanyaan', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d', 'jawaban_benar'),
        ])
            ->where('id_peserta', $id_peserta)
            ->where('id_paket_soal', $id_paket_soal)
            ->orderBy('id_soal')
            ->get();

        if ($jawaban->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data jawaban tidak ditemukan'], 404);
        }

        $benar = $jawaban->where('is_correct', true)->count();
        $salah = $jawaban->where('is_correct', false)->count();
        $totalSoal = $benar + $salah;
        $skor = $totalSoal > 0 ? round(($benar / $totalSoal) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'ringkasan' => [
                    'total_soal' => $totalSoal,
                    'jawaban_benar' => $benar,
                    'jawaban_salah' => $salah,
                    'skor' => $skor,
                ],
                'detail' => $jawaban->map(fn ($j) => [
                    'id_soal' => $j->id_soal,
                    'pertanyaan' => $j->soal->pertanyaan,
                    'pilihan_a' => $j->soal->pilihan_a,
                    'pilihan_b' => $j->soal->pilihan_b,
                    'pilihan_c' => $j->soal->pilihan_c,
                    'pilihan_d' => $j->soal->pilihan_d,
                    'jawaban_benar' => $j->soal->jawaban_benar,
                    'jawaban_peserta' => $j->jawaban,
                    'is_correct' => $j->is_correct,
                ]),
            ],
        ]);
    }

    // ========================
    // ADMIN: Laporan
    // ========================

    public function laporanByKegiatan(Request $request, $id_kegiatan)
    {
        $this->ensureAdminAccess($request);

        $query = JawabanPeserta::query()
            ->whereHas('paketSoal', fn ($q) => $q->where('id_kegiatan', $id_kegiatan))
            ->with([
                'peserta' => fn ($q) => $q->select('id_peserta', 'nama_lengkap', 'nip', 'pangkat', 'gol', 'jabatan', 'nama_instansi', 'kab_kota', 'provinsi'),
                'paketSoal' => fn ($q) => $q->select('id_paket_soal', 'nama_paket'),
            ])
            ->orderBy('created_at', 'desc');

        if ($request->filled('id_peserta')) {
            $query->where('id_peserta', $request->input('id_peserta'));
        }

        if ($request->filled('id_paket_soal')) {
            $query->where('id_paket_soal', $request->input('id_paket_soal'));
        }

        $data = $query->get();

        $grouped = $data->groupBy(fn ($item) => $item->id_peserta . '_' . $item->id_paket_soal);

        $results = $grouped->map(function ($items) {
            $first = $items->first();
            $benar = $items->where('is_correct', true)->count();
            $salah = $items->where('is_correct', false)->count();
            $total = $benar + $salah;

            return [
                'id_peserta' => $first->id_peserta,
                'nama_peserta' => $first->peserta->nama_lengkap,
                'nip' => $first->peserta->nip,
                'jabatan' => $first->peserta->jabatan,
                'nama_instansi' => $first->peserta->nama_instansi,
                'id_paket_soal' => $first->id_paket_soal,
                'nama_paket' => $first->paketSoal->nama_paket,
                'total_soal' => $total,
                'jawaban_benar' => $benar,
                'jawaban_salah' => $salah,
                'skor' => $total > 0 ? round(($benar / $total) * 100, 1) : 0,
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $results]);
    }

    public function laporanDetail(Request $request, $id_kegiatan, $id_peserta, $id_paket_soal)
    {
        $this->ensureAdminAccess($request);

        $peserta = Peserta::select('id_peserta', 'id_kegiatan', 'id_tpk', 'nama_lengkap', 'nip', 'pangkat', 'gol', 'jabatan', 'no_hp', 'email', 'nama_instansi', 'kab_kota', 'provinsi')
            ->find($id_peserta);

        if (!$peserta) {
            return response()->json(['success' => false, 'message' => 'Peserta tidak ditemukan'], 404);
        }

        $jawaban = JawabanPeserta::with([
            'soal' => fn ($q) => $q->select('id_soal', 'pertanyaan', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d', 'jawaban_benar'),
        ])
            ->where('id_peserta', $id_peserta)
            ->where('id_paket_soal', $id_paket_soal)
            ->orderBy('id_soal')
            ->get();

        if ($jawaban->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data jawaban tidak ditemukan'], 404);
        }

        $benar = $jawaban->where('is_correct', true)->count();
        $salah = $jawaban->where('is_correct', false)->count();
        $total = $benar + $salah;

        return response()->json([
            'success' => true,
            'data' => [
                'peserta' => $peserta,
                'ringkasan' => [
                    'total_soal' => $total,
                    'jawaban_benar' => $benar,
                    'jawaban_salah' => $salah,
                    'skor' => $total > 0 ? round(($benar / $total) * 100, 1) : 0,
                ],
                'detail' => $jawaban->map(fn ($j) => [
                    'id_soal' => $j->id_soal,
                    'pertanyaan' => $j->soal->pertanyaan,
                    'pilihan_a' => $j->soal->pilihan_a,
                    'pilihan_b' => $j->soal->pilihan_b,
                    'pilihan_c' => $j->soal->pilihan_c,
                    'pilihan_d' => $j->soal->pilihan_d,
                    'jawaban_benar' => $j->soal->jawaban_benar,
                    'jawaban_peserta' => $j->jawaban,
                    'is_correct' => $j->is_correct,
                ]),
            ],
        ]);
    }

    // ========================
    // ADMIN: CRUD Paket Soal
    // ========================

    public function indexPaket(Request $request)
    {
        $this->ensureAdminAccess($request);

        $query = PaketSoal::withCount('soals')
            ->with('kegiatan:id_kegiatan,nama_kegiatan')
            ->orderByDesc('created_at');

        if ($request->filled('id_kegiatan')) {
            $query->where('id_kegiatan', $request->input('id_kegiatan'));
        }

        if ($request->filled('search')) {
            $query->where('nama_paket', 'like', '%' . $request->input('search') . '%');
        }

        $data = $query->get();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function storePaket(Request $request)
    {
        $this->ensureAdminAccess($request);

        $validated = $request->validate([
            'nama_paket' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:1000',
            'id_kegiatan' => 'nullable|exists:kegiatan,id_kegiatan',
            'is_active' => 'boolean',
        ]);

        $paket = PaketSoal::create($validated);

        return response()->json(['success' => true, 'data' => $paket], 201);
    }

    public function showPaket(Request $request, $id_paket_soal)
    {
        $this->ensureAdminAccess($request);

        $paket = PaketSoal::withCount('soals')
            ->with('kegiatan:id_kegiatan,nama_kegiatan')
            ->find($id_paket_soal);

        if (!$paket) {
            return response()->json(['success' => false, 'message' => 'Paket soal tidak ditemukan'], 404);
        }

        return response()->json(['success' => true, 'data' => $paket]);
    }

    public function updatePaket(Request $request, $id_paket_soal)
    {
        $this->ensureAdminAccess($request);

        $paket = PaketSoal::find($id_paket_soal);

        if (!$paket) {
            return response()->json(['success' => false, 'message' => 'Paket soal tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'nama_paket' => 'sometimes|string|max:255',
            'deskripsi' => 'nullable|string|max:1000',
            'id_kegiatan' => 'nullable|exists:kegiatan,id_kegiatan',
            'is_active' => 'boolean',
        ]);

        $paket->update($validated);

        return response()->json(['success' => true, 'data' => $paket]);
    }

    public function destroyPaket(Request $request, $id_paket_soal)
    {
        $this->ensureAdminAccess($request);

        $paket = PaketSoal::find($id_paket_soal);

        if (!$paket) {
            return response()->json(['success' => false, 'message' => 'Paket soal tidak ditemukan'], 404);
        }

        $hasAnswers = JawabanPeserta::where('id_paket_soal', $id_paket_soal)->exists();

        if ($hasAnswers) {
            return response()->json([
                'success' => false,
                'message' => 'Paket soal tidak dapat dihapus karena sudah memiliki jawaban peserta.',
            ], 422);
        }

        $paket->delete();

        return response()->json(['success' => true, 'message' => 'Paket soal berhasil dihapus']);
    }

    // ========================
    // ADMIN: CRUD Soal
    // ========================

    public function indexSoal(Request $request, $id_paket_soal)
    {
        $this->ensureAdminAccess($request);

        $paket = PaketSoal::find($id_paket_soal);

        if (!$paket) {
            return response()->json(['success' => false, 'message' => 'Paket soal tidak ditemukan'], 404);
        }

        $soal = Soal::where('id_paket_soal', $id_paket_soal)
            ->orderBy('urutan')
            ->orderBy('id_soal')
            ->get();

        return response()->json(['success' => true, 'data' => $soal]);
    }

    public function storeSoal(Request $request, $id_paket_soal)
    {
        $this->ensureAdminAccess($request);

        $paket = PaketSoal::find($id_paket_soal);

        if (!$paket) {
            return response()->json(['success' => false, 'message' => 'Paket soal tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'soal' => 'required|array|min:1',
            'soal.*.pertanyaan' => 'required|string',
            'soal.*.pilihan_a' => 'required|string|max:1000',
            'soal.*.pilihan_b' => 'required|string|max:1000',
            'soal.*.pilihan_c' => 'required|string|max:1000',
            'soal.*.pilihan_d' => 'required|string|max:1000',
            'soal.*.jawaban_benar' => 'required|in:a,b,c,d',
            'soal.*.urutan' => 'sometimes|integer|min:0',
        ]);

        $records = [];
        $maxUrutan = Soal::where('id_paket_soal', $id_paket_soal)->max('urutan') ?? 0;

        foreach ($validated['soal'] as $i => $item) {
            $records[] = [
                'id_paket_soal' => $id_paket_soal,
                'pertanyaan' => $item['pertanyaan'],
                'pilihan_a' => $item['pilihan_a'],
                'pilihan_b' => $item['pilihan_b'],
                'pilihan_c' => $item['pilihan_c'],
                'pilihan_d' => $item['pilihan_d'],
                'jawaban_benar' => $item['jawaban_benar'],
                'urutan' => $item['urutan'] ?? ($maxUrutan + $i + 1),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Soal::insert($records);

        $soal = Soal::where('id_paket_soal', $id_paket_soal)
            ->orderBy('urutan')
            ->orderBy('id_soal')
            ->get();

        return response()->json(['success' => true, 'data' => $soal], 201);
    }

    public function updateSoal(Request $request, $id_soal)
    {
        $this->ensureAdminAccess($request);

        $soal = Soal::find($id_soal);

        if (!$soal) {
            return response()->json(['success' => false, 'message' => 'Soal tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'pertanyaan' => 'sometimes|string',
            'pilihan_a' => 'sometimes|string|max:1000',
            'pilihan_b' => 'sometimes|string|max:1000',
            'pilihan_c' => 'sometimes|string|max:1000',
            'pilihan_d' => 'sometimes|string|max:1000',
            'jawaban_benar' => 'sometimes|in:a,b,c,d',
            'urutan' => 'sometimes|integer|min:0',
        ]);

        $soal->update($validated);

        return response()->json(['success' => true, 'data' => $soal]);
    }

    public function destroySoal(Request $request, $id_soal)
    {
        $this->ensureAdminAccess($request);

        $soal = Soal::find($id_soal);

        if (!$soal) {
            return response()->json(['success' => false, 'message' => 'Soal tidak ditemukan'], 404);
        }

        $hasAnswers = JawabanPeserta::where('id_soal', $id_soal)->exists();

        if ($hasAnswers) {
            return response()->json([
                'success' => false,
                'message' => 'Soal tidak dapat dihapus karena sudah memiliki jawaban peserta.',
            ], 422);
        }

        $soal->delete();

        return response()->json(['success' => true, 'message' => 'Soal berhasil dihapus']);
    }

    public function importSoal(Request $request, $id_paket_soal)
    {
        $this->ensureAdminAccess($request);

        $paket = PaketSoal::find($id_paket_soal);

        if (!$paket) {
            return response()->json(['success' => false, 'message' => 'Paket soal tidak ditemukan'], 404);
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (count($rows) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'File kosong atau tidak memiliki data soal.',
                ], 422);
            }

            $header = array_map(fn ($h) => strtolower(trim($h)), $rows[0]);
            $colMap = array_flip($header);

            $requiredCols = ['pertanyaan', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d', 'jawaban_benar'];
            foreach ($requiredCols as $col) {
                if (!isset($colMap[$col])) {
                    return response()->json([
                        'success' => false,
                        'message' => "Kolom '{$col}' tidak ditemukan. Header yang dibutuhkan: " . implode(', ', $requiredCols),
                    ], 422);
                }
            }

            $maxUrutan = Soal::where('id_paket_soal', $id_paket_soal)->max('urutan') ?? 0;
            $records = [];
            $errors = [];

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                if (empty($row[$colMap['pertanyaan']])) {
                    continue;
                }

                $jawabanBenar = strtolower(trim($row[$colMap['jawaban_benar']] ?? ''));

                if (!in_array($jawabanBenar, ['a', 'b', 'c', 'd'])) {
                    $errors[] = "Baris " . ($i + 1) . ": jawaban_benar harus a/b/c/d, dapat '{$jawabanBenar}'";
                    continue;
                }

                $records[] = [
                    'id_paket_soal' => $id_paket_soal,
                    'pertanyaan' => trim($row[$colMap['pertanyaan']]),
                    'pilihan_a' => trim($row[$colMap['pilihan_a']] ?? ''),
                    'pilihan_b' => trim($row[$colMap['pilihan_b']] ?? ''),
                    'pilihan_c' => trim($row[$colMap['pilihan_c']] ?? ''),
                    'pilihan_d' => trim($row[$colMap['pilihan_d']] ?? ''),
                    'jawaban_benar' => $jawabanBenar,
                    'urutan' => isset($colMap['urutan']) ? (int) ($row[$colMap['urutan']] ?? 0) : ++$maxUrutan,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (empty($records)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data soal yang valid di file.',
                    'errors' => $errors,
                ], 422);
            }

            Soal::insert($records);

            $soal = Soal::where('id_paket_soal', $id_paket_soal)
                ->orderBy('urutan')
                ->orderBy('id_soal')
                ->get();

            return response()->json([
                'success' => true,
                'message' => count($records) . ' soal berhasil diimpor.' . ($errors ? ' ' . count($errors) . ' barir dilewati.' : ''),
                'data' => $soal,
                'import_errors' => $errors,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca file: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function replaceSoal(Request $request, $id_paket_soal)
    {
        $this->ensureAdminAccess($request);

        $paket = PaketSoal::find($id_paket_soal);

        if (!$paket) {
            return response()->json(['success' => false, 'message' => 'Paket soal tidak ditemukan'], 404);
        }

        $hasAnswers = JawabanPeserta::where('id_paket_soal', $id_paket_soal)->exists();

        if ($hasAnswers) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat mengganti semua soal karena sudah ada jawaban peserta.',
            ], 422);
        }

        $validated = $request->validate([
            'soal' => 'required|array|min:1',
            'soal.*.pertanyaan' => 'required|string',
            'soal.*.pilihan_a' => 'required|string|max:1000',
            'soal.*.pilihan_b' => 'required|string|max:1000',
            'soal.*.pilihan_c' => 'required|string|max:1000',
            'soal.*.pilihan_d' => 'required|string|max:1000',
            'soal.*.jawaban_benar' => 'required|in:a,b,c,d',
            'soal.*.urutan' => 'sometimes|integer|min:0',
        ]);

        DB::transaction(function () use ($id_paket_soal, $validated) {
            Soal::where('id_paket_soal', $id_paket_soal)->delete();

            $records = [];
            foreach ($validated['soal'] as $i => $item) {
                $records[] = [
                    'id_paket_soal' => $id_paket_soal,
                    'pertanyaan' => $item['pertanyaan'],
                    'pilihan_a' => $item['pilihan_a'],
                    'pilihan_b' => $item['pilihan_b'],
                    'pilihan_c' => $item['pilihan_c'],
                    'pilihan_d' => $item['pilihan_d'],
                    'jawaban_benar' => $item['jawaban_benar'],
                    'urutan' => $item['urutan'] ?? ($i + 1),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Soal::insert($records);
        });

        $soal = Soal::where('id_paket_soal', $id_paket_soal)
            ->orderBy('urutan')
            ->orderBy('id_soal')
            ->get();

        return response()->json(['success' => true, 'data' => $soal]);
    }

    // ========================
    // HELPERS
    // ========================
    // ADMIN: Download Template
    // ========================

    public function downloadTemplate()
    {
        $path = storage_path('app/public/template-import-soal.xlsx');

        if (!file_exists($path)) {
            return response()->json(['success' => false, 'message' => 'Template tidak ditemukan'], 404);
        }

        return response()->download($path, 'template-import-soal.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ========================

    private function ensureAdminAccess(Request $request): void
    {
        $user = $request->user();

        if (!$user) {
            abort(response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401));
        }

        $role = str_replace([' ', '-'], '_', strtolower(trim((string) $user->role)));

        if (!in_array($role, ['admin', 'super_admin', 'operator', 'verifikator', 'kepala'], true)) {
            abort(response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses.'], 403));
        }
    }
}
