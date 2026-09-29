<?php

namespace App\SatuSehat\FHIR;

abstract class BaseFhirResource
{
    public function __construct(protected readonly string $orgId) {}

    abstract public function toArray(): array;

    public function json(): string
    {
        return (string) json_encode($this->toArray());
    }
}
