<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitKerja extends Model
{
    use HasFactory;

    protected $table = 'unit_kerja';
    // primary key default id

    protected $fillable = [
        'kode_unit',
        'nama_unit',
        'jenis_unit',
        'tahun',
        'keterangan',
    ];

    public function subUnits()
    {
        return $this->hasMany(SubUnitKerja::class, 'unit_kerja_id', 'id');
    }
}
