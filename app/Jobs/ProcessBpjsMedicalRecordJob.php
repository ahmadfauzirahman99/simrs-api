<?php

namespace App\Jobs;

use App\Models\BpjsMedicalRecordJob;
use App\Models\BpjsMedicalRecordPayload;
use App\Services\Bpjs\BridgingV3;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBpjsMedicalRecordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $jobId,
        public readonly int $payloadId,
        public readonly array $requestPayload,
    ) {}

    public function handle(BridgingV3 $bpjs): void
    {
        $job = BpjsMedicalRecordJob::findOrFail($this->jobId);
        $payloadRecord = BpjsMedicalRecordPayload::findOrFail($this->payloadId);

        $query = $bpjs->insertMedicalRecord($this->requestPayload);

        if (!$query->exec()) {
            $job->update([
                'status'     => 'failed',
                'last_error' => $bpjs->getError()['message'],
            ]);

            $payloadRecord->update([
                'request_json'         => json_encode($this->requestPayload, JSON_UNESCAPED_SLASHES),
                'response_json'        => json_encode([
                    'metaData' => $bpjs->getMetaData(),
                    'response' => $bpjs->getResponse(),
                ], JSON_UNESCAPED_SLASHES),
                'bpjs_response_code'    => (string) $bpjs->getError()['code'],
                'bpjs_response_message' => $bpjs->getError()['message'],
            ]);

            $this->fail($bpjs->getError()['message']);
            return;
        }

        $job->update([
            'status'     => 'sent',
            'last_error' => null,
            'sent_at'    => now(),
        ]);

        $payloadRecord->update([
            'request_json'         => json_encode($this->requestPayload, JSON_UNESCAPED_SLASHES),
            'response_json'        => json_encode([
                'metaData' => $bpjs->getMetaData(),
                'response' => $bpjs->getResponse(),
            ], JSON_UNESCAPED_SLASHES),
            'bpjs_response_code'    => (string) $bpjs->getMetaData()->code,
            'bpjs_response_message' => (string) $bpjs->getMetaData()->message,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        BpjsMedicalRecordJob::where('id', $this->jobId)->update([
            'status'     => 'failed',
            'last_error' => $e->getMessage(),
        ]);
    }
}
