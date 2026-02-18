<?php

namespace App\Http\Controllers\Api;

use App\Models\LogAktivitas;

class LogAktivitasController extends BaseApiController
{
    protected $modelClass = LogAktivitas::class;
    // no validation required; logs created by application logic
    protected $rules = [];
}
