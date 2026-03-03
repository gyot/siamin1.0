<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KeanggotaanTim;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $identity = $request->email;
        $field = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'user';

        $user = User::with('pegawai')
            ->where($field, $identity)
            ->whereIn('role', $roles)
            ->where('status', 'aktif')
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email/username atau password tidak valid.'],
            ]);
        }

        // $user->update(['last_login' => now()]);
        $unitKerja = $this->getUnitKerjaByPegawai($user->id_pegawai, $user->unit_kerja_id ?? null);

        return response()->json([
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
    }

    public function loginAdmin(Request $request)
    {
        return $this->attemptLogin($request, ['admin', 'operator', 'verifikator', 'kepala']);
    }

    public function loginPeserta(Request $request)
    {
        return $this->attemptLogin($request, ['peserta']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('pegawai');
        $unitKerja = $this->getUnitKerjaByPegawai($user->id_pegawai, $user->unit_kerja_id ?? null);

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
