<?php

namespace App\SatuSehat\Support;

use RuntimeException;

class Config
{
    public function __construct(private readonly array $config) {}

    public function env(): string
    {
        return strtoupper((string) ($this->config['env'] ?? 'DEV'));
    }

    /**
     * Returns the credentials for the active environment.
     *
     * @return array{auth_url: string, fhir_url: string, client_id: string, client_secret: string, org_id: string}
     * @throws RuntimeException if any required field is empty.
     */
    public function credentials(): array
    {
        $env  = strtolower($this->env());
        $data = $this->config[$env] ?? [];

        foreach (['auth_url', 'fhir_url', 'client_id', 'client_secret', 'org_id'] as $key) {
            if (empty($data[$key])) {
                throw new RuntimeException(
                    "Satu Sehat config [{$env}.{$key}] is missing or empty. Check your .env file."
                );
            }
        }

        return $data;
    }
}
