<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Antrol\AntreanRsController;
use App\Http\Controllers\Api\Apm\ApmController;

use App\Http\Controllers\Api\Bpjs\BpjsMedicalRecordController;
use App\Http\Controllers\Api\Bpjs\BpjsReferensiController;
use App\Http\Controllers\Api\Bpjs\BpjsRujukanController;
use App\Http\Controllers\Api\Bpjs\BpjsSukonController;
use App\Http\Controllers\Api\Bpjs\BpjsVClaimController;
use App\Http\Controllers\Api\SatuSehat\AccountController;
use App\Http\Controllers\Api\SatuSehat\AllergyIntoleranceController;
use App\Http\Controllers\Api\SatuSehat\ChargeItemController;
use App\Http\Controllers\Api\SatuSehat\ClaimController;
use App\Http\Controllers\Api\SatuSehat\ConditionController;
use App\Http\Controllers\Api\SatuSehat\CoverageController;
use App\Http\Controllers\Api\SatuSehat\DiagnosticReportController;
use App\Http\Controllers\Api\SatuSehat\EncounterController;
use App\Http\Controllers\Api\SatuSehat\ImmunizationController;
use App\Http\Controllers\Api\SatuSehat\InvoiceController;
use App\Http\Controllers\Api\SatuSehat\MedicationDispenseController;
use App\Http\Controllers\Api\SatuSehat\MedicationRequestController;
use App\Http\Controllers\Api\SatuSehat\ObservationController;
use App\Http\Controllers\Api\SatuSehat\ProcedureController;
use App\Http\Controllers\Api\SatuSehat\ServiceRequestController;
use App\Http\Controllers\Api\SatuSehat\TokenController;

Route::prefix('v1')->middleware(['throttle:api', 'client.api'])->group(function () {
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
        Route::get('/rekam-medis/resources', [BpjsMedicalRecordController::class, 'resources']);
        Route::get('/rekam-medis/resources/{resource}', [BpjsMedicalRecordController::class, 'resourceDetail']);
        Route::get('/rekam-medis/history', [BpjsMedicalRecordController::class, 'history']);
        Route::post('/rekam-medis/preview', [BpjsMedicalRecordController::class, 'preview']);
        Route::post('/rekam-medis/insert', [BpjsMedicalRecordController::class, 'insert']);
        Route::post('/rekam-medis/dispatch', [BpjsMedicalRecordController::class, 'dispatch']);

        // Rujukan
        Route::get('/rujukan/list', [BpjsRujukanController::class, 'list']);
        Route::get('/rujukan/detail', [BpjsRujukanController::class, 'detail']);
        Route::get('/rujukan/jumlah-sep', [BpjsRujukanController::class, 'jumlahSep']);

        // SEP tambahan
        Route::put('/sep/checkout', [BpjsVClaimController::class, 'checkoutSep']);
        Route::post('/sep/pengajuan', [BpjsVClaimController::class, 'pengajuanSep']);
        Route::post('/sep/approval', [BpjsVClaimController::class, 'approvalSep']);

        //Sukon
        Route::get('/surat-kontrol/sep', [BpjsSukonController::class, 'cariSep']);
        Route::get('/surat-kontrol/detail', [BpjsSukonController::class, 'cariSuratKontrol']);
        Route::get('/surat-kontrol/list', [BpjsSukonController::class, 'listRencanaKontrol']);
        Route::get('/surat-kontrol/list-tanggal', [BpjsSukonController::class, 'listRencanaKontrolByTanggal']);
        Route::post('/surat-kontrol/insert', [BpjsSukonController::class, 'insertSuratKontrol']);
        Route::post('/surat-kontrol/update', [BpjsSukonController::class, 'updateSuratKontrol']);
        Route::delete('/surat-kontrol', [BpjsSukonController::class, 'hapusSuratKontrol']);

        // SPRI
        Route::post('/spri/insert', [BpjsSukonController::class, 'insertSpri']);
        Route::post('/spri/update', [BpjsSukonController::class, 'updateSpri']);
    });

    Route::prefix('antrol')->group(function () {
        Route::get('/antrean', [AntreanRsController::class, 'index']);
        Route::get('/poli', [AntreanRsController::class, 'poli']);
        Route::get('/dokter', [AntreanRsController::class, 'dokter']);
        Route::get('/jadwal-dokter', [AntreanRsController::class, 'jadwalDokter']);
    });

    Route::prefix('apm')->group(function () {
        Route::post('/search-peserta', [ApmController::class, 'searchPeserta']);
        Route::get('/jadwal-hari-ini', [ApmController::class, 'jadwalHariIni']);
        Route::post('/check-in', [ApmController::class, 'checkIn']);
        Route::post('/daftar-mandiri', [ApmController::class, 'daftarMandiri']);
        Route::post('/update-task', [ApmController::class, 'updateTask']);
        Route::get('/status-antrean', [ApmController::class, 'statusAntrean']);
    });



    Route::prefix('satu-sehat')->group(function () {
        Route::get('/token', [TokenController::class, 'index']);
        Route::post('/encounter/send',       [EncounterController::class,      'send']);
        Route::post('/condition/send',       [ConditionController::class,      'send']);
        Route::post('/observation/send',     [ObservationController::class,    'send']);
        Route::post('/procedure/send',       [ProcedureController::class,      'send']);
        Route::post('/service-request/send', [ServiceRequestController::class, 'send']);

        // Klaim BPJS-K
        Route::post('/coverage/send',     [CoverageController::class,   'send']);
        Route::post('/account/send',      [AccountController::class,    'send']);
        Route::post('/charge-item/send',  [ChargeItemController::class, 'send']);
        Route::post('/invoice/send',      [InvoiceController::class,    'send']);
        Route::post('/claim/send',        [ClaimController::class,      'send']);

        // Klinis tambahan
        Route::post('/allergy-intolerance/send',  [AllergyIntoleranceController::class,  'send']);
        Route::post('/medication-request/send',   [MedicationRequestController::class,   'send']);
        Route::post('/medication-dispense/send',  [MedicationDispenseController::class,  'send']);
        Route::post('/diagnostic-report/send',    [DiagnosticReportController::class,    'send']);
        Route::post('/immunization/send',         [ImmunizationController::class,        'send']);
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

// Endpoint khusus Kiosk APM (Layar Sentuh Mesin Mandiri RS)
Route::prefix('apm-api')->middleware('throttle:120,1')->group(function () {
    Route::post('/search-peserta', [ApmController::class, 'searchPeserta']);
    Route::get('/jadwal-hari-ini', [ApmController::class, 'jadwalHariIni']);
    Route::post('/check-in', [ApmController::class, 'checkIn']);
    Route::post('/daftar-mandiri', [ApmController::class, 'daftarMandiri']);
    Route::post('/update-task', [ApmController::class, 'updateTask']);
    Route::get('/status-antrean', [ApmController::class, 'statusAntrean']);
    Route::get('/display-state', [\App\Http\Controllers\Web\DisplayAntreanController::class, 'state']);
});
