<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSertifikatBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Mendukung form edit lama yang masih mengirim POST ke endpoint store.
            // Bila nilai ini dikirim, controller akan memperbarui batch tersebut.
            'id_batch' => ['sometimes', 'integer', 'exists:sertifikat_batch,id_batch'],
            'id_kegiatan' => ['required', 'exists:kegiatan,id_kegiatan'],
            'nomor_sertifikat' => ['required', 'string', 'max:150'],
            'id_penandatangan' => ['nullable', 'exists:pegawai,id_pegawai'],
            'tanggal_ttd' => ['nullable', 'date'],
            'template_file' => ['nullable', 'file', 'mimes:doc,docx', 'max:10120'],
            'struktur_program' => ['sometimes', 'array'],
            'struktur_program.*.id_kelas' => ['sometimes', 'nullable', 'integer'],
            'struktur_program.*.materi' => ['required_with:struktur_program', 'string', 'max:255'],
            'struktur_program.*.jumlah_jam' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'struktur_program.*.urutan' => ['sometimes', 'nullable', 'integer'],
            'status' => ['sometimes', Rule::in(['draft', 'terbit', 'dicabut'])],
        ];
    }
}
