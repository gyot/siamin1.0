<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatSertifikat extends Model
{
    protected $table = 'riwayat_sertifikat';

    public $timestamps = false;

    protected $fillable = [
        'tipe',
        'id_batch',
        'id_target',
        'aksi',
        'status_lama',
        'status_baru',
        'catatan',
        'id_pelaku',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public static function catat(string $tipe, int $idTarget, string $aksi, ?string $statusLama, string $statusBaru, ?string $catatan = null, ?int $idPelaku = null, ?int $idBatch = null): void
    {
        self::create([
            'tipe' => $tipe,
            'id_batch' => $idBatch,
            'id_target' => $idTarget,
            'aksi' => $aksi,
            'status_lama' => $statusLama,
            'status_baru' => $statusBaru,
            'catatan' => $catatan,
            'id_pelaku' => $idPelaku,
        ]);
    }
}
