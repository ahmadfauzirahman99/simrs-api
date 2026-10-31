<?php

namespace App\Support\Bpjs;

class BpjsMockRegistry
{
    protected static ?array $lookupTable = null;

    /**
     * Cari mock response berdasarkan path dan method HTTP.
     * Mengembalikan array response siap pakai jika ditemukan.
     */
    public static function findMock(string $path, string $method = 'GET'): ?array
    {
        self::ensureLoaded();

        $normalizedPath = '/' . ltrim(strtolower($path), '/');
        $normalizedMethod = strtoupper($method);

        // 1. Exact match
        $key = "{$normalizedMethod}:{$normalizedPath}";
        if (isset(self::$lookupTable[$key])) {
            return self::$lookupTable[$key];
        }

        // 2. Fallback prefix/pattern match
        foreach (self::$lookupTable as $storedKey => $response) {
            [$storedMethod, $storedPath] = explode(':', $storedKey, 2);
            if ($storedMethod !== $normalizedMethod && $storedMethod !== 'ANY') {
                continue;
            }

            // Jika path adalah prefix (contoh /api/v1/bpjs/peserta cocok dengan request)
            if ($normalizedPath === $storedPath || str_starts_with($normalizedPath, $storedPath . '/')) {
                return $response;
            }
        }

        return null;
    }

    /**
     * Apakah kredensial BPJS di server masih kosong atau masih dummy template bawaan (.env.example)?
     */
    public static function isConfigured(): bool
    {
        $baseUrl = (string) config('services.bpjs_v3.base_url');
        $consId = (string) config('services.bpjs_v3.cons_id');
        $secretKey = (string) config('services.bpjs_v3.secret_key');

        if (empty($baseUrl) || empty($consId) || empty($secretKey)) {
            return false;
        }

        // Cek jika masih berisi nilai placeholder template
        $placeholders = ['URL_BPJS_V3', 'CONS_ID_BPJS_V3', 'SECRET_KEY_BP', 'USER_KEY_BPJS_V3'];
        foreach ($placeholders as $ph) {
            if (str_contains($baseUrl, $ph) || $consId === $ph || $secretKey === $ph) {
                return false;
            }
        }

        // Base URL harus berupa URL HTTP/HTTPS valid
        if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            return false;
        }

        return true;
    }

    protected static function ensureLoaded(): void
    {
        if (self::$lookupTable !== null) {
            return;
        }

        self::$lookupTable = [];
        $catalog = new BpjsTesterCatalog();

        foreach ($catalog->groups() as $group) {
            foreach ($group['items'] ?? [] as $item) {
                $path = '/' . ltrim(strtolower($item['path'] ?? ''), '/');
                $method = strtoupper($item['method'] ?? 'GET');
                $mock = $item['mockResponses']['success'] ?? null;

                if (!empty($path) && !empty($mock)) {
                    self::$lookupTable["{$method}:{$path}"] = $mock;
                }
            }
        }
    }
}
