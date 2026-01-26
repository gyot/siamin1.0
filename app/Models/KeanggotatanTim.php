<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeanggotatanTim extends Model
{
    use HasFactory;

    protected $table = 'keanggotan_tim';
    public $timestamps = true;

    protected $fillable = [
        'id_pegawai', 'unit_kerja_id', 'sub_unit_kerja_id', 'peran', 'tahun'
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai', 'id_pegawai');
    }

    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class, 'unit_kerja_id');
    }

    public function subUnitKerja()
    {
        return $this->belongsTo(SubUnitKerja::class, 'sub_unit_kerja_id');
    }
}
