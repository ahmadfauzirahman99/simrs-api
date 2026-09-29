<?php

namespace App\SatuSehat\Support;

use App\SatuSehat\Contracts\TokenStoreInterface;

class TokenStore implements TokenStoreInterface
{
    public function __construct(private readonly string $path) {}

    public function get(): ?array
    {
        if (!file_exists($this->path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($this->path), true);

        if (!is_array($data) || empty($data['token'])) {
            return null;
        }

        if (time() >= ($data['expires_at'] ?? 0)) {
            return null;
        }

        return $data;
    }

    public function put(string $token, int $expiresIn): void
    {
        $data = [
            'token'      => $token,
            'expires_at' => time() + $expiresIn - 60, // 60-second buffer
        ];

        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->path, json_encode($data), LOCK_EX);
    }

    public function flush(): void
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }
}
