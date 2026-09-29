<?php

namespace App\SatuSehat\FHIR;

class ObservationBuilder extends BaseFhirResource
{
    private string  $status           = 'final';
    private array   $categories       = [];
    private ?array  $code             = null;
    private ?array  $subject          = null;
    private ?string $encounterId      = null;
    private ?string $effectiveDateTime = null;
    private ?array  $valueQuantity    = null;
    private ?array  $valueCodeable    = null;
    private ?string $valueString      = null;
    private array   $interpretations  = [];
    private array   $referenceRanges  = [];
    private array   $components       = [];

    private static array $loincDisplayMap = [
        '8480-6'   => 'Systolic blood pressure',
        '8462-4'   => 'Diastolic blood pressure',
        '8867-4'   => 'Heart rate',
        '8310-5'   => 'Body temperature',
        '9279-1'   => 'Respiratory rate',
        '2708-6'   => 'Oxygen saturation',
        '29463-7'  => 'Body weight',
        '8302-2'   => 'Body height',
        '39156-5'  => 'BMI',
    ];

    private static array $categorySystemMap = [
        'vital-signs'  => 'http://terminology.hl7.org/CodeSystem/observation-category',
        'laboratory'   => 'http://terminology.hl7.org/CodeSystem/observation-category',
        'imaging'      => 'http://terminology.hl7.org/CodeSystem/observation-category',
        'procedure'    => 'http://terminology.hl7.org/CodeSystem/observation-category',
        'survey'       => 'http://terminology.hl7.org/CodeSystem/observation-category',
        'exam'         => 'http://terminology.hl7.org/CodeSystem/observation-category',
    ];

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function addCategory(string $category): self
    {
        $system = self::$categorySystemMap[$category]
            ?? 'http://terminology.hl7.org/CodeSystem/observation-category';

        $this->categories[] = [
            'coding' => [
                [
                    'system' => $system,
                    'code'   => $category,
                ],
            ],
        ];
        return $this;
    }

    public function addCode(string $loincCode): self
    {
        $display = self::$loincDisplayMap[$loincCode] ?? null;

        $coding = [
            'system' => 'http://loinc.org',
            'code'   => $loincCode,
        ];

        if ($display !== null) {
            $coding['display'] = $display;
        }

        $this->code = ['coding' => [$coding]];
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

    public function setEffectiveDateTime(string $dateTime): self
    {
        $this->effectiveDateTime = $dateTime;
        return $this;
    }

    public function setValueQuantity(float $value, string $unit, string $system = 'http://unitsofmeasure.org'): self
    {
        $this->valueQuantity = [
            'value'  => $value,
            'unit'   => $unit,
            'system' => $system,
            'code'   => $unit,
        ];
        return $this;
    }

    public function setValueCodeableConcept(string $code, string $display, string $system): self
    {
        $this->valueCodeable = [
            'coding' => [
                [
                    'system'  => $system,
                    'code'    => $code,
                    'display' => $display,
                ],
            ],
        ];
        return $this;
    }

    public function setValueString(string $value): self
    {
        $this->valueString = $value;
        return $this;
    }

    public function addInterpretation(string $code, string $display): self
    {
        $this->interpretations[] = [
            'coding' => [
                [
                    'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ObservationInterpretation',
                    'code'    => $code,
                    'display' => $display,
                ],
            ],
        ];
        return $this;
    }

    public function addReferenceRange(float $low, float $high, string $unit): self
    {
        $this->referenceRanges[] = [
            'low'  => ['value' => $low,  'unit' => $unit],
            'high' => ['value' => $high, 'unit' => $unit],
        ];
        return $this;
    }

    public function addComponent(string $loincCode, array $valueQuantity): self
    {
        $display = self::$loincDisplayMap[$loincCode] ?? null;

        $coding = [
            'system' => 'http://loinc.org',
            'code'   => $loincCode,
        ];
        if ($display !== null) {
            $coding['display'] = $display;
        }

        $this->components[] = [
            'code'          => ['coding' => [$coding]],
            'valueQuantity' => $valueQuantity,
        ];
        return $this;
    }

    public function toArray(): array
    {
        $data = [
            'resourceType' => 'Observation',
            'status'       => $this->status,
        ];

        if ($this->categories !== []) {
            $data['category'] = $this->categories;
        }

        if ($this->code !== null) {
            $data['code'] = $this->code;
        }

        if ($this->subject !== null) {
            $data['subject'] = $this->subject;
        }

        if ($this->encounterId !== null) {
            $data['encounter'] = ['reference' => "Encounter/{$this->encounterId}"];
        }

        if ($this->effectiveDateTime !== null) {
            $data['effectiveDateTime'] = $this->effectiveDateTime;
        }

        if ($this->valueQuantity !== null) {
            $data['valueQuantity'] = $this->valueQuantity;
        } elseif ($this->valueCodeable !== null) {
            $data['valueCodeableConcept'] = $this->valueCodeable;
        } elseif ($this->valueString !== null) {
            $data['valueString'] = $this->valueString;
        }

        if ($this->interpretations !== []) {
            $data['interpretation'] = $this->interpretations;
        }

        if ($this->referenceRanges !== []) {
            $data['referenceRange'] = $this->referenceRanges;
        }

        if ($this->components !== []) {
            $data['component'] = $this->components;
        }

        return $data;
    }
}
