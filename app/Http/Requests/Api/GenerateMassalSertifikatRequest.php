<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class GenerateMassalSertifikatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_batch' => ['required', 'exists:sertifikat_batch,id_batch'],
            'peserta_ids' => ['required', 'array', 'min:1'],
            'peserta_ids.*' => ['required', 'integer', 'distinct', 'exists:peserta,id_peserta'],
        ];
    }
}
