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
            'kabupaten_kota' => $this->kabupaten_kota,
            'status' => $this->status,
            'total_peserta' => $this->total_peserta,
            'peserta_ringkasan' => $this->peserta_ringkasan,
            'metode_pelaksanaan' => $this->metode_pelaksanaan,
            'metode_pembayaran' => $this->metode_pembayaran,
            'deskripsi' => $this->deskripsi,
            'rincian_kegiatan' => $this->rincian_kegiatan,
            'dokumentasi_url' => $this->dokumentasi_url,
            'materi_url' => $this->materi_url,
            'panduan_url' => $this->panduan_url,
            'laporan_url' => $this->laporan_url,
            'surat_menyurat_url' => $this->surat_menyurat_url,
            'unit_kerja_id' => $this->unit_kerja_id,
            'unit_kerja' => $this->unit_kerja,
            'daftar_tpk' => $this->whenLoaded('daftarTpk'),
        ];
    }
}
