<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogAktivitas extends Model
{
    use HasFactory;

    protected $table = 'log_aktivitas';
    // primary key is default 'id'
    public $timestamps = false; // migration only has created_at

    protected $fillable = [
        'id_user',
        'aktivitas',
        'tabel',
        'id_referensi',
        'created_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
