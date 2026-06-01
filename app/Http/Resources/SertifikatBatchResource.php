<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SertifikatBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_batch' => $this->id_batch,
            'id_kegiatan' => $this->id_kegiatan,
            'nomor_sertifikat' => $this->nomor_sertifikat,
            'id_penandatangan' => $this->id_penandatangan,
            'tanggal_ttd' => optional($this->tanggal_ttd)->format('Y-m-d'),
            'template_file' => $this->template_file,
            'status' => $this->status,
            'kegiatan' => $this->whenLoaded('kegiatan'),
            'penandatangan' => $this->whenLoaded('penandatangan'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
