<?php

use App\Http\Controllers\Api\OgMetaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Open Graph link-preview routes
|--------------------------------------------------------------------------
|
| Shareable public pages. The web server (nginx / Apache) forwards requests
| for these paths to Laravel so the SPA shell can be served with per-page
| Open Graph meta tags injected — crawler bots (WhatsApp, Telegram, Facebook,
| ...) do not execute JavaScript and would otherwise only see the generic
| index.html title. Humans receive the same SPA shell back with harmless
| extra <meta> tags, so routing everyone through here is safe.
|
*/

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/kegiatan/{kode}/{slugJudul?}', [OgMetaController::class, 'show']);
    Route::get('/daftar-peserta/{kode}/{slugJudul?}', [OgMetaController::class, 'show']);
    Route::get('/unduh-sertifikat/{kode}/{slugJudul?}', [OgMetaController::class, 'show']);
    Route::get('/evaluasi/{kode}/{idTpk?}/{slugJudul?}', [OgMetaController::class, 'show']);
    Route::get('/formulir/{kode}/{peran}/{idTpk?}/{slugJudul?}', [OgMetaController::class, 'show']);
    Route::get('/laporan-evaluasi/{kode}/{idTpk?}/{slugJudul?}', [OgMetaController::class, 'show']);
});
