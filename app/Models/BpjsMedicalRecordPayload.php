<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpjsMedicalRecordPayload extends Model
{
    protected $table = 'bpjs_medical_record_payloads';

    protected $fillable = [
        'job_id',
        'bundle_json',
        'normalized_bundle_json',
        'gzip_payload',
        'encrypted_payload',
        'request_json',
        'response_json',
        'bpjs_response_code',
        'bpjs_response_message',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(BpjsMedicalRecordJob::class, 'job_id');
    }
}
