<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SertifikatPesertaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_batch' => $this->id_batch,
            'id_peserta' => $this->id_peserta,
            'qr_token' => $this->qr_token,
            'status' => $this->status,
            'batch' => new SertifikatBatchResource($this->whenLoaded('batch')),
            'peserta' => $this->whenLoaded('peserta'),
            'lokasi' => $this->when(
                $this->relationLoaded('peserta') && $this->peserta?->relationLoaded('tpk'),
                fn () => [
                    'id_tpk' => $this->peserta->tpk?->id_tpk,
                    'lokasi' => $this->peserta->tpk?->lokasi,
                    'kabupaten_kota' => $this->peserta->tpk?->kabupaten_kota,
                ]
            ),
            'kelas' => $this->when(
                $this->relationLoaded('peserta') && $this->peserta?->relationLoaded('kelas'),
                fn () => $this->peserta->kelas->map(fn ($k) => [
                    'id_kelas' => $k->id_kelas,
                    'nama_kelas' => $k->nama_kelas,
                    'deskripsi' => $k->deskripsi,
                ])
            ),
            'kegiatan' => $this->when(
                $this->relationLoaded('batch') && $this->batch?->relationLoaded('kegiatan'),
                fn () => $this->batch->kegiatan
            ),
            'penandatangan' => $this->when(
                $this->relationLoaded('batch') && $this->batch?->relationLoaded('penandatangan'),
                fn () => $this->batch->penandatangan
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
