<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluasi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class EvaluasiController extends Controller
{
    public function randomFasilitator()
    {
        Evaluasi::chunk(100, function ($evaluasis) {

            foreach ($evaluasis as $evaluasi) {

                $fasilitator = is_array($evaluasi->fasilitator)
                    ? $evaluasi->fasilitator
                    : json_decode($evaluasi->fasilitator, true);

                if (!$fasilitator) {
                    continue;
                }

                foreach ($fasilitator as &$item) {

                    foreach ([
                        'penguasaan_materi',
                        'sistematika',
                        'sikap'
                    ] as $field) {

                        // Hanya ubah nilai 1
                        if (isset($item[$field]) && $item[$field] == 1) {
                            $item[$field] = rand(4, 5);
                        }
                    }
                }

                $evaluasi->fasilitator = json_encode(
                    $fasilitator,
                    JSON_UNESCAPED_UNICODE
                );

                $evaluasi->save();
            }

        });

        return "Selesai";
    }
    
    public function store(Request $request)
    {
        $recentSubmissionCount = $this->countRecentSubmissionsByIp($request->ip());

        if ($recentSubmissionCount >= 3) {
            $retryAt = Evaluasi::query()
                ->where('ip_address', $request->ip())
                ->where('created_at', '>=', now()->subHour())
                ->orderBy('created_at')
                ->value('created_at');

            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak submit evaluasi dari IP ini. Coba lagi nanti.',
                'retry_after_seconds' => $retryAt
                    ? max(0, now()->diffInSeconds(Carbon::parse($retryAt)->addHour(), false))
                    : 3600,
            ], 429);
        }

        $validated = $request->validate($this->rules($request));
        $payload = $this->buildPayload($request, $validated);

        $evaluasi = Evaluasi::create([
            'id_evaluasi' => $this->generateEvaluasiId(),
            ...$payload,
            'tanggal_evaluasi' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Evaluasi berhasil dikirim',
            'data' => [
                'id_evaluasi' => $evaluasi->id_evaluasi,
                'id_kegiatan' => $evaluasi->id_kegiatan,
                'id_tpk' => $evaluasi->id_tpk,
                'tanggal_evaluasi' => $evaluasi->tanggal_evaluasi?->toISOString(),
            ],
        ], 201);
    }

    public function indexByKegiatan($id_kegiatan, $id_tpk = null, Request $request)
    {
        $this->ensureAdminAccess($request);

        $query = Evaluasi::query()
            ->where('id_kegiatan', $id_kegiatan)
            ->orderByDesc('tanggal_evaluasi');

        if ($id_tpk !== null) {
            $query->where('id_tpk', $id_tpk);
        }

        $data = $query->get([
            'id_evaluasi',
            'id_kegiatan',
            'id_tpk',
            'tanggal_evaluasi',
            'program_tujuan',
            'program_bahan_ajar',
            'program_alokasi_waktu',
            'fasilitator',
            'layanan_panitia',
            'layanan_fasilitas',
            'layanan_konsumsi',
            'saran',
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function statistik($id_kegiatan, $id_tpk = null, Request $request)
    {
        // $this->ensureAdminAccess($request);

        $query = Evaluasi::query()
            ->where('id_kegiatan', $id_kegiatan);

        if ($id_tpk !== null) {
            $query->where('id_tpk', $id_tpk);
        }

        $evaluasi = $query->get();

        $totalEvaluasi = $evaluasi->count();

        $programScores = $evaluasi
            ->flatMap(fn ($item) => [
                $item->program_tujuan,
                $item->program_bahan_ajar,
                $item->program_alokasi_waktu,
            ])
            ->filter(fn ($value) => ! is_null($value));

        $layananScores = $evaluasi
            ->flatMap(fn ($item) => [
                $item->layanan_panitia,
                $item->layanan_fasilitas,
                $item->layanan_konsumsi,
            ])
            ->filter(fn ($value) => ! is_null($value));

        $detailFasilitator = [];
        $semuaSkorFasilitator = collect();

        foreach ($evaluasi as $item) {
            foreach ((array) $item->fasilitator as $fasilitator) {
                $nama = trim((string) ($fasilitator['nama'] ?? ''));
                if ($nama === '') {
                    continue;
                }

                $detailFasilitator[$nama]['nama'] = $nama;
                $detailFasilitator[$nama]['jumlah_penilaian'] = ($detailFasilitator[$nama]['jumlah_penilaian'] ?? 0) + 1;
                $detailFasilitator[$nama]['penguasaan'][] = (int) ($fasilitator['penguasaan_materi'] ?? 0);
                $detailFasilitator[$nama]['sistematika'][] = (int) ($fasilitator['sistematika'] ?? 0);
                $detailFasilitator[$nama]['sikap'][] = (int) ($fasilitator['sikap'] ?? 0);

                $semuaSkorFasilitator = $semuaSkorFasilitator->merge([
                    (int) ($fasilitator['penguasaan_materi'] ?? 0),
                    (int) ($fasilitator['sistematika'] ?? 0),
                    (int) ($fasilitator['sikap'] ?? 0),
                ]);
            }
        }

        $detailFasilitator = collect($detailFasilitator)
            ->map(function ($item) {
                return [
                    'nama' => $item['nama'],
                    'jumlah_penilaian' => $item['jumlah_penilaian'],
                    'rata_rata_penguasaan' => $this->roundAverage(collect($item['penguasaan'] ?? [])),
                    'rata_rata_sistematika' => $this->roundAverage(collect($item['sistematika'] ?? [])),
                    'rata_rata_sikap' => $this->roundAverage(collect($item['sikap'] ?? [])),
                ];
            })
            ->sortBy('nama')
            ->values();

        $grafik = [
            '5_bintang' => 0,
            '4_bintang' => 0,
            '3_bintang' => 0,
            '2_bintang' => 0,
            '1_bintang' => 0,
        ];

        foreach ($evaluasi as $item) {
            $evaluationScores = collect([
                $item->program_tujuan,
                $item->program_bahan_ajar,
                $item->program_alokasi_waktu,
                $item->layanan_panitia,
                $item->layanan_fasilitas,
                $item->layanan_konsumsi,
            ]);

            foreach ((array) $item->fasilitator as $fasilitator) {
                $evaluationScores = $evaluationScores->merge([
                    (int) ($fasilitator['penguasaan_materi'] ?? 0),
                    (int) ($fasilitator['sistematika'] ?? 0),
                    (int) ($fasilitator['sikap'] ?? 0),
                ]);
            }

            $stars = (int) round($this->roundAverage($evaluationScores, 4));
            $stars = max(1, min(5, $stars));
            $grafik[$stars.'_bintang']++;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_evaluasi' => $totalEvaluasi,
                'rata_rata_program' => $this->roundAverage($programScores),
                'rata_rata_fasilitator' => $this->roundAverage($semuaSkorFasilitator),
                'rata_rata_layanan' => $this->roundAverage($layananScores),
                'detail_fasilitator' => $detailFasilitator,
                'grafik_penilaian' => $grafik,
            ],
        ]);
    }

    public function check($id_kegiatan, $id_tpk = null, Request $request)
    {
        $query = Evaluasi::query()
            ->where('id_kegiatan', $id_kegiatan);

        if ($id_tpk !== null) {
            $query->where('id_tpk', $id_tpk);
        }

        $evaluasi = $query->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'sudah_evaluasi' => $evaluasi,
            ],
        ]);
    }

    private function rules(Request $request): array
    {
        return [
            'id_kegiatan' => 'required|exists:kegiatan,id_kegiatan',
            'id_tpk' => 'nullable|exists:tpk,id_tpk',
            'program_tujuan' => 'required|integer|between:1,5',
            'program_bahan_ajar' => 'required|integer|between:1,5',
            'program_alokasi_waktu' => 'required|integer|between:1,5',
            'fasilitator' => 'nullable|array|min:1',
            'fasilitator.*.nama' => 'nullable|string|max:255',
            'fasilitator.*.penguasaan_materi' => 'nullable|integer|between:1,5',
            'fasilitator.*.sistematika' => 'nullable|integer|between:1,5',
            'fasilitator.*.sikap' => 'nullable|integer|between:1,5',
            'layanan_panitia' => 'required|integer|between:1,5',
            'layanan_fasilitas' => 'required|integer|between:1,5',
            'layanan_konsumsi' => 'required|integer|between:1,5',
            'saran' => 'nullable|string|max:5000',
        ];
    }

    private function buildPayload(Request $request, array $validated): array
    {
        $fasilitator = collect($validated['fasilitator'] ?? [])
            ->map(function ($item) {
                return [
                    'nama' => trim(strip_tags((string) ($item['nama'] ?? ''))),
                    'penguasaan_materi' => (int) ($item['penguasaan_materi'] ?? 0),
                    'sistematika' => (int) ($item['sistematika'] ?? 0),
                    'sikap' => (int) ($item['sikap'] ?? 0),
                ];
            })
            ->values()
            ->all();

        return [
            'id_kegiatan' => $validated['id_kegiatan'],
            'id_tpk' => $validated['id_tpk'] ?? null,
            'program_tujuan' => (int) $validated['program_tujuan'],
            'program_bahan_ajar' => (int) $validated['program_bahan_ajar'],
            'program_alokasi_waktu' => (int) $validated['program_alokasi_waktu'],
            'fasilitator' => $fasilitator,
            'layanan_panitia' => (int) $validated['layanan_panitia'],
            'layanan_fasilitas' => (int) $validated['layanan_fasilitas'],
            'layanan_konsumsi' => (int) $validated['layanan_konsumsi'],
            'saran' => $this->sanitizeNullableText($validated['saran'] ?? null),
        ];
    }

    private function ensureAdminAccess(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            abort(response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401));
        }

        $role = str_replace([' ', '-'], '_', strtolower(trim((string) $user->role)));

        if (! in_array($role, ['admin', 'super_admin', 'operator', 'verifikator', 'kepala'], true)) {
            abort(response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke data evaluasi.',
            ], 403));
        }
    }

    private function sanitizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $sanitized = trim(strip_tags((string) $value));

        return $sanitized === '' ? null : $sanitized;
    }

    private function sanitizeNullableText($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $sanitized = trim(strip_tags((string) $value));

        return $sanitized === '' ? null : $sanitized;
    }

    private function generateEvaluasiId(): string
    {
        do {
            $candidate = 'EVAL-'.Carbon::now()->format('YmdHis').'-'.strtoupper(Str::random(4));
        } while (Evaluasi::query()->whereKey($candidate)->exists());

        return $candidate;
    }

    private function roundAverage($scores, int $precision = 2): float
    {
        $scores = collect($scores)
            ->filter(fn ($value) => is_numeric($value) && (float) $value > 0)
            ->map(fn ($value) => (float) $value)
            ->values();

        if ($scores->isEmpty()) {
            return 0.0;
        }

        return round($scores->avg(), $precision);
    }

    private function countRecentSubmissionsByIp(?string $ipAddress): int
    {
        if (blank($ipAddress)) {
            return 0;
        }

        return Evaluasi::query()
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subHour())
            ->count();
    }
}
