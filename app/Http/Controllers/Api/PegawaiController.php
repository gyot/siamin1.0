<?php

namespace App\Http\Controllers\Api;

use App\Models\Pegawai;

class PegawaiController extends BaseApiController
{
    protected $modelClass = Pegawai::class;

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
