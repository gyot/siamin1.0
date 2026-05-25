<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\{
    DashboardController,
    UserController,
    PegawaiController,
    PenugasanPegawaiController,
    KegiatanController,
    EvaluasiController,
    KegiatanAtkController,
    PesertaController,
    SertifikatController,
    AkunPesertaController,
    KeanggotaanTimController,
    LogAktivitasController,
    UnitKerjaController,
    SubUnitKerjaController
};

Route::prefix('v1')->group(function () {

    // AUTH
    Route::prefix('auth')->group(function () {
        Route::post('/login-admin', [AuthController::class, 'loginAdmin']);
        Route::post('/login-peserta', [AuthController::class, 'loginPeserta']);
    });


    // KEGIATAN & PESERTA TANPA LOGIN
    Route::get('kegiatan/statistik', [KegiatanController::class, 'statistik']);
    Route::get('kegiatan/all', [KegiatanController::class, 'getAllKegiatan']);
    Route::get('kegiatan/tim/{id}', [KegiatanController::class, 'getAllKegiatanTim']);
    Route::get('kegiatan/tim_kegiatan/{id}', [KegiatanController::class, 'getAllKegiatanTimKegiatan']);
    Route::get('kegiatan/tim-saya', [KegiatanController::class, 'getKegiatanTimSaya'])->middleware('auth:sanctum');
    Route::apiResource('kegiatan', KegiatanController::class);
    Route::apiResource('kegiatan-atk', KegiatanAtkController::class);
    Route::apiResource('peserta', PesertaController::class);
    Route::apiResource('unit-kerja', UnitKerjaController::class);
    Route::get('unit-kerja/user/{id}', [UnitKerjaController::class, 'unit_user']);
    Route::post('evaluasi', [EvaluasiController::class, 'store']);
    Route::get('evaluasi/check/{id_kegiatan}', [EvaluasiController::class, 'check']);
    Route::get('evaluasi/{id_kegiatan}/statistik', [EvaluasiController::class, 'statistik']);
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('peran', [PenugasanPegawaiController::class, 'peran']);
    
    // PROTECTED
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('pegawai', PegawaiController::class);
        // Route::apiResource('peserta', PesertaController::class);
        Route::apiResource('sertifikat', SertifikatController::class);
        Route::apiResource('akun-peserta', AkunPesertaController::class);
        Route::apiResource('keanggotaan-tim', KeanggotaanTimController::class);
        Route::apiResource('log-aktivitas', LogAktivitasController::class);
        Route::apiResource('penugasan-pegawai', PenugasanPegawaiController::class);
        Route::get('penugasan-pegawai-detailed', [PenugasanPegawaiController::class, 'indexWithDetails']);
        Route::apiResource('sub-unit-kerja', SubUnitKerjaController::class);
        Route::get('evaluasi/{id_kegiatan}', [EvaluasiController::class, 'indexByKegiatan']);

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

});
