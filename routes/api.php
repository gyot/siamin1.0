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
    TpkController,
    SertifikatController,
    SertifikatBatchController,
    SertifikatPesertaController,
    AkunPesertaController,
    KeanggotaanTimController,
    LogAktivitasController,
    UnitKerjaController,
    SubUnitKerjaController,
    TestController,
    KelasController
};

Route::prefix('v1')->group(function () {

    // AUTH
    Route::prefix('auth')->group(function () {
        Route::post('/login-admin', [AuthController::class, 'loginAdmin']);
        Route::post('/login-peserta', [AuthController::class, 'loginPeserta']);
    });
Route::get('/evaluasi/random-fasilitator', [EvaluasiController::class, 'randomFasilitator'])
    ->name('evaluasi.random');

    // KEGIATAN & PESERTA TANPA LOGIN
    Route::get('kegiatan/statistik', [KegiatanController::class, 'statistik']);
    Route::get('kegiatan/all', [KegiatanController::class, 'getAllKegiatan']);
    Route::get('kegiatan/tim/{id}', [KegiatanController::class, 'getAllKegiatanTim']);
    Route::get('kegiatan/tim_kegiatan/{id}', [KegiatanController::class, 'getAllKegiatanTimKegiatan']);
    Route::get('kegiatan/tim-saya', [KegiatanController::class, 'getKegiatanTimSaya'])->middleware('auth:sanctum');
    Route::apiResource('kegiatan', KegiatanController::class);
    Route::get('kegiatan/{id}/peserta-sertifikat', [SertifikatPesertaController::class, 'pesertaByKegiatan']);
    Route::get('kegiatan/{id}/sertifikat-batch', [SertifikatBatchController::class, 'byKegiatan']);
    Route::apiResource('kegiatan-atk', KegiatanAtkController::class);
    Route::apiResource('tpk', TpkController::class);
    Route::get('peserta/{id}/kegiatan', [PesertaController::class, 'showWithKegiatan']);
    Route::apiResource('peserta', PesertaController::class);
    Route::apiResource('unit-kerja', UnitKerjaController::class);
    Route::get('unit-kerja/user/{id}', [UnitKerjaController::class, 'unit_user']);
    Route::post('evaluasi', [EvaluasiController::class, 'store']);
    Route::get('evaluasi/check/{id_kegiatan}/{id_tpk?}', [EvaluasiController::class, 'check']);
    Route::get('evaluasi/{id_kegiatan}/{id_tpk?}/statistik', [EvaluasiController::class, 'statistik']);
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('peran', [PenugasanPegawaiController::class, 'peran']);
    Route::get('penugasan/ketua-panitia/{id_kegiatan}', [PenugasanPegawaiController::class, 'ketuaPanitia']);
    Route::apiResource('sertifikat-batch', SertifikatBatchController::class);
    Route::post('sertifikat/generate', [SertifikatController::class, 'generate']);
    Route::post('sertifikat/generate-massal', [SertifikatController::class, 'generateMassal']);
    Route::get('sertifikat/peserta/{id_peserta}', [SertifikatController::class, 'byPeserta']);
    Route::patch('sertifikat-peserta/{id}/status', [SertifikatController::class, 'updatePesertaStatus']);
    Route::delete('sertifikat-peserta/{id}', [SertifikatController::class, 'destroyPeserta']);
    Route::apiResource('sertifikat', SertifikatController::class);

    // TEST / PAKET SOAL (public)
    Route::get('test/peserta/{id_kegiatan}', [TestController::class, 'pesertaByKegiatan']);
    Route::get('test/peserta-detail/{id_peserta}', [TestController::class, 'pesertaDetail']);
    Route::get('test/paket/{id_kegiatan}', [TestController::class, 'paketByKegiatan']);
    Route::get('test/soal/{id_paket_soal}', [TestController::class, 'soalByPaket']);
    Route::post('test/submit', [TestController::class, 'submit']);
    Route::get('test/hasil/{id_peserta}/{id_paket_soal}', [TestController::class, 'hasil']);
    Route::get('test/template', [TestController::class, 'downloadTemplate']);

    // PROTECTED
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('pegawai', PegawaiController::class);
        // Route::apiResource('peserta', PesertaController::class);
        Route::apiResource('akun-peserta', AkunPesertaController::class);
        Route::apiResource('keanggotaan-tim', KeanggotaanTimController::class);
        Route::apiResource('log-aktivitas', LogAktivitasController::class);
        Route::apiResource('penugasan-pegawai', PenugasanPegawaiController::class);
        Route::get('penugasan-pegawai-detailed', [PenugasanPegawaiController::class, 'indexWithDetails']);
        Route::apiResource('sub-unit-kerja', SubUnitKerjaController::class);
        Route::get('evaluasi/{id_kegiatan}/{id_tpk?}', [EvaluasiController::class, 'indexByKegiatan']);

        // TEST LAPORAN (admin)
        Route::get('test/laporan/{id_kegiatan}', [TestController::class, 'laporanByKegiatan']);
        Route::get('test/laporan/{id_kegiatan}/{id_peserta}/{id_paket_soal}', [TestController::class, 'laporanDetail']);

        // CRUD PAKET SOAL (admin)
        Route::get('test/paket-all', [TestController::class, 'indexPaket']);
        Route::post('test/paket', [TestController::class, 'storePaket']);
        Route::get('test/paket-manage/{id_paket_soal}', [TestController::class, 'showPaket']);
        Route::put('test/paket/{id_paket_soal}', [TestController::class, 'updatePaket']);
        Route::delete('test/paket/{id_paket_soal}', [TestController::class, 'destroyPaket']);

        // CRUD SOAL (admin)
        Route::get('test/paket/{id_paket_soal}/soal', [TestController::class, 'indexSoal']);
        Route::post('test/paket/{id_paket_soal}/soal', [TestController::class, 'storeSoal']);
        Route::post('test/paket/{id_paket_soal}/import', [TestController::class, 'importSoal']);
        Route::put('test/paket/{id_paket_soal}/soal-replace', [TestController::class, 'replaceSoal']);
        Route::put('test/soal/{id_soal}', [TestController::class, 'updateSoal']);
        Route::delete('test/soal/{id_soal}', [TestController::class, 'destroySoal']);

        // KELAS
        Route::get('kelas/{id_kegiatan}', [KelasController::class, 'index']);
        Route::post('kelas', [KelasController::class, 'store']);
        Route::get('kelas-detail/{id_kelas}', [KelasController::class, 'show']);
        Route::put('kelas/{id_kelas}', [KelasController::class, 'update']);
        Route::delete('kelas/{id_kelas}', [KelasController::class, 'destroy']);
        Route::post('kelas/{id_kelas}/anggota', [KelasController::class, 'addAnggota']);
        Route::delete('kelas/{id_kelas}/anggota/{id_peserta}', [KelasController::class, 'removeAnggota']);

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    });
