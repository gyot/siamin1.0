<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KegiatanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_kegiatan' => $this->id_kegiatan,
            'nama_kegiatan' => $this->nama_kegiatan,
            'rincian_kegiatan' => $this->rincian_kegiatan,
            'dokumentasi_url' => $this->dokumentasi_url,
            'materi_url' => $this->materi_url,
            'panduan_url' => $this->panduan_url,
            'laporan_url' => $this->laporan_url,
            'surat_menyurat_url' => $this->surat_menyurat_url,
            'tanggal_mulai' => $this->tanggal_mulai,
            'tanggal_selesai' => $this->tanggal_selesai,
            'flyer' => $this->flyer,
            'template_biodata' => $this->template_biodata,
            'peserta_ringkasan' => $this->peserta_ringkasan,
            'total_peserta' => $this->total_peserta,
            'metode_pembayaran' => $this->metode_pembayaran,
            'deskripsi' => $this->deskripsi,
            'metode_pelaksanaan' => $this->metode_pelaksanaan,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'id_pegawai' => $this->id_pegawai,
            'unit_kerja_id' => $this->unit_kerja_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'daftar_atk' => $this->whenLoaded('daftarAtk'),
            'daftar_tpk' => $this->whenLoaded('daftarTpk'),
            'paket_soal' => $this->whenLoaded('paketSoals'),
        ];
    }
}
