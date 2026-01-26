<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitKerja extends Model
{
    use HasFactory;

    protected $table = 'unit_kerja';
    public $timestamps = true;

    protected $fillable = [
        'kode_unit', 'nama_unit', 'jenis_unit', 'tahun', 'keterangan'
    ];

    public function subUnitKerja()
    {
        return $this->hasMany(SubUnitKerja::class, 'unit_kerja_id');
    }

    public function keanggotatanTim()
    {
        return $this->hasMany(KeanggotatanTim::class, 'unit_kerja_id');
    }
}
