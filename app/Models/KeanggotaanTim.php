<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeanggotaanTim extends Model
{
    use HasFactory;

    protected $table = 'keanggotan_tim';
    // primary key default id

    protected $fillable = [
        'id_pegawai',
        'unit_kerja_id',
        'sub_unit_kerja_id',
        'peran',
        'tahun',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai', 'id_pegawai');
    }

    public function unit()
    {
        return $this->belongsTo(UnitKerja::class, 'unit_kerja_id', 'id');
    }

    public function subUnit()
    {
        return $this->belongsTo(SubUnitKerja::class, 'sub_unit_kerja_id', 'id');
    }
}
