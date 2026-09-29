<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BpjsMedicalRecordJob;
use App\Services\Bpjs\MedicalRecord\BpjsMedicalRecordResourceBlueprintService;
use App\Support\Bpjs\MedicalRecord\BpjsMedicalRecordHistoryPresenter;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class BpjsMedicalRecordPageController extends Controller
{
    public function __invoke(
        BpjsMedicalRecordHistoryPresenter $presenter,
        BpjsMedicalRecordResourceBlueprintService $blueprints,
    ): Response
    {
        $historyReady = Schema::hasTable('bpjs_medical_record_jobs')
            && Schema::hasTable('bpjs_medical_record_payloads');

        $jobs = $historyReady
            ? BpjsMedicalRecordJob::query()
                ->with('payloads')
                ->latest()
                ->limit(12)
                ->get()
            : collect();

        return Inertia::render('Bpjs/MedicalRecord', [
            'history' => $presenter->presentCollection($jobs),
            'historyReady' => $historyReady,
            'resourceBlueprints' => array_values(array_map(
                fn (string $key) => $blueprints->get($key),
                array_keys($blueprints->all())
            )),
            'systemLabel' => 'BPJS',
            'samplePath' => config('services.bpjs_medical_record.sample_path'),
            'providerCode' => config('services.bpjs_medical_record.provider_code'),
            'rsCode' => config('services.bpjs_medical_record.rs_code'),
            'kemenkesCode' => config('services.bpjs_medical_record.kemenkes_code'),
        ]);
    }
}
