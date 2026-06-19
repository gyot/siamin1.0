<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKegiatanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'rincian_kegiatan' => 'sometimes|string',
            'dokumentasi_url' => 'sometimes|url|max:255',
            'materi_url' => 'sometimes|url|max:255',
            'panduan_url' => 'sometimes|url|max:255',
            'laporan_url' => 'sometimes|url|max:255',
            'surat_menyurat_url' => 'sometimes|url|max:255',
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'flyer' => 'sometimes|file|image|max:10048',
            'template_biodata' => 'sometimes|file|mimes:doc,docx|max:10120',
            'peserta_ringkasan' => 'sometimes|string',
            'total_peserta' => 'sometimes|integer|min:0',
            'metode_pembayaran' => [
                'sometimes',
                Rule::in(['transfer', 'pulsa', 'transfer_dan_pulsa', 'tunai', 'tidak_dibayar']),
            ],
            'deskripsi' => 'sometimes|nullable|string',
            'metode_pelaksanaan' => [
                'sometimes',
                Rule::in(['daring', 'luring', 'hybrid']),
            ],
            'status' => [
                'required',
                Rule::in(['draft', 'berjalan', 'selesai', 'dibatalkan']),
            ],
            'created_by' => 'sometimes|nullable|exists:users,id_user',
            'id_pegawai' => ['required', 'nullable', 'exists:pegawai,id_pegawai'],
            'unit_kerja_id' => 'sometimes|nullable|exists:unit_kerja,id',
            'daftar_atk' => 'sometimes|array',
            'daftar_atk.*.nama_barang' => ['required_with:daftar_atk', 'string', 'max:255'],
            'daftar_atk.*.spesifikasi' => 'sometimes|nullable|string|max:255',
            'daftar_atk.*.jumlah' => 'sometimes|integer|min:1',
            'daftar_atk.*.satuan' => 'sometimes|nullable|string|max:100',
            'daftar_atk.*.keterangan' => 'sometimes|nullable|string',
            'daftar_tpk' => 'required|array|min:1',
            'daftar_tpk.*.lokasi' => ['required', 'string', 'max:255'],
            'daftar_tpk.*.kabupaten_kota' => 'sometimes|nullable|string|max:255',
        ];
    }
}
