<?php

namespace App\SatuSehat\FHIR;

class AllergyIntoleranceBuilder extends BaseFhirResource
{
    private string  $clinicalStatus       = 'active';
    private string  $verificationStatus   = 'confirmed';
    private string  $type                 = 'allergy';
    private string  $category             = 'medication';
    private string  $criticality          = 'low';
    private ?array  $code                 = null;
    private ?array  $patient              = null;
    private ?string $encounterId          = null;
    private ?string $onsetDateTime        = null;
    private ?string $recordedDate         = null;
    private array   $reactions            = [];

    private static array $categoryMap = [
        'food'        => 'food',
        'medication'  => 'medication',
        'environment' => 'environment',
        'biologic'    => 'biologic',
    ];

    public function setClinicalStatus(string $status = 'active'): self
    {
        $this->clinicalStatus = $status;
        return $this;
    }

    public function setVerificationStatus(string $status = 'confirmed'): self
    {
        $this->verificationStatus = $status;
        return $this;
    }

    public function setType(string $type = 'allergy'): self
    {
        $this->type = $type;
        return $this;
    }

    public function setCategory(string $category = 'medication'): self
    {
        $this->category = self::$categoryMap[$category] ?? 'medication';
        return $this;
    }

    public function setCriticality(string $criticality = 'low'): self
    {
        $this->criticality = $criticality;
        return $this;
    }

    public function setCode(string $snomedCode, string $display): self
    {
        $this->code = [
            'coding' => [
                [
                    'system'  => 'http://snomed.info/sct',
                    'code'    => $snomedCode,
                    'display' => $display,
                ],
            ],
        ];
        return $this;
    }

    public function setPatient(string $patientId): self
    {
        $this->patient = ['reference' => "Patient/{$patientId}"];
        return $this;
    }

    public function setEncounter(string $encounterId): self
    {
        $this->encounterId = $encounterId;
        return $this;
    }

    public function setOnsetDateTime(string $dateTime): self
    {
        $this->onsetDateTime = $dateTime;
        return $this;
    }

    public function setRecordedDate(string $date): self
    {
        $this->recordedDate = $date;
        return $this;
    }

    public function addReaction(string $substanceCode, string $substanceDisplay, string $manifestationCode, string $manifestationDisplay, string $severity = 'mild'): self
    {
        $this->reactions[] = [
            'substance'     => [
                'coding' => [
                    [
                        'system'  => 'http://snomed.info/sct',
                        'code'    => $substanceCode,
                        'display' => $substanceDisplay,
                    ],
                ],
            ],
            'manifestation' => [
                [
                    'coding' => [
                        [
                            'system'  => 'http://snomed.info/sct',
                            'code'    => $manifestationCode,
                            'display' => $manifestationDisplay,
                        ],
                    ],
                ],
            ],
            'severity' => $severity,
        ];
        return $this;
    }

    public function toArray(): array
    {
        $data = [
            'resourceType'       => 'AllergyIntolerance',
            'clinicalStatus'     => [
                'coding' => [
                    [
                        'system'  => 'http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical',
                        'code'    => $this->clinicalStatus,
                        'display' => ucfirst($this->clinicalStatus),
                    ],
                ],
            ],
            'verificationStatus' => [
                'coding' => [
                    [
                        'system'  => 'http://terminology.hl7.org/CodeSystem/allergyintolerance-verification',
                        'code'    => $this->verificationStatus,
                        'display' => ucfirst($this->verificationStatus),
                    ],
                ],
            ],
            'type'        => $this->type,
            'category'    => [$this->category],
            'criticality' => $this->criticality,
            'code'        => $this->code,
            'patient'     => $this->patient,
        ];

        if ($this->encounterId !== null) {
            $data['encounter'] = ['reference' => "Encounter/{$this->encounterId}"];
        }

        if ($this->onsetDateTime !== null) {
            $data['onsetDateTime'] = $this->onsetDateTime;
        }

        if ($this->recordedDate !== null) {
            $data['recordedDate'] = $this->recordedDate;
        }

        if ($this->reactions !== []) {
            $data['reaction'] = $this->reactions;
        }

        return $data;
    }
}
