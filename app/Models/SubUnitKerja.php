<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubUnitKerja extends Model
{
    use HasFactory;

    protected $table = 'sub_unit_kerja';
    public $timestamps = true;

    protected $fillable = [
        'unit_kerja_id', 'nama_sub_unit', 'fungsi'
    ];

    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class, 'unit_kerja_id');
    }

    public function keanggotatanTim()
    {
        return $this->hasMany(KeanggotatanTim::class, 'sub_unit_kerja_id');
    }
}
