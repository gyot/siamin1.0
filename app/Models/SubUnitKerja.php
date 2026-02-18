<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubUnitKerja extends Model
{
    use HasFactory;

    protected $table = 'sub_unit_kerja';
    // primary key default id

    protected $fillable = [
        'unit_kerja_id',
        'nama_sub_unit',
        'fungsi',
    ];

    public function unit()
    {
        return $this->belongsTo(UnitKerja::class, 'unit_kerja_id', 'id');
    }
}
