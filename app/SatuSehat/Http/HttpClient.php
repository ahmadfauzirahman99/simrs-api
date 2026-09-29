<?php

namespace App\SatuSehat\Http;

use App\SatuSehat\Contracts\HttpClientInterface;
use RuntimeException;

class HttpClient implements HttpClientInterface
{
    public function request(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $method = strtoupper($method);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }

        $responseBody = curl_exec($ch);
        $statusCode   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error        = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException('cURL error: ' . $error);
        }

        return [
            'status' => $statusCode,
            'body'   => $responseBody ?: '',
        ];
    }
}
