<?php

namespace App\SatuSehat\FHIR;

class CoverageBuilder extends BaseFhirResource
{
    private string $bpjsOrgId;
    private string $status        = 'active';
    private string $subscriberId  = '';
    private ?array $beneficiary   = null;
    private string $kelas         = '1';
    private string $ppuType       = 'ppu-pegawai-swasta';
    private string $ppuName       = 'Pegawai Swasta';

    public function __construct(string $orgId, string $bpjsOrgId)
    {
        parent::__construct($orgId);
        $this->bpjsOrgId = $bpjsOrgId;
    }

    public function setNomorKartu(string $nomorKartu): self
    {
        $this->subscriberId = $nomorKartu;
        return $this;
    }

    public function setBeneficiary(string $patientId): self
    {
        $this->beneficiary = ['reference' => "Patient/{$patientId}"];
        return $this;
    }

    public function setKelas(string $kelas): self
    {
        $this->kelas = $kelas;
        return $this;
    }

    public function setPpu(string $type, string $name): self
    {
        $this->ppuType = $type;
        $this->ppuName = $name;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'resourceType' => 'Coverage',
            'identifier'   => [
                [
                    'system' => "https://sys-ids.kemkes.go.id/insurance-subscriber/{$this->bpjsOrgId}",
                    'value'  => $this->subscriberId,
                ],
            ],
            'status'     => $this->status,
            'type'       => [
                'coding' => [
                    [
                        'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                        'code'    => 'PUBLICPOL',
                        'display' => 'public healthcare',
                    ],
                ],
            ],
            'subscriberId' => $this->subscriberId,
            'beneficiary'  => $this->beneficiary,
            'payor'        => [
                [
                    'reference' => "Organization/{$this->bpjsOrgId}",
                    'display'   => 'BPJS Kesehatan',
                ],
            ],
            'class' => [
                [
                    'type' => [
                        'coding' => [
                            [
                                'system'  => 'http://terminology.hl7.org/CodeSystem/coverage-class',
                                'code'    => 'group',
                                'display' => 'Group',
                            ],
                        ],
                    ],
                    'value' => $this->ppuType,
                    'name'  => $this->ppuName,
                ],
                [
                    'type' => [
                        'coding' => [
                            [
                                'system'  => 'http://terminology.hl7.org/CodeSystem/coverage-class',
                                'code'    => 'class',
                                'display' => 'Class',
                            ],
                        ],
                    ],
                    'value' => $this->kelas,
                    'name'  => "Kelas {$this->kelas}",
                ],
            ],
            'extension' => [
                [
                    'url'            => 'http://fhir.kemkes.go.id/r4/structureDefinition/primaryCareProvider',
                    'valueReference' => ['reference' => "Organization/{$this->orgId}"],
                ],
            ],
        ];
    }
}
