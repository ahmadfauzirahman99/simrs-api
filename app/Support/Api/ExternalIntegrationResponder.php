<?php

namespace App\Support\Api;

use App\Helpers\ApiResponse;
use App\Services\Bpjs\BridgingV3;
use App\Support\Bpjs\BpjsMockRegistry;
use Illuminate\Http\JsonResponse;

class ExternalIntegrationResponder
{
    public function bpjs(BridgingV3 $bpjs, mixed $query, string $message = 'Sukses'): JsonResponse
    {
        $request = request();
        $isMockRequested = $request && ($request->header('X-Mock-Mode') === 'true' || $request->header('X-Mock') === 'true' || $request->boolean('mock'));
        $isNotConfigured = !BpjsMockRegistry::isConfigured();

        // 1. Jika klien secara eksplisit meminta Mock ATAU kredensial BPJS di server masih kosong/template:
        if ($isMockRequested || $isNotConfigured) {
            $path = $request ? '/' . ltrim($request->path(), '/') : '';
            $method = $request ? $request->method() : 'GET';
            $mockData = BpjsMockRegistry::findMock($path, $method);

            if ($mockData) {
                if ($request) {
                    $request->attributes->set('is_mock_response', true);
                }

                $metaCode = (int) ($mockData['metaData']['code'] ?? 200);
                $metaMsg = (string) ($mockData['metaData']['message'] ?? 'Sukses (Simulasi Mock BPJS)');
                $responseData = $mockData['response'] ?? null;

                $jsonResponse = response()->json([
                    'metaData' => [
                        'code' => (string) $metaCode,
                        'message' => $metaMsg,
                    ],
                    'response' => $responseData,
                ], $metaCode >= 200 && $metaCode < 300 ? 200 : ($metaCode >= 400 && $metaCode < 600 ? $metaCode : 200));

                return $this->withMockHeaders($jsonResponse, $bpjs, 'Simulator Mock Response (Kredensial BPJS Belum Dikonfigurasi atau Header X-Mock-Mode Aktif)');
            }
        }

        // 2. Jika mode Live (kredensial ada & tidak minta mock):
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
            'X-Gateway-Mode' => 'live',
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

    protected function withMockHeaders(JsonResponse $response, BridgingV3 $bpjs, string $note): JsonResponse
    {
        return $response->withHeaders([
            'X-Gateway-Mode' => 'mock-simulation',
            'X-Gateway-Note' => $note,
            'X-Target-Method' => $bpjs->method ?: 'GET',
        ]);
    }
}
