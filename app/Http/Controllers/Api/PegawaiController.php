<?php

namespace App\Http\Controllers\Api;

use App\Models\Pegawai;
use Illuminate\Http\Request;

class PegawaiController extends BaseApiController
{
    protected $modelClass = Pegawai::class;

    /**
     * Tampilan pegawai (read-only, sumber data dari aplikasi KANDI).
     * View menghasilkan 1 baris per kombinasi tim x role x PDM —
     * digabung jadi 1 baris per pegawai dengan keterangan lengkap.
     */
    public function index(Request $request)
    {
        $rows = Pegawai::query()->get();

        $data = $rows->groupBy('id_pegawai')->map(function ($g) {
            $base = $g->first()->toArray();
            $base['roles_detail'] = $g->pluck('role_name')->filter()->unique()->implode(', ');
            $base['tim_detail'] = $g->pluck('nama_tim')->filter()->unique()->implode(', ');
            $base['pdm_detail'] = $g->pluck('nama_pdm')->filter()->unique()->implode(', ');
            $base['jabatan_pdm_detail'] = $g->pluck('jabatan_pdm')->filter()->unique()->implode(', ');
            return $base;
        })->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Validation rules used for store and update.
     * For update, unique rules will be adjusted by BaseApiController::validateIfNeeded
     * to ignore the current model id when applicable.
     *
     * @var array
     */
    protected $rules = [
        'nip' => 'required|string|max:30|unique:pegawai,nip',
        'nama' => 'required|string|max:255',
        'tempat_lahir' => 'nullable|string|max:255',
        'tanggal_lahir' => 'nullable|date',
        'tmt_cpns' => 'nullable|date',
        'tmt_pangkat' => 'nullable|date',
        'pangkat' => 'nullable|string|max:100',
        'golongan' => 'nullable|string|max:50',
        'nama_jabatan' => 'nullable|string|max:255',
        'tmt_jabatan' => 'nullable|date',
        'pendidikan_terakhir' => 'nullable|string|max:255',
        'jurusan' => 'nullable|string|max:255',
        'tempat_pendidikan' => 'nullable|string|max:255',
        'tahun_lulus' => 'nullable|integer',
        'latihan_jabatan' => 'nullable|string',
        'status_kepegawaian' => 'nullable|string|max:100',
        'status' => 'nullable|in:aktif,nonaktif',
    ];
}
