<?php

namespace App\SatuSehat\FHIR;

class AccountBuilder extends BaseFhirResource
{
    private string $status      = 'active';
    private string $name        = 'Akun Billing Pasien';
    private string $description = 'Akun Billing Pasien untuk Tarif, Pembiayaan, dan Penyesuaian';
    private ?array $subject     = null;
    private ?array $servicePeriod = null;
    private ?string $coverageId = null;

    public function setPatient(string $patientId, string $name): self
    {
        $this->subject = [
            'reference' => "Patient/{$patientId}",
            'display'   => $name,
        ];
        return $this;
    }

    public function setServicePeriod(string $start, string $end): self
    {
        $this->servicePeriod = ['start' => $start, 'end' => $end];
        return $this;
    }

    public function setCoverage(string $coverageId): self
    {
        $this->coverageId = $coverageId;
        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'resourceType' => 'Account',
            'status'       => $this->status,
            'type'         => [
                'coding' => [
                    [
                        'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                        'code'    => 'PBILLACCT',
                        'display' => 'patient billing account',
                    ],
                ],
                'text' => 'patient',
            ],
            'name'          => $this->name,
            'subject'       => [$this->subject],
            'servicePeriod' => $this->servicePeriod,
            'coverage'      => [
                [
                    'coverage' => ['reference' => "Coverage/{$this->coverageId}"],
                    'priority' => 1,
                ],
            ],
            'owner'       => ['reference' => "Organization/{$this->orgId}"],
            'description' => $this->description,
        ];
    }
}
