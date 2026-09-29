<?php

namespace App\SatuSehat\Contracts;

interface TokenStoreInterface
{
    /**
     * Retrieve the cached token record, or null if expired/missing.
     *
     * @return array{token: string, expires_at: int}|null
     */
    public function get(): ?array;

    /**
     * Persist a new token with its lifetime.
     */
    public function put(string $token, int $expiresIn): void;

    /**
     * Delete the stored token, forcing a fresh fetch.
     */
    public function flush(): void;
}
