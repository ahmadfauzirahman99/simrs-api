<?php

namespace App\SatuSehat\FHIR;

class ClaimBuilder extends BaseFhirResource
{
    private string  $bpjsOrgId          = '';
    private string  $noSep              = '';
    private string  $noBatch            = '';
    private ?array  $patient            = null;
    private ?array  $billablePeriod     = null;
    private ?string $created            = null;
    private ?array  $enterer            = null;
    private ?string $locationId         = null;
    private ?string $coverageId         = null;
    private ?string $invoiceId          = null;
    private ?string $encounterId        = null;
    private ?int    $total              = null;
    private array   $diagnoses          = [];
    private array   $procedures         = [];
    private string  $upgradeClass       = 'no-upgrade';
    private string  $upgradeClassDisplay = 'Tidak Ada Kenaikan Kelas';
    private string  $grouperVersion     = '4';
    private string  $eklaimVersion      = '5.8.3';
    private string  $tariffClass        = 'AP';
    private string  $tariffClassDisplay = 'Tarif Kelas A Pemerintah';
    private string  $claimSubType       = 'drg';
    private ?string $inacbgCode         = null;
    private ?string $inacbgDisplay      = null;
    private ?int    $inacbgValue        = null;

    public function __construct(string $orgId, string $bpjsOrgId)
    {
        parent::__construct($orgId);
        $this->bpjsOrgId = $bpjsOrgId;
    }

    public function setIdentifiers(string $noSep, string $noBatch): self
    {
        $this->noSep   = $noSep;
        $this->noBatch = $noBatch;
        return $this;
    }

    public function setPatient(string $patientId): self
    {
        $this->patient = ['reference' => "Patient/{$patientId}"];
        return $this;
    }

    public function setBillablePeriod(string $start, string $end): self
    {
        $this->billablePeriod = ['start' => $start, 'end' => $end];
        return $this;
    }

    public function setCreated(string $datetime): self
    {
        $this->created = $datetime;
        return $this;
    }

    public function setEnterer(string $practitionerId, string $nik): self
    {
        $this->enterer = [
            'identifier' => [
                'system' => 'https://fhir.kemkes.go.id/id/nik',
                'value'  => $nik,
            ],
            'reference' => "Practitioner/{$practitionerId}",
        ];
        return $this;
    }

    public function setLocation(string $locationId): self
    {
        $this->locationId = $locationId;
        return $this;
    }

    public function setCoverage(string $coverageId): self
    {
        $this->coverageId = $coverageId;
        return $this;
    }

    public function setInvoice(string $invoiceId): self
    {
        $this->invoiceId = $invoiceId;
        return $this;
    }

    public function setEncounterRef(string $encounterId): self
    {
        $this->encounterId = $encounterId;
        return $this;
    }

    public function setTotal(int $amount): self
    {
        $this->total = $amount;
        return $this;
    }

    public function setInacbg(string $code, string $display, int $value): self
    {
        $this->inacbgCode    = $code;
        $this->inacbgDisplay = $display;
        $this->inacbgValue   = $value;
        return $this;
    }

    public function setUpgradeClass(string $code, string $display): self
    {
        $this->upgradeClass        = $code;
        $this->upgradeClassDisplay = $display;
        return $this;
    }

    public function setGrouperVersion(string $version): self
    {
        $this->grouperVersion = $version;
        return $this;
    }

    public function setEklaimVersion(string $version): self
    {
        $this->eklaimVersion = $version;
        return $this;
    }

    public function setTariffClass(string $code, string $display): self
    {
        $this->tariffClass        = $code;
        $this->tariffClassDisplay = $display;
        return $this;
    }

    public function setSubType(string $subType): self
    {
        $this->claimSubType = $subType;
        return $this;
    }

    public function addDiagnosis(int $sequence, string $icd10Code, string $display, string $type = 'discharge'): self
    {
        $typeMap = [
            'discharge' => ['code' => 'discharge', 'display' => 'Discharge Diagnosis'],
            'admitting' => ['code' => 'admitting', 'display' => 'Admitting Diagnosis'],
        ];
        $t = $typeMap[$type] ?? $typeMap['discharge'];

        $this->diagnoses[] = [
            'sequence'                   => $sequence,
            'diagnosisCodeableConcept'   => [
                'coding' => [
                    [
                        'system'  => 'http://hl7.org/fhir/sid/icd-10',
                        'code'    => $icd10Code,
                        'display' => $display,
                    ],
                ],
            ],
            'type' => [
                [
                    'coding' => [
                        [
                            'system'  => 'http://terminology.hl7.org/CodeSystem/ex-diagnosistype',
                            'code'    => $t['code'],
                            'display' => $t['display'],
                        ],
                    ],
                ],
            ],
        ];
        return $this;
    }

    public function addProcedure(int $sequence, string $icd9cmCode, string $display): self
    {
        $this->procedures[] = [
            'sequence'                     => $sequence,
            'procedureCodeableConcept'     => [
                'coding' => [
                    [
                        'system'  => 'http://hl7.org/fhir/sid/icd-9-cm',
                        'code'    => $icd9cmCode,
                        'display' => $display,
                    ],
                ],
            ],
        ];
        return $this;
    }

    public function toArray(): array
    {
        $supportingInfo = [
            [
                'sequence'  => 1,
                'category'  => $this->siCategory('upgrade-class-indicator', 'Indikator Naik Kelas'),
                'code'      => [
                    'coding' => [
                        [
                            'system'  => 'http://terminology.kemkes.go.id/CodeSystem/upgradeClassIndicator',
                            'code'    => $this->upgradeClass,
                            'display' => $this->upgradeClassDisplay,
                        ],
                    ],
                ],
            ],
            [
                'sequence'    => 2,
                'category'    => $this->siCategory('unu-grouper-version', 'Versi Grouper INACBG'),
                'valueString' => $this->grouperVersion,
            ],
            [
                'sequence'    => 3,
                'category'    => $this->siCategory('e-klaim-version', 'Versi Aplikasi E-Klaim'),
                'valueString' => $this->eklaimVersion,
            ],
        ];

        if ($this->invoiceId !== null) {
            $supportingInfo[] = [
                'sequence'       => 4,
                'category'       => $this->siCategory('invoice', 'Invoice'),
                'valueReference' => ['reference' => "Invoice/{$this->invoiceId}"],
            ];
        }

        if ($this->encounterId !== null) {
            $supportingInfo[] = [
                'sequence'       => count($supportingInfo) + 1,
                'category'       => $this->siCategory('encounter', 'Encounter'),
                'valueReference' => ['reference' => "Encounter/{$this->encounterId}"],
            ];
        }

        $data = [
            'resourceType'  => 'Claim',
            'identifier'    => [
                [
                    'system' => "http://sys-ids.kemkes.go.id/claim-batch-number/{$this->bpjsOrgId}",
                    'value'  => $this->noBatch,
                ],
                [
                    'system' => "http://sys-ids.kemkes.go.id/claim-number/{$this->bpjsOrgId}",
                    'value'  => $this->noSep,
                ],
            ],
            'status'         => 'active',
            'type'           => [
                'coding' => [
                    [
                        'system'  => 'http://terminology.hl7.org/CodeSystem/claim-type',
                        'code'    => 'institutional',
                        'display' => 'Institutional',
                    ],
                ],
            ],
            'subType' => [
                'coding' => [
                    [
                        'system'  => 'http://terminology.kemkes.go.id',
                        'code'    => $this->claimSubType,
                        'display' => strtoupper($this->claimSubType),
                    ],
                ],
            ],
            'use'            => 'claim',
            'patient'        => $this->patient,
            'billablePeriod' => $this->billablePeriod,
            'created'        => $this->created,
            'insurer'        => ['reference' => "Organization/{$this->bpjsOrgId}"],
            'provider'       => ['reference' => "Organization/{$this->orgId}"],
            'priority'       => [
                'coding' => [
                    [
                        'system'  => 'http://terminology.hl7.org/CodeSystem/processpriority',
                        'code'    => 'normal',
                        'display' => 'Normal',
                    ],
                ],
            ],
            'payee' => [
                'type'  => [
                    'coding' => [
                        [
                            'system'  => 'http://terminology.hl7.org/CodeSystem/payeetype',
                            'code'    => 'provider',
                            'display' => 'Provider',
                        ],
                    ],
                ],
                'party' => ['reference' => "Organization/{$this->orgId}"],
            ],
            'supportingInfo' => $supportingInfo,
            'diagnosis'      => $this->diagnoses,
            'insurance'      => [
                [
                    'sequence' => 1,
                    'focal'    => true,
                    'coverage' => ['reference' => "Coverage/{$this->coverageId}"],
                    'identifier' => [
                        'system' => "http://sys-ids.kemkes.go.id/claim-number/{$this->bpjsOrgId}",
                        'value'  => $this->noSep,
                    ],
                ],
            ],
        ];

        if ($this->enterer !== null) {
            $data['enterer'] = $this->enterer;
        }

        if ($this->locationId !== null) {
            $data['facility'] = ['reference' => "Location/{$this->locationId}"];
        }

        if ($this->procedures !== []) {
            $data['procedure'] = $this->procedures;
        }

        if ($this->total !== null) {
            $data['total'] = ['value' => $this->total, 'currency' => 'IDR'];
        }

        if ($this->inacbgCode !== null) {
            $data['extension'] = [
                [
                    'url'       => 'https://fhir.kemkes.go.id/r4/StructureDefinition/Package',
                    'extension' => [
                        [
                            'url'                => 'tariffClass',
                            'valueCodeableConcept' => [
                                'coding' => [
                                    [
                                        'system'  => 'http://terminology.kemkes.go.id/CodeSystem/tariffClass',
                                        'code'    => $this->tariffClass,
                                        'display' => $this->tariffClassDisplay,
                                    ],
                                ],
                            ],
                        ],
                        [
                            'url'       => 'basePackage',
                            'extension' => [
                                [
                                    'url'                => 'code',
                                    'valueCodeableConcept' => [
                                        'coding' => [
                                            [
                                                'system'  => 'http://terminology.kemkes.go.id/CodeSystem/inacbg-v5',
                                                'code'    => $this->inacbgCode,
                                                'display' => $this->inacbgDisplay,
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'url'          => 'value',
                                    'valueInteger' => $this->inacbgValue,
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        return $data;
    }

    private function siCategory(string $code, string $display): array
    {
        return [
            'coding' => [
                [
                    'system'  => 'http://terminology.kemkes.go.id/CodeSystem/claiminformationcategory',
                    'code'    => $code,
                    'display' => $display,
                ],
            ],
        ];
    }
}
