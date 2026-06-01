<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateSertifikatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_batch' => ['required', 'exists:sertifikat_batch,id_batch'],
            'id_peserta' => ['required', 'exists:peserta,id_peserta'],
            'status' => ['sometimes', Rule::in(['draft', 'terbit', 'dicabut'])],
        ];
    }
}
