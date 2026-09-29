<?php

namespace App\SatuSehat\Contracts;

interface HttpClientInterface
{
    /**
     * @param  string        $method   GET|POST|PUT
     * @param  string        $url
     * @param  string[]      $headers  Format: ['Name: value']
     * @param  string|null   $body
     * @return array{status: int, body: string}
     */
    public function request(string $method, string $url, array $headers = [], ?string $body = null): array;
}
