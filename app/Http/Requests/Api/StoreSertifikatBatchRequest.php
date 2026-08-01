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
            'status' => ['sometimes', Rule::in(['draft', 'terbit', 'dicabut'])],
        ];
    }
}
