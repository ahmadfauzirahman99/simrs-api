<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BpjsMedicalRecordJob extends Model
{
    protected $table = 'bpjs_medical_record_jobs';

    protected $fillable = [
        'encounter_id',
        'patient_id',
        'sep_no',
        'jns_pelayanan',
        'bulan',
        'tahun',
        'status',
        'last_error',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function payloads(): HasMany
    {
        return $this->hasMany(BpjsMedicalRecordPayload::class, 'job_id');
    }
}
