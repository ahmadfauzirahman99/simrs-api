<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Bpjs\BaseBridgingV3;
use App\Support\Bpjs\BpjsTesterCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class BpjsTesterPageController extends Controller
{
    public function __invoke(BpjsTesterCatalog $catalog): Response
    {
        return Inertia::render('Bpjs/Tester', [
            'endpointGroups' => $catalog->groups(),
            'systemLabel' => 'BPJS',
        ]);
    }

    public function encryptPreview(Request $request): JsonResponse
    {
        $payload = $request->input('payload');
        $consId = (string) ($request->input('cons_id') ?: config('services.bpjs_vclaim.cons_id', '12345'));
        $secretKey = (string) ($request->input('secret_key') ?: config('services.bpjs_vclaim.secret_key', 'userSecret123'));
        $timestamp = (string) ($request->input('timestamp') ?: time());
        $userKey = (string) ($request->input('user_key') ?: config('services.bpjs_vclaim.user_key', 'userKeyBPJS99'));

        $key = $consId . $secretKey . $timestamp;
        $signature = base64_encode(hash_hmac('sha256', $consId . '&' . $timestamp, $secretKey, true));

        $metaData = [
            'code' => '200',
            'message' => 'Sukses',
        ];

        $dataToEncrypt = $payload;
        if (is_array($payload) && array_key_exists('response', $payload)) {
            $dataToEncrypt = $payload['response'];
            if (isset($payload['metaData'])) {
                $metaData = $payload['metaData'];
            }
        }

        try {
            $ciphertext = BaseBridgingV3::stringEncrypt($key, $dataToEncrypt);
            $keyHash = hash('sha256', $key);
            $iv = substr(hex2bin($keyHash), 0, 16);

            return response()->json([
                'success' => true,
                'raw_bpjs_response' => [
                    'metaData' => $metaData,
                    'response' => $ciphertext,
                ],
                'crypto_details' => [
                    'algorithm' => 'AES-256-CBC',
                    'compression' => 'LZ-String (EncodedURIComponent)',
                    'cons_id' => $consId,
                    'secret_key_masked' => substr($secretKey, 0, 2) . str_repeat('*', max(2, strlen($secretKey) - 4)) . substr($secretKey, -2),
                    'timestamp' => $timestamp,
                    'derived_key' => $consId . '+' . substr($secretKey, 0, 2) . '***+' . $timestamp,
                    'signature_hmac_sha256' => $signature,
                    'user_key_masked' => substr($userKey, 0, 2) . str_repeat('*', max(2, strlen($userKey) - 4)) . substr($userKey, -2),
                    'iv_hex' => bin2hex($iv),
                    'plain_byte_size' => strlen(is_string($dataToEncrypt) ? $dataToEncrypt : (string) json_encode($dataToEncrypt)),
                    'encrypted_byte_size' => strlen($ciphertext),
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function decryptPreview(Request $request): JsonResponse
    {
        $ciphertext = (string) $request->input('ciphertext');
        $consId = (string) ($request->input('cons_id') ?: config('services.bpjs_vclaim.cons_id', '12345'));
        $secretKey = (string) ($request->input('secret_key') ?: config('services.bpjs_vclaim.secret_key', 'userSecret123'));
        $timestamp = (string) ($request->input('timestamp') ?: time());

        $key = $consId . $secretKey . $timestamp;

        try {
            $decrypted = BaseBridgingV3::stringDecrypt($key, $ciphertext);

            return response()->json([
                'success' => true,
                'decrypted' => $decrypted,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function logSimulation(Request $request): JsonResponse
    {
        $user = $request->user();
        $endpoint = (string) $request->input('endpoint', '/api/v1/bpjs/simulator');
        $method = strtoupper((string) $request->input('method', 'GET'));
        $durationMs = (int) $request->input('duration_ms', 120);
        $statusCode = (int) $request->input('status_code', 200);

        try {
            \App\Models\GatewayLog::create([
                'user_id' => $user?->id,
                'client_name' => $user?->name ?? 'Demo Client',
                'client_email' => $user?->email,
                'endpoint' => $endpoint,
                'method' => $method,
                'service' => \App\Models\GatewayLog::detectService($endpoint),
                'status_code' => $statusCode,
                'response_time_ms' => $durationMs,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'request_payload' => !empty($request->input('payload')) ? json_encode($request->input('payload')) : null,
                'response_summary' => substr((string) $request->input('summary', 'Simulasi web tester'), 0, 500),
                'is_mock' => true,
            ]);

            if ($user) {
                $today = now()->format('Y-m-d');
                $cacheKey = "client_req_{$user->id}_{$today}";
                $usedToday = (int) \Illuminate\Support\Facades\Cache::get($cacheKey, 0);
                \Illuminate\Support\Facades\Cache::put($cacheKey, $usedToday + 1, now()->endOfDay());
            }

            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
