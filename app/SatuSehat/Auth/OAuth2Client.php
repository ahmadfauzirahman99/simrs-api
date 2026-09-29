<?php

namespace App\SatuSehat\Auth;

use App\SatuSehat\Contracts\HttpClientInterface;
use App\SatuSehat\Contracts\TokenStoreInterface;
use App\SatuSehat\Http\HttpException;
use App\SatuSehat\Support\Config;

class OAuth2Client
{
    public function __construct(
        private readonly Config               $config,
        private readonly HttpClientInterface  $http,
        private readonly TokenStoreInterface  $store,
    ) {}

    /**
     * Return a valid Bearer token, fetching a new one when the cached token is expired.
     *
     * @throws HttpException on HTTP error from the auth endpoint.
     */
    public function token(): string
    {
        $cached = $this->store->get();

        if ($cached !== null) {
            return $cached['token'];
        }

        return $this->fetchToken();
    }

    /**
     * Invalidate the cached token so the next call to token() fetches a fresh one.
     */
    public function flushToken(): void
    {
        $this->store->flush();
    }

    private function fetchToken(): string
    {
        $creds = $this->config->credentials();
        $url   = rtrim($creds['auth_url'], '/') . '/accesstoken?grant_type=client_credentials';

        $result = $this->http->request(
            'POST',
            $url,
            ['Content-Type: application/x-www-form-urlencoded'],
            http_build_query([
                'client_id'     => $creds['client_id'],
                'client_secret' => $creds['client_secret'],
            ])
        );

        if ($result['status'] !== 200) {
            throw new HttpException($result['status'], $result['body']);
        }

        $data = json_decode($result['body'], true);

        if (empty($data['access_token'])) {
            throw new HttpException($result['status'], $result['body']);
        }

        $this->store->put($data['access_token'], (int) ($data['expires_in'] ?? 3599));

        return $data['access_token'];
    }
}
