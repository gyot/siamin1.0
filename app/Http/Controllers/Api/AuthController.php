<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AkunPeserta;
use App\Models\KeanggotaanTim;
use App\Models\Peserta;
use App\Models\ProfilPeserta;
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

    public function loginUnified(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $identity = $request->email;
        $password = $request->password;
        $field = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'user';

        // 1. Cek di tabel users (admin)
        $user = User::with('pegawai')
            ->where($field, $identity)
            ->where('status', 'aktif')
            ->first();

        if ($user && Hash::check($password, $user->password)) {
            $unitKerja = $this->getUnitKerjaByPegawai($user->id_pegawai, $this->getRawUnitKerjaId($user));

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'data' => [
                    'login_as' => 'admin',
                    'user' => $user,
                    'unit_kerja_id' => $unitKerja->pluck('unit_kerja_id')->unique()->values(),
                    'unit_kerja' => $unitKerja->values(),
                    'token' => $user->createToken('siamin-api')->plainTextToken,
                    'token_type' => 'Bearer',
                ],
            ]);
        }

        // 2. Cek di tabel akun_peserta
        $akun = AkunPeserta::with('profil')
            ->where('username', $identity)
            ->where('status', 'aktif')
            ->first();

        if ($akun && Hash::check($password, $akun->password)) {
            $akun->update(['last_login' => now()]);
            $profil = $akun->profil;

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'data' => [
                    'login_as' => 'peserta',
                    'user' => [
                        'id' => $akun->id_akun_peserta,
                        'username' => $akun->username,
                        'role' => 'peserta',
                        'nama' => $profil->nama_lengkap ?? $akun->username,
                        'id_profil' => $akun->id_profil,
                        'profil' => $profil,
                    ],
                    'token' => 'dummy-peserta-' . $akun->id_akun_peserta,
                    'token_type' => 'Bearer',
                ],
            ]);
        }

        // 3. Tidak ditemukan
        throw ValidationException::withMessages([
            'email' => ['Username atau password tidak valid.'],
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:akun_peserta,username',
            'password' => 'required|string|min:6|confirmed',
            'nip' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'no_hp' => 'nullable|string|max:20',
            'jenis_kelamin' => 'nullable|in:laki-laki,perempuan',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'npwp_nik' => 'nullable|string|max:30',
            'pangkat' => 'nullable|string|max:100',
            'jabatan' => 'nullable|string|max:255',
            'nama_instansi' => 'nullable|string|max:255',
            'npsn' => 'nullable|string|max:8',
            'kab_kota' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            $profil = ProfilPeserta::create([
                'nama_lengkap' => $request->nama_lengkap,
                'nip' => $request->nip,
                'email' => $request->email,
                'no_hp' => $request->no_hp,
                'jenis_kelamin' => $request->jenis_kelamin,
                'tempat_lahir' => $request->tempat_lahir,
                'tanggal_lahir' => $request->tanggal_lahir,
                'npwp_nik' => $request->npwp_nik,
                'pangkat' => $request->pangkat,
                'jabatan' => $request->jabatan,
                'nama_instansi' => $request->nama_instansi,
                'npsn' => $request->npsn,
                'kab_kota' => $request->kab_kota,
            ]);

            $akun = AkunPeserta::create([
                'id_profil' => $profil->id_profil,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'status' => 'aktif',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil. Silakan login.',
                'data' => [
                    'id_akun_peserta' => $akun->id_akun_peserta,
                    'username' => $akun->username,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal registrasi: ' . $e->getMessage(),
            ], 500);
        }
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
