<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SertifikatBatch extends Model
{
    use HasFactory;

    protected $table = 'sertifikat_batch';
    protected $primaryKey = 'id_batch';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_kegiatan',
        'nomor_sertifikat',
        'id_penandatangan',
        'tanggal_ttd',
        'template_file',
        'status',
        'tanggal_diajukan',
        'tanggal_persetujuan',
        'tanggal_proses',
        'pesan_revisi',
        'alasan_penolakan',
        'id_pengaju',
    ];

    protected $casts = [
        'tanggal_ttd' => 'date',
        'tanggal_diajukan' => 'datetime',
        'tanggal_persetujuan' => 'datetime',
        'tanggal_proses' => 'datetime',
    ];

    /**
     * Pemicu notifikasi lonceng untuk SEMUA jalur mutasi batch (SertifikatController,
     * SertifikatBatchController, ajukan-ulang) — logika dibagikan di satu titik
     * karena update batch punya banyak pintu masuk yang saling membypass.
     */
    protected static function booted(): void
    {
        static::saved(function (SertifikatBatch $batch) {
            if (!$batch->wasRecentlyCreated && !$batch->wasChanged('status')) {
                return;
            }
            \App\Services\NotifikasiService::dariStatusNaskah($batch);
        });
    }

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function penandatangan()
    {
        return $this->belongsTo(Pegawai::class, 'id_penandatangan', 'id_pegawai');
    }

    public function sertifikatPeserta()
    {
        return $this->hasMany(SertifikatPeserta::class, 'id_batch', 'id_batch');
    }

    public function strukturProgram()
    {
        return $this->hasMany(StrukturProgram::class, 'id_batch', 'id_batch')->orderBy('urutan')->orderBy('id_struktur');
    }
}
