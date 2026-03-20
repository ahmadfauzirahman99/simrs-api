<?php

namespace App\Support\Api;

use App\Helpers\ApiResponse;
use App\Services\Bpjs\BridgingV3;
use Illuminate\Http\JsonResponse;

class ExternalIntegrationResponder
{
    public function bpjs(BridgingV3 $bpjs, mixed $query, string $message = 'Sukses'): JsonResponse
    {
        if (!$query->exec()) {
            return $this->withBpjsHeaders(
                ApiResponse::error($bpjs->getError()['message'], (int) $bpjs->getError()['code']),
                $bpjs
            );
        }

        return $this->withBpjsHeaders(
            ApiResponse::success($bpjs->getResponse(), $message),
            $bpjs
        );
    }

    protected function withBpjsHeaders(JsonResponse $response, BridgingV3 $bpjs): JsonResponse
    {
        $debugHeaders = $bpjs->getDebugHeadersSafe();
        $outboundPayload = $bpjs->getFinalOutboundPayload();
        $outboundQuery = $bpjs->getFinalOutboundQueryParams();

        if (is_array($outboundPayload)) {
            $outboundPayload = json_encode($outboundPayload, JSON_UNESCAPED_SLASHES);
        }

        if (is_array($outboundQuery)) {
            $outboundQuery = json_encode($outboundQuery, JSON_UNESCAPED_SLASHES);
        }

        return $response->withHeaders([
            'X-Target-Url' => $bpjs->finalUrl,
            'X-Target-Method' => $bpjs->method,
            'X-Target-Cons-Id' => $debugHeaders['x_cons_id'] ?? '',
            'X-Target-Timestamp' => $debugHeaders['x_timestamp'] ?? '',
            'X-Target-User-Key-Masked' => $debugHeaders['user_key_masked'] ?? '',
            'X-Target-Signature-Present' => $debugHeaders['x_signature_present'] ?? 'no',
            'X-Target-Payload' => base64_encode((string) ($outboundPayload ?? '')),
            'X-Target-Query' => base64_encode((string) ($outboundQuery ?? '')),
        ]);
    }
}
