<?php

namespace App\SatuSehat\Http;

use App\SatuSehat\Auth\OAuth2Client;
use App\SatuSehat\Contracts\HttpClientInterface;
use App\SatuSehat\Support\Config;

class FhirClient
{
    public function __construct(
        private readonly Config              $config,
        private readonly HttpClientInterface $http,
        private readonly OAuth2Client        $oauth,
    ) {}

    public function get(string $resource, array $params = []): array
    {
        return $this->execute('GET', $resource, null, $params);
    }

    public function post(string $resource, array $data): array
    {
        return $this->execute('POST', $resource, $data);
    }

    public function put(string $resource, string $id, array $data): array
    {
        return $this->execute('PUT', $resource . '/' . $id, $data);
    }

    private function execute(string $method, string $resource, ?array $body = null, array $params = []): array
    {
        $creds = $this->config->credentials();
        $url   = rtrim($creds['fhir_url'], '/') . '/' . ltrim($resource, '/');

        if ($params) {
            $url .= '?' . http_build_query($params);
        }

        $token   = $this->oauth->token();
        $headers = $this->buildHeaders($token);
        $bodyStr = $body !== null ? json_encode($body) : null;

        $result = $this->http->request($method, $url, $headers, $bodyStr);

        if ($result['status'] === 401) {
            $this->oauth->flushToken();
            $token   = $this->oauth->token();
            $headers = $this->buildHeaders($token);
            $result  = $this->http->request($method, $url, $headers, $bodyStr);
        }

        if ($result['status'] >= 400) {
            throw new HttpException($result['status'], $result['body']);
        }

        return json_decode($result['body'], true) ?? [];
    }

    private function buildHeaders(string $token): array
    {
        return [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
    }
}
