<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardKegiatanIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tahun' => ['sometimes', 'nullable', 'digits:4'],
            'status' => [
                'sometimes',
                'nullable',
                Rule::in(['draft', 'berjalan', 'selesai', 'dibatalkan']),
            ],
            'unit_kerja' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'page' => $this->input('page', 1),
            'limit' => $this->input('limit', 9),
            'search' => $this->filled('search') ? trim((string) $this->input('search')) : null,
            'tahun' => $this->filled('tahun') ? (string) $this->input('tahun') : null,
            'status' => $this->filled('status') ? trim((string) $this->input('status')) : null,
            'unit_kerja' => $this->filled('unit_kerja') ? trim((string) $this->input('unit_kerja')) : null,
        ]);
    }
}
