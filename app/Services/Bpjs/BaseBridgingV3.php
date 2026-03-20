<?php

namespace App\Services\Bpjs;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LZCompressor\LZString;
use Throwable;

class BaseBridgingV3
{
    public string $ver;
    public string $baseUrl;
    public string $finalUrl = '';

    public string $cID;
    public string $cSecret;
    public string $user_key;

    public string $signature;
    public string $timestamp;
    public string $dekripsi_key;

    public string $method = 'GET';
    protected array|string|null $parameter = [];

    public array $header = [];
    public array $setup = [];

    public mixed $response = null;
    public ?string $errorMessage = null;
    public ?string $errorCode = null;

    public function __construct()
    {
        date_default_timezone_set('UTC');

        $this->ver      = (string) config('services.bpjs_v3.api_ver');
        $this->baseUrl  = rtrim((string) config('services.bpjs_v3.base_url'), '/');
        $this->cID      = (string) config('services.bpjs_v3.cons_id');
        $this->cSecret  = (string) config('services.bpjs_v3.secret_key');
        $this->user_key = (string) config('services.bpjs_v3.user_key');

        $this->timestamp = (string) time();

        $this->signature = base64_encode(
            hash_hmac('sha256', $this->cID . '&' . $this->timestamp, $this->cSecret, true)
        );

        $this->header = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'X-cons-id'    => $this->cID,
            'X-timestamp'  => $this->timestamp,
            'X-signature'  => $this->signature,
            'user_key'     => $this->user_key,
            'Accept'       => 'application/json',
        ];

        $this->setup = [
            'verify' => false,
            'timeout' => 60,
        ];

        $this->dekripsi_key = $this->cID . $this->cSecret . $this->timestamp;

        date_default_timezone_set('Asia/Jakarta');
    }

    public function setUpUrl(array|string $module, array|string|null $param = [], string $method = 'GET'): static
    {
        if (is_array($module)) {
            $module = implode('/', $module);
        }

        $module = trim(str_replace('\\', '/', $module), '/');

        $this->finalUrl = $this->baseUrl . '/' . $module;
        $this->parameter = $param;
        $this->method = strtoupper($method);

        return $this;
    }

    public function exec(): bool
    {
        try {
            if (empty($this->finalUrl)) {
                $this->errorCode = '500';
                $this->errorMessage = 'URL BPJS belum diset';
                return false;
            }

            $raw = $this->execute();

            $this->response = json_decode($raw);

            if (
                !is_null($this->response) &&
                isset($this->response->response) &&
                !empty($this->response->response)
            ) {
                $this->response->response = self::stringDecrypt(
                    $this->dekripsi_key,
                    $this->response->response
                );
            }

            $this->writeLog();

            return $this->validateResponse();
        } catch (Throwable $e) {
            $this->errorCode = '500';
            $this->errorMessage = $e->getMessage();

            Log::error('BPJS Bridging Error', [
                'url' => $this->finalUrl,
                'method' => $this->method,
                'parameter' => $this->parameter,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function execute(): string
    {
        $http = Http::withHeaders($this->header)
            ->withOptions($this->setup);

        return match ($this->method) {
            'POST' => $http->withBody(
                is_array($this->parameter) ? json_encode($this->parameter) : (string) $this->parameter,
                'application/x-www-form-urlencoded'
            )->post($this->finalUrl)->body(),

            'PUT' => $http->withBody(
                is_array($this->parameter) ? json_encode($this->parameter) : (string) $this->parameter,
                'application/x-www-form-urlencoded'
            )->put($this->finalUrl)->body(),

            'DELETE' => $http->withBody(
                is_array($this->parameter) ? json_encode($this->parameter) : (string) $this->parameter,
                'application/x-www-form-urlencoded'
            )->delete($this->finalUrl)->body(),

            default => $http->get(
                $this->finalUrl,
                is_array($this->parameter) ? $this->parameter : []
            )->body(),
        };
    }

    protected function validateResponse(): bool
    {
        if (!isset($this->response->metaData)) {
            $this->errorCode = '500';
            $this->errorMessage = 'Tidak dapat menghubungkan dengan server BPJS';
            return false;
        }

        if ((string) $this->response->metaData->code === '200') {
            $this->errorCode = null;
            $this->errorMessage = null;
            return true;
        }

        $this->errorCode = (string) ($this->response->metaData->code ?? '500');
        $this->errorMessage = (string) ($this->response->metaData->message ?? 'Terjadi kesalahan');

        return false;
    }

    public function getResponse(): mixed
    {
        if (!empty($this->response) && isset($this->response->response) && empty($this->errorCode)) {
            return $this->response->response;
        }

        return (object) [];
    }

    public function getMetaData(): object
    {
        return (object) [
            'code' => (string) ($this->errorCode ?? ($this->response->metaData->code ?? '200')),
            'message' => (string) ($this->errorMessage ?? ($this->response->metaData->message ?? 'Sukses')),
        ];
    }

    public function getError(): array
    {
        return [
            'code' => $this->errorCode ?? '500',
            'message' => $this->errorMessage ?? 'Terjadi kesalahan',
        ];
    }

    public function getParam(): array|string|null
    {
        return $this->parameter;
    }

    public function getDebugHeadersSafe(): array
    {
        return [
            'x_cons_id' => $this->header['X-cons-id'] ?? null,
            'x_timestamp' => $this->header['X-timestamp'] ?? null,
            'user_key_masked' => $this->maskValue($this->header['user_key'] ?? null),
            'x_signature_present' => !empty($this->header['X-signature']) ? 'yes' : 'no',
        ];
    }

    public function getFinalOutboundPayload(): array|string|null
    {
        return $this->parameter;
    }

    public function getFinalOutboundQueryParams(): array|string|null
    {
        if ($this->method !== 'GET') {
            return null;
        }

        return $this->parameter;
    }

    protected function maskValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $length = strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 2) . str_repeat('*', max(2, $length - 4)) . substr($value, -2);
    }

    protected function writeLog(): void
    {
        Log::info('BPJS Bridging Log', [
            'url' => $this->finalUrl,
            'method' => $this->method,
            'parameter' => $this->parameter,
            'response' => $this->response,
        ]);
    }

    public static function stringDecrypt(string $key, string $string): mixed
    {
        $encrypt_method = 'AES-256-CBC';

        $key_hash = hex2bin(hash('sha256', $key));
        $iv = substr(hex2bin(hash('sha256', $key)), 0, 16);

        $output = openssl_decrypt(
            base64_decode($string),
            $encrypt_method,
            $key_hash,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($output === false) {
            return (object) [];
        }

        $output = LZString::decompressFromEncodedURIComponent($output);

        return json_decode($output) ?? (object) [];
    }
}
