<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ApotekController;
use App\Http\Controllers\Api\VClaimController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AntreanRsController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\Bpjs\BpjsVClaimController;
use App\Http\Controllers\Api\Bpjs\BpjsReferensiController;
use App\Http\Controllers\Api\Bpjs\BpjsSukonController;

Route::prefix('v1')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'metaData' => [
                'code' => '200',
                'message' => 'Sukses',
            ],
            'response' => (object) [
                'app' => 'SIMRS API',
                'version' => 'v1',
                'status' => 'active',
            ],
        ]);
    });

    Route::prefix('bpjs')->group(function () {
        Route::get('/peserta', [BpjsVClaimController::class, 'peserta']);
        Route::get('/sep', [BpjsVClaimController::class, 'cariSep']);
        Route::get('/sep-riwayat', [BpjsVClaimController::class, 'historyPelayananPeserta']);
        Route::post('/sep', [BpjsVClaimController::class, 'insertSep']);
        Route::put('/sep', [BpjsVClaimController::class, 'updateSep']);
        Route::delete('/sep', [BpjsVClaimController::class, 'hapusSep']);


        Route::get('/monitoring-kunjungan', [BpjsVClaimController::class, 'monitoringKunjungan']);
        Route::get('/monitoring-klaim', [BpjsVClaimController::class, 'monitoringKlaim']);

        Route::get('/referensi/poli', [BpjsReferensiController::class, 'poli']);
        Route::get('/referensi/diagnosa', [BpjsReferensiController::class, 'diagnosa']);
        Route::get('/referensi/faskes', [BpjsReferensiController::class, 'faskes']);
        Route::get('/referensi/dokter-dpjp', [BpjsReferensiController::class, 'dokterDpjp']);
        Route::get('/referensi/provinsi', [BpjsReferensiController::class, 'provinsi']);
        Route::get('/referensi/kabupaten', [BpjsReferensiController::class, 'kabupaten']);
        Route::get('/referensi/kecamatan', [BpjsReferensiController::class, 'kecamatan']);
        Route::get('/referensi/prosedur', [BpjsReferensiController::class, 'prosedur']);


        //Sukon
        Route::post('/surat-kontrol/insert', [BpjsSukonController::class, 'insertSuratKontrol']);
        Route::post('/surat-kontrol/update', [BpjsSukonController::class, 'updateSuratKontrol']);

        // SPRI
        Route::post('/spri/insert', [BpjsSukonController::class, 'insertSpri']);
        Route::post('/spri/update', [BpjsSukonController::class, 'updateSpri']);

        });

    Route::fallback(function () {
        return response()->json([
            'metaData' => [
                'code' => '404',
                'message' => 'Endpoint tidak ditemukan',
            ],
            'response' => (object) [],
        ], 404);
    });
});
