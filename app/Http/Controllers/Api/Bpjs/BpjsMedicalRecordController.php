<?php

namespace App\Http\Controllers\Api\Bpjs;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bpjs\MedicalRecordPreviewRequest;
use App\Jobs\ProcessBpjsMedicalRecordJob;
use App\Models\BpjsMedicalRecordJob;
use App\Services\Bpjs\BridgingV3;
use App\Services\Bpjs\MedicalRecord\BuildBpjsMedicalRecordRequestService;
use App\Services\Bpjs\MedicalRecord\BpjsMedicalRecordResourceBlueprintService;
use App\Services\Bpjs\MedicalRecord\EncodeBpjsMedicalRecordPayloadService;
use App\Services\Bpjs\MedicalRecord\GenerateBpjsMedicalRecordBundleService;
use App\Support\Bpjs\MedicalRecord\BpjsMedicalRecordHistoryPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class BpjsMedicalRecordController extends Controller
{
    public function resources(BpjsMedicalRecordResourceBlueprintService $blueprints): JsonResponse
    {
        return ApiResponse::success([
            'items' => array_values(array_map(
                fn (string $key) => $blueprints->get($key),
                array_keys($blueprints->all())
            )),
        ], 'Blueprint resource medical record BPJS berhasil dimuat');
    }

    public function resourceDetail(string $resource, BpjsMedicalRecordResourceBlueprintService $blueprints): JsonResponse
    {
        return ApiResponse::success([
            'item' => $blueprints->get($resource),
            'generated_template' => $blueprints->template($resource),
        ], 'Detail blueprint resource medical record BPJS berhasil dimuat');
    }

    public function history(BpjsMedicalRecordHistoryPresenter $presenter): JsonResponse
    {
        if (!$this->historyStorageReady()) {
            return ApiResponse::success([
                'items' => [],
                'storage_ready' => false,
            ], 'Histori medical record BPJS belum tersedia. Jalankan migrasi terlebih dahulu.');
        }

        $jobs = BpjsMedicalRecordJob::query()
            ->with('payloads')
            ->latest()
            ->limit(20)
            ->get();

        return ApiResponse::success([
            'items' => $presenter->presentCollection($jobs),
            'storage_ready' => true,
        ], 'Histori medical record BPJS berhasil dimuat');
    }

    public function preview(
        MedicalRecordPreviewRequest $request,
        GenerateBpjsMedicalRecordBundleService $bundleService,
        EncodeBpjsMedicalRecordPayloadService $encoder,
        BuildBpjsMedicalRecordRequestService $builder,
    ): JsonResponse {
        $prepared = $this->preparePayload(
            $request->no_sep,
            $request->jns_pelayanan,
            $request->bulan,
            $request->tahun,
            $request->input('bundle'),
            $bundleService,
            $encoder,
            $builder
        );

        $job = $this->storeSnapshot(
            requestData: $request->validated(),
            prepared: $prepared,
            status: 'generated'
        );

        return ApiResponse::success([
            'job_id' => $job?->id,
            'payload_id' => $job?->payloads()->latest('id')->value('id'),
            'raw_bundle' => $prepared['bundle'],
            'bundle_snapshot' => $prepared['snapshot'],
            'request_payload' => $prepared['payload'],
            'encoding' => [
                'gzip_payload_base64_length' => strlen($prepared['encoded']['gzip_payload']),
                'encrypted_payload_length' => strlen($prepared['encoded']['encrypted_payload']),
            ],
            'source' => [
                'sample_path' => config('services.bpjs_medical_record.sample_path'),
                'bundle_source' => $prepared['source'],
            ],
            'storage_ready' => $job !== null,
        ], 'Preview medical record BPJS berhasil dibuat');
    }

    public function insert(
        MedicalRecordPreviewRequest $request,
        GenerateBpjsMedicalRecordBundleService $bundleService,
        EncodeBpjsMedicalRecordPayloadService $encoder,
        BuildBpjsMedicalRecordRequestService $builder,
        BridgingV3 $bpjs,
    ): JsonResponse {
        $prepared = $this->preparePayload(
            $request->no_sep,
            $request->jns_pelayanan,
            $request->bulan,
            $request->tahun,
            $request->input('bundle'),
            $bundleService,
            $encoder,
            $builder
        );

        $job = $this->storeSnapshot(
            requestData: $request->validated(),
            prepared: $prepared,
            status: 'draft'
        );

        $payloadRecord = $job?->payloads()->latest('id')->first();

        $query = $bpjs->insertMedicalRecord($prepared['payload']);

        if (!$query->exec()) {
            $job?->update([
                'status' => 'failed',
                'last_error' => $bpjs->getError()['message'],
            ]);

            $payloadRecord?->update([
                'request_json' => json_encode($prepared['payload'], JSON_UNESCAPED_SLASHES),
                'response_json' => json_encode([
                    'metaData' => $bpjs->getMetaData(),
                    'response' => $bpjs->getResponse(),
                ], JSON_UNESCAPED_SLASHES),
                'bpjs_response_code' => (string) $bpjs->getError()['code'],
                'bpjs_response_message' => $bpjs->getError()['message'],
            ]);

            return ApiResponse::error(
                $bpjs->getError()['message'],
                (int) $bpjs->getError()['code'],
                [
                    'job_id' => $job?->id,
                    'payload_id' => $payloadRecord?->id,
                    'raw_bundle' => $prepared['bundle'],
                    'bundle_snapshot' => $prepared['snapshot'],
                    'request_payload' => $prepared['payload'],
                    'encoding' => [
                        'gzip_payload_base64_length' => strlen($prepared['encoded']['gzip_payload']),
                        'encrypted_payload_length' => strlen($prepared['encoded']['encrypted_payload']),
                    ],
                    'source' => [
                        'sample_path' => config('services.bpjs_medical_record.sample_path'),
                        'bundle_source' => $prepared['source'],
                    ],
                    'storage_ready' => $job !== null,
                ]
            );
        }

        $job?->update([
            'status' => 'sent',
            'last_error' => null,
            'sent_at' => now(),
        ]);

        $payloadRecord?->update([
            'request_json' => json_encode($prepared['payload'], JSON_UNESCAPED_SLASHES),
            'response_json' => json_encode([
                'metaData' => $bpjs->getMetaData(),
                'response' => $bpjs->getResponse(),
            ], JSON_UNESCAPED_SLASHES),
            'bpjs_response_code' => (string) $bpjs->getMetaData()->code,
            'bpjs_response_message' => (string) $bpjs->getMetaData()->message,
        ]);

        return ApiResponse::success([
            'job_id' => $job?->id,
            'payload_id' => $payloadRecord?->id,
            'raw_bundle' => $prepared['bundle'],
            'bundle_snapshot' => $prepared['snapshot'],
            'request_payload' => $prepared['payload'],
            'encoding' => [
                'gzip_payload_base64_length' => strlen($prepared['encoded']['gzip_payload']),
                'encrypted_payload_length' => strlen($prepared['encoded']['encrypted_payload']),
            ],
            'bpjs_response' => [
                'metaData' => $bpjs->getMetaData(),
                'response' => $bpjs->getResponse(),
            ],
            'source' => [
                'sample_path' => config('services.bpjs_medical_record.sample_path'),
                'bundle_source' => $prepared['source'],
            ],
            'storage_ready' => $job !== null,
        ], 'Insert medical record BPJS berhasil dikirim');
    }

    public function dispatch(
        MedicalRecordPreviewRequest $request,
        GenerateBpjsMedicalRecordBundleService $bundleService,
        EncodeBpjsMedicalRecordPayloadService $encoder,
        BuildBpjsMedicalRecordRequestService $builder,
    ): JsonResponse {
        if (!$this->historyStorageReady()) {
            return ApiResponse::error('Storage belum siap. Jalankan migrasi terlebih dahulu.', 503);
        }

        $prepared = $this->preparePayload(
            $request->no_sep,
            $request->jns_pelayanan,
            $request->bulan,
            $request->tahun,
            $request->input('bundle'),
            $bundleService,
            $encoder,
            $builder
        );

        $job = $this->storeSnapshot(
            requestData: $request->validated(),
            prepared: $prepared,
            status: 'queued'
        );

        $payloadRecord = $job->payloads()->latest('id')->first();

        ProcessBpjsMedicalRecordJob::dispatch(
            $job->id,
            $payloadRecord->id,
            $prepared['payload']
        );

        return ApiResponse::success([
            'job_id'     => $job->id,
            'payload_id' => $payloadRecord->id,
            'status'     => 'queued',
        ], 'Medical record BPJS berhasil diantrekan');
    }

    protected function preparePayload(
        string $noSep,
        string $jnsPelayanan,
        string $bulan,
        string $tahun,
        ?array $bundleInput,
        GenerateBpjsMedicalRecordBundleService $bundleService,
        EncodeBpjsMedicalRecordPayloadService $encoder,
        BuildBpjsMedicalRecordRequestService $builder,
    ): array {
        $bundle = is_array($bundleInput) && !empty($bundleInput)
            ? $bundleInput
            : $bundleService->fromSample();
        $snapshot = $bundleService->makeSnapshot($bundle);
        $encoded = $encoder->encode($bundle);
        $payload = $builder->build(
            $noSep,
            $jnsPelayanan,
            $bulan,
            $tahun,
            $encoded['encrypted_payload']
        );

        return [
            'bundle' => $bundle,
            'snapshot' => $snapshot,
            'encoded' => $encoded,
            'payload' => $payload,
            'source' => is_array($bundleInput) && !empty($bundleInput) ? 'staging_bundle' : 'sample_file',
        ];
    }

    protected function storeSnapshot(array $requestData, array $prepared, string $status): ?BpjsMedicalRecordJob
    {
        if (!$this->historyStorageReady()) {
            return null;
        }

        $job = BpjsMedicalRecordJob::create([
            'encounter_id' => null,
            'patient_id' => null,
            'sep_no' => $requestData['no_sep'],
            'jns_pelayanan' => $requestData['jns_pelayanan'],
            'bulan' => $requestData['bulan'],
            'tahun' => $requestData['tahun'],
            'status' => $status,
            'last_error' => null,
            'sent_at' => null,
        ]);

        $job->payloads()->create([
            'bundle_json' => json_encode($prepared['bundle'], JSON_UNESCAPED_SLASHES),
            'normalized_bundle_json' => json_encode($prepared['bundle'], JSON_UNESCAPED_SLASHES),
            'gzip_payload' => $prepared['encoded']['gzip_payload'],
            'encrypted_payload' => $prepared['encoded']['encrypted_payload'],
            'request_json' => null,
            'response_json' => null,
            'bpjs_response_code' => null,
            'bpjs_response_message' => null,
        ]);

        return $job;
    }

    protected function historyStorageReady(): bool
    {
        return Schema::hasTable('bpjs_medical_record_jobs')
            && Schema::hasTable('bpjs_medical_record_payloads');
    }
}
