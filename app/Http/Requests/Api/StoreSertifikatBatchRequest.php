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
            'id_kegiatan' => ['required', 'exists:kegiatan,id_kegiatan'],
            'nomor_sertifikat' => ['required', 'string', 'max:150'],
            'id_penandatangan' => ['nullable', 'exists:pegawai,id_pegawai'],
            'tanggal_ttd' => ['nullable', 'date'],
            'template_file' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['draft', 'terbit', 'dicabut'])],
        ];
    }
}
