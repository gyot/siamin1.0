<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardKegiatanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_kegiatan' => $this->id_kegiatan,
            'nama_kegiatan' => $this->nama_kegiatan,
            'tanggal_mulai' => $this->tanggal_mulai,
            'tanggal_selesai' => $this->tanggal_selesai,
            'lokasi' => $this->lokasi,
            'status' => $this->status,
            'total_peserta' => $this->total_peserta,
            'peserta_ringkasan' => $this->peserta_ringkasan,
            'unit_kerja_id' => $this->unit_kerja_id,
            'unit_kerja' => $this->unit_kerja,
        ];
    }
}
