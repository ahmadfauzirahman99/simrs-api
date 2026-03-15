<?php

namespace App\Services\SatuSehat;

use App\Models\SatuSehatToken;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OAuth2Client
{
    public string $auth_url;
    public string $base_url;
    public string $client_id;
    public string $client_secret;
    public string $organization_id;
    public string $environment;

    public function __construct()
    {
        $this->loadConfig();
    }

    protected function loadConfig(): void
    {
        $this->environment = strtoupper((string) config('services.satusehat.env', 'DEV'));

        if ($this->environment === 'DEV') {
            $this->auth_url        = (string) config('services.satusehat.auth_dev');
            $this->base_url        = (string) config('services.satusehat.fhir_dev');
            $this->client_id       = (string) config('services.satusehat.client_id_dev');
            $this->client_secret   = (string) config('services.satusehat.client_secret_dev');
            $this->organization_id = (string) config('services.satusehat.org_id_dev');
        } else {
            $this->auth_url        = (string) config('services.satusehat.auth_prod');
            $this->base_url        = (string) config('services.satusehat.fhir_prod');
            $this->client_id       = (string) config('services.satusehat.client_id_prod');
            $this->client_secret   = (string) config('services.satusehat.client_secret_prod');
            $this->organization_id = (string) config('services.satusehat.org_id_prod');
        }

        if (empty($this->organization_id)) {
            throw new Exception('Add your organization_id in the environment first');
        }
    }

    public function token(): string|array
    {
        $this->loadConfig();

        if (empty($this->organization_id)) {
            throw new Exception('Add your organization_id in the environment first');
        }

        $url = rtrim($this->auth_url, '/') . '/accesstoken?grant_type=client_credentials';

        $response = Http::asForm()
            ->withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])
            ->post($url, [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
            ]);

        try {
            $content = $response->json();

            if (isset($content['access_token'])) {
                SatuSehatToken::create([
                    'environment' => $this->environment,
                    'token' => $content['access_token'],
                    'created_at_token' => now(),
                    'expired' => (int) ($content['expires_in'] ?? 0),
                ]);

                return $content['access_token'];
            }

            $msg = $content['issue'][0]['details']['text'] ?? 'Gagal mengambil token SATUSEHAT';

            return [
                'msg' => $msg,
            ];
        } catch (Exception $e) {
            Log::error('SATUSEHAT token error', [
                'message' => $e->getMessage(),
                'response' => $response->body(),
            ]);

            return [
                'msg' => $e->getMessage(),
            ];
        }
    }

    public function isTokenExpired($expiryTime, int $validityInSeconds): bool
    {
        if (!is_int($expiryTime)) {
            $expiryTime = strtotime((string) $expiryTime);
        }

        $currentTime = time();
        $differenceInSeconds = $expiryTime - $currentTime;

        return $differenceInSeconds <= $validityInSeconds;
    }

    public function getValidToken(): string|array
    {
        $latestToken = SatuSehatToken::where('environment', $this->environment)
            ->latest('id')
            ->first();

        if (!$latestToken) {
            return $this->token();
        }

        $expiredAt = Carbon::parse($latestToken->created_at_token)
            ->addSeconds((int) $latestToken->expired)
            ->timestamp;

        if ($this->isTokenExpired($expiredAt, 60)) {
            return $this->token();
        }

        return $latestToken->token;
    }
}
