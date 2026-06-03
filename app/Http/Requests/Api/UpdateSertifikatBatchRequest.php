<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSertifikatBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_kegiatan' => ['sometimes', 'exists:kegiatan,id_kegiatan'],
            'nomor_sertifikat' => ['sometimes', 'string', 'max:150'],
            'id_penandatangan' => ['nullable', 'exists:pegawai,id_pegawai'],
            'tanggal_ttd' => ['nullable', 'date'],
            'template_file' => ['nullable', 'file', 'mimes:doc,docx', 'max:10120'],
            'status' => ['sometimes', Rule::in(['draft', 'terbit', 'dicabut'])],
        ];
    }
}
