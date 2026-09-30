<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewayLog extends Model
{
    protected $fillable = [
        'user_id',
        'client_name',
        'client_email',
        'endpoint',
        'method',
        'service',
        'status_code',
        'response_time_ms',
        'ip_address',
        'user_agent',
        'request_payload',
        'response_summary',
        'is_mock',
    ];

    protected $casts = [
        'is_mock' => 'boolean',
        'status_code' => 'integer',
        'response_time_ms' => 'integer',
        'request_payload' => 'array',
        'response_summary' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function detectService(string $endpoint): string
    {
        if (str_contains($endpoint, '/bpjs') || str_contains($endpoint, 'bpjs')) {
            return 'BPJS';
        }
        if (str_contains($endpoint, '/satusehat') || str_contains($endpoint, 'satusehat')) {
            return 'SATUSEHAT';
        }
        if (str_contains($endpoint, '/antrean') || str_contains($endpoint, 'antrol')) {
            return 'ANTREAN';
        }
        return 'GATEWAY';
    }
}
