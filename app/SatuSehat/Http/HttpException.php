<?php

namespace App\SatuSehat\Http;

use RuntimeException;

class HttpException extends RuntimeException
{
    public function __construct(
        private readonly int    $statusCode,
        private readonly string $responseBody,
    ) {
        parent::__construct("Satu Sehat HTTP {$statusCode}");
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): string
    {
        return $this->responseBody;
    }
}
