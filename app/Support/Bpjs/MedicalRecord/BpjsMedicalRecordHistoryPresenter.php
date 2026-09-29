<?php

namespace App\Support\Bpjs\MedicalRecord;

use App\Models\BpjsMedicalRecordJob;
use App\Services\Bpjs\MedicalRecord\GenerateBpjsMedicalRecordBundleService;
use Illuminate\Support\Collection;

class BpjsMedicalRecordHistoryPresenter
{
    public function __construct(
        protected GenerateBpjsMedicalRecordBundleService $bundleService,
    ) {
    }

    public function presentCollection(Collection $jobs): array
    {
        return $jobs->map(fn (BpjsMedicalRecordJob $job) => $this->presentJob($job))->all();
    }

    public function presentJob(BpjsMedicalRecordJob $job): array
    {
        $payload = $job->payloads->sortByDesc('id')->first();

        $bundle = $this->decodeJson($payload?->normalized_bundle_json ?: $payload?->bundle_json);
        $snapshot = is_array($bundle) ? $this->bundleService->makeSnapshot($bundle) : null;
        $request = $this->decodeJson($payload?->request_json);
        $response = $this->decodeJson($payload?->response_json);

        return [
            'id' => $job->id,
            'sep_no' => $job->sep_no,
            'jns_pelayanan' => $job->jns_pelayanan,
            'jns_pelayanan_label' => $job->jns_pelayanan === '1' ? 'Rawat Inap' : 'Rawat Jalan',
            'bulan' => $job->bulan,
            'tahun' => $job->tahun,
            'status' => $job->status,
            'last_error' => $job->last_error,
            'sent_at' => $job->sent_at?->toIso8601String(),
            'created_at' => $job->created_at?->toIso8601String(),
            'payload' => [
                'id' => $payload?->id,
                'bpjs_response_code' => $payload?->bpjs_response_code,
                'bpjs_response_message' => $payload?->bpjs_response_message,
                'gzip_payload_length' => strlen($payload?->gzip_payload ?? ''),
                'encrypted_payload_length' => strlen($payload?->encrypted_payload ?? ''),
                'raw_bundle' => $bundle,
                'request' => $request,
                'response' => $response,
            ],
            'bundle_snapshot' => $snapshot,
        ];
    }

    protected function decodeJson(?string $value): ?array
    {
        if (!$value) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
