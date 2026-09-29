<?php

namespace App\SatuSehat\FHIR;

use InvalidArgumentException;

class ConditionBuilder extends BaseFhirResource
{
    private string  $clinicalStatus     = 'active';
    private array   $categories         = [];
    private array   $codes              = [];
    private ?array  $subject            = null;
    private ?string $encounterId        = null;
    private ?string $onsetDateTime      = null;
    private ?string $recordedDate       = null;

    private static array $categoryMap = [
        'diagnosis' => ['code' => 'encounter-diagnosis', 'display' => 'Encounter Diagnosis'],
        'keluhan'   => ['code' => 'problem-list-item',   'display' => 'Problem List Item'],
    ];

    public function addClinicalStatus(string $status = 'active'): self
    {
        $this->clinicalStatus = $status;
        return $this;
    }

    public function addCategory(string $category): self
    {
        $entry = self::$categoryMap[strtolower($category)] ?? null;

        if ($entry === null) {
            throw new InvalidArgumentException(
                "Invalid category: {$category}. Accepted: diagnosis, keluhan"
            );
        }

        $this->categories[] = [
            'coding' => [
                [
                    'system'  => 'http://terminology.hl7.org/CodeSystem/condition-category',
                    'code'    => $entry['code'],
                    'display' => $entry['display'],
                ],
            ],
        ];

        return $this;
    }

    public function addCode(string $icd10Code, string $display): self
    {
        $this->codes[] = [
            'system'  => 'http://hl7.org/fhir/sid/icd-10',
            'code'    => $icd10Code,
            'display' => $display,
        ];
        return $this;
    }

    public function setSubject(string $patientId, string $name): self
    {
        $this->subject = [
            'reference' => "Patient/{$patientId}",
            'display'   => $name,
        ];
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

    public function setRecordedDate(string $dateTime): self
    {
        $this->recordedDate = $dateTime;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'resourceType'       => 'Condition',
            'clinicalStatus'     => [
                'coding' => [
                    [
                        'system'  => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                        'code'    => $this->clinicalStatus,
                        'display' => ucfirst($this->clinicalStatus),
                    ],
                ],
            ],
            'verificationStatus' => [
                'coding' => [
                    [
                        'system'  => 'http://terminology.hl7.org/CodeSystem/condition-ver-status',
                        'code'    => 'confirmed',
                        'display' => 'Confirmed',
                    ],
                ],
            ],
            'category'     => $this->categories !== [] ? $this->categories : $this->defaultCategory(),
            'code'         => ['coding' => $this->codes],
            'subject'      => $this->subject,
            'encounter'    => $this->encounterId !== null
                ? ['reference' => "Encounter/{$this->encounterId}"]
                : null,
            'onsetDateTime'  => $this->onsetDateTime,
            'recordedDate'   => $this->recordedDate,
        ];
    }

    public function json(): string
    {
        if ($this->subject === null) {
            throw new \RuntimeException('ConditionBuilder: subject is required before calling json().');
        }
        if ($this->encounterId === null) {
            throw new \RuntimeException('ConditionBuilder: encounter is required before calling json().');
        }

        return (string) json_encode(array_filter($this->toArray(), fn($v) => $v !== null));
    }

    private function defaultCategory(): array
    {
        return [
            [
                'coding' => [
                    [
                        'system'  => 'http://terminology.hl7.org/CodeSystem/condition-category',
                        'code'    => 'encounter-diagnosis',
                        'display' => 'Encounter Diagnosis',
                    ],
                ],
            ],
        ];
    }
}
