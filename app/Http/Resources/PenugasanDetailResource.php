<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PenugasanDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_penugasan' => $this->id,
            'id_kegiatan' => $this->id_kegiatan,
            'id_pegawai' => $this->id_pegawai,
            'peran' => $this->peran,
            'nama_pegawai' => $this->pegawai?->nama ?? 'N/A',
            'nama_kegiatan' => $this->kegiatan?->nama_kegiatan ?? 'N/A',
            'tanggal_mulai' => $this->kegiatan?->tanggal_mulai,
            'tanggal_selesai' => $this->kegiatan?->tanggal_selesai,
            'kabupaten_kota' => $this->kegiatan?->daftarTpk?->first()?->kabupaten_kota,
            'lokasi' => $this->kegiatan?->daftarTpk?->first()?->lokasi,
            'daftar_tpk' => $this->kegiatan?->daftarTpk,
        ];
    }
}
