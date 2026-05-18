<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KeanggotaanTim;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    /**
     * Login user and return token
     *
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * Get authenticated user profile
     *
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * Logout user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout berhasil',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 500);
        }
    }

    private function attemptLogin(Request $request, array $roles)
    {
        $startedAt = microtime(true);
        $stage = 'validate';
        $identity = null;
        $field = 'email';

        try {
            $request->validate([
                'email' => 'required|string',
                'password' => 'required|string',
            ]);

            $identity = $request->email;
            $field = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'user';
            $allowedRoles = collect($roles)
                ->map(fn ($role) => $this->normalizeRole($role))
                ->unique()
                ->values();

            $stage = 'query_user';

            $user = User::with('pegawai')
                ->where($field, $identity)
                ->where('status', 'aktif')
                ->first();

            $stage = 'verify_password';

            if (
                ! $user
                || ! $allowedRoles->contains($this->normalizeRole($user->role))
                || ! Hash::check($request->password, $user->password)
            ) {
                throw ValidationException::withMessages([
                    'email' => ['Email/username atau password tidak valid.'],
                ]);
            }

            $stage = 'resolve_unit_kerja';

            // $user->update(['last_login' => now()]);
            $unitKerja = $this->getUnitKerjaByPegawai($user->id_pegawai, $this->getRawUnitKerjaId($user));

            $stage = 'create_token';

            $response = response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'data' => [
                    'user' => $user,
                    'unit_kerja_id' => $unitKerja->pluck('unit_kerja_id')->unique()->values(),
                    'unit_kerja' => $unitKerja->values(),
                    'token' => $user->createToken('siamin-api')->plainTextToken,
                    'token_type' => 'Bearer',
                ],
            ]);

            $this->logSlowLoginIfNeeded($startedAt, [
                'stage' => 'completed',
                'identity' => $identity,
                'field' => $field,
                'user_id' => $user->id_user,
            ]);

            return $response;
        } catch (Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            if ($durationMs >= 2000) {
                Log::warning('Login request failed after a slow execution.', [
                    'stage' => $stage,
                    'field' => $field,
                    'identity' => $identity,
                    'duration_ms' => $durationMs,
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    public function loginAdmin(Request $request)
    {
        return $this->attemptLogin($request, ['admin', 'super_admin', 'operator', 'verifikator', 'kepala']);
    }

    public function loginPeserta(Request $request)
    {
        return $this->attemptLogin($request, ['peserta']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('pegawai');
        $unitKerja = $this->getUnitKerjaByPegawai($user->id_pegawai, $this->getRawUnitKerjaId($user));

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id_user' => $user->id_user,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'last_login' => $user->last_login,
                    'created_at' => $user->created_at,
                    'unit_kerja_id' => $unitKerja->pluck('unit_kerja_id')->unique()->values(),
                    'unit_kerja' => $unitKerja->values(),
                ],
                'pegawai' => $user->pegawai ? [
                    'id_pegawai' => $user->pegawai->id_pegawai,
                    'nip' => $user->pegawai->nip,
                    'nama' => $user->pegawai->nama,
                    'tempat_lahir' => $user->pegawai->tempat_lahir,
                    'tanggal_lahir' => $user->pegawai->tanggal_lahir,
                    'nama_jabatan' => $user->pegawai->nama_jabatan,
                    'golongan' => $user->pegawai->golongan,
                    'pangkat' => $user->pegawai->pangkat,
                    'tmt_cpns' => $user->pegawai->tmt_cpns,
                    'pendidikan_terakhir' => $user->pegawai->pendidikan_terakhir,
                    'status_kepegawaian' => $user->pegawai->status_kepegawaian,
                ] : null,
                'unit_kerja' => $unitKerja->values(),
            ],
        ]);
    }

    private function getUnitKerjaByPegawai($pegawaiId, $rawUnitKerjaId = null)
    {
        $idsFromTim = collect();

        if ($pegawaiId) {
            $idsFromTim = KeanggotaanTim::where('id_pegawai', $pegawaiId)
                ->pluck('unit_kerja_id')
                ->filter()
                ->map(fn ($v) => (string) $v);
        }

        $idsFromUser = $this->normalizeUnitKerjaIds($rawUnitKerjaId);
        $allIds = $idsFromTim->merge($idsFromUser)->unique()->values();

        if ($allIds->isEmpty()) {
            return collect();
        }

        $numericIds = $allIds
            ->filter(fn ($v) => preg_match('/^\d+$/', (string) $v))
            ->map(fn ($v) => (int) $v)
            ->values();

        $units = UnitKerja::query()
            ->whereIn('kode_unit', $allIds->all())
            ->orWhereIn('id', $numericIds->all())
            ->get();

        return $allIds->map(function ($rawId) use ($units) {
            $numericId = preg_match('/^\d+$/', (string) $rawId) ? (int) $rawId : null;

            $unit = $units->first(function ($item) use ($rawId, $numericId) {
                return $item->kode_unit === (string) $rawId
                    || ($numericId !== null && (int) $item->id === $numericId);
            });

            return [
                'unit_kerja_id' => (string) $rawId,
                'nama_unit' => $unit?->nama_unit ?? null,
                'kode_unit' => $unit?->kode_unit ?? (string) $rawId,
            ];
        })->values();
    }

    private function normalizeUnitKerjaIds($rawUnitKerjaId)
    {
        if (is_null($rawUnitKerjaId)) {
            return collect();
        }

        if (is_array($rawUnitKerjaId)) {
            return collect($rawUnitKerjaId)
                ->map(fn ($v) => trim((string) $v))
                ->filter();
        }

        $raw = trim((string) $rawUnitKerjaId);
        if ($raw === '') {
            return collect();
        }

        if (str_starts_with($raw, '[') && str_ends_with($raw, ']')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return collect($decoded)
                    ->map(fn ($v) => trim((string) $v))
                    ->filter();
            }
        }

        return collect(explode(',', $raw))
            ->map(fn ($v) => trim((string) $v))
            ->filter();
    }

    private function normalizeRole(?string $role): string
    {
        return str_replace([' ', '-'], '_', strtolower(trim((string) $role)));
    }

    private function getRawUnitKerjaId(User $user)
    {
        if (array_key_exists('unit_kerja_id', $user->getAttributes())) {
            return $user->getAttributes()['unit_kerja_id'];
        }

        static $hasUnitKerjaColumn;

        if ($hasUnitKerjaColumn === null) {
            $hasUnitKerjaColumn = DB::getSchemaBuilder()->hasColumn($user->getTable(), 'unit_kerja_id');
        }

        if (! $hasUnitKerjaColumn) {
            return null;
        }

        return User::query()
            ->whereKey($user->getKey())
            ->value('unit_kerja_id');
    }

    private function logSlowLoginIfNeeded(float $startedAt, array $context = []): void
    {
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        if ($durationMs < 2000) {
            return;
        }

        Log::warning('Slow login request detected.', array_merge($context, [
            'duration_ms' => $durationMs,
        ]));
    }

    // public function me(Request $request)
    // {
    //     $user = $request->user()->load([
    //         'pegawai.keanggotaanTim.unitKerja',
    //     ]);

    //     if (! $user) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'User tidak ditemukan',
    //         ], 401);
    //     }

    //     $pegawai = $user->pegawai;

    //     $unitKerja = [];

    //     if ($pegawai && $pegawai->keanggotaanTim) {
    //         $unitKerja = $pegawai->keanggotaanTim
    //             ->filter(fn ($kt) => $kt->unitKerja)
    //             ->map(function ($kt) {
    //                 return [
    //                     'id_unit' => $kt->unitKerja->id,
    //                     'nama_unit' => $kt->unitKerja->nama_unit,
    //                 ];
    //             })
    //             ->values();
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data' => [
    //             'user' => [
    //                 'id_user' => $user->id_user,
    //                 'email' => $user->email,
    //                 'role' => $user->role,
    //                 'status' => $user->status,
    //                 'created_at' => $user->created_at,
    //             ],
    //             'pegawai' => $pegawai ? [
    //                 'id_pegawai' => $pegawai->id_pegawai,
    //                 'nip' => $pegawai->nip,
    //                 'nama' => $pegawai->nama,
    //             ] : null,
    //             'unit_kerja' => $unitKerja,
    //         ],
    //     ]);
    // }
}
