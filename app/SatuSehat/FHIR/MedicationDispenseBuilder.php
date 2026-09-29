<?php

namespace App\SatuSehat\FHIR;

class MedicationDispenseBuilder extends BaseFhirResource
{
    private string  $status            = 'completed';
    private string  $category          = 'outpatient';
    private ?array  $subject           = null;
    private ?string $encounterId       = null;
    private ?string $medicationRequestId = null;
    private ?array  $performer         = null;
    private ?string $whenHandedOver    = null;
    private ?array  $quantity          = null;
    private ?array  $daysSupply        = null;
    private array   $dosageInstructions = [];
    private string  $dispensingNo      = '';

    // Medication (contained)
    private string  $medContainedId    = 'medication-1';
    private string  $kfaCode           = '';
    private string  $kfaDisplay        = '';

    public function setMedication(string $kfaCode, string $kfaDisplay): self
    {
        $this->kfaCode    = $kfaCode;
        $this->kfaDisplay = $kfaDisplay;
        return $this;
    }

    public function setDispensingNo(string $no): self
    {
        $this->dispensingNo = $no;
        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function setPatient(string $patientId): self
    {
        $this->subject = ['reference' => "Patient/{$patientId}"];
        return $this;
    }

    public function setEncounter(string $encounterId): self
    {
        $this->encounterId = $encounterId;
        return $this;
    }

    public function setMedicationRequest(string $medRequestId): self
    {
        $this->medicationRequestId = $medRequestId;
        return $this;
    }

    public function setPerformer(string $practitionerId): self
    {
        $this->performer = [
            ['actor' => ['reference' => "Practitioner/{$practitionerId}"]],
        ];
        return $this;
    }

    public function setWhenHandedOver(string $datetime): self
    {
        $this->whenHandedOver = $datetime;
        return $this;
    }

    public function setQuantity(float $value, string $unit = 'TAB'): self
    {
        $this->quantity = [
            'value'  => $value,
            'unit'   => $unit,
            'system' => 'http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm',
            'code'   => $unit,
        ];
        return $this;
    }

    public function setDaysSupply(int $days): self
    {
        $this->daysSupply = [
            'value'  => $days,
            'unit'   => 'days',
            'system' => 'http://unitsofmeasure.org',
            'code'   => 'd',
        ];
        return $this;
    }

    public function addDosageInstruction(int $frequency, int $period, string $periodUnit, float $doseValue, string $doseUnit = 'TAB'): self
    {
        $this->dosageInstructions[] = [
            'sequence' => count($this->dosageInstructions) + 1,
            'timing'   => [
                'repeat' => [
                    'frequency'  => $frequency,
                    'period'     => $period,
                    'periodUnit' => $periodUnit,
                ],
            ],
            'doseAndRate' => [
                [
                    'doseQuantity' => [
                        'value'  => $doseValue,
                        'unit'   => $doseUnit,
                        'system' => 'http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm',
                        'code'   => $doseUnit,
                    ],
                ],
            ],
        ];
        return $this;
    }

    private function buildContainedMedication(): array
    {
        return [
            'resourceType' => 'Medication',
            'id'           => $this->medContainedId,
            'meta'         => [
                'profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/Medication'],
            ],
            'code' => [
                'coding' => [
                    [
                        'system'  => 'http://sys-ids.kemkes.go.id/kfa',
                        'code'    => $this->kfaCode,
                        'display' => $this->kfaDisplay,
                    ],
                ],
            ],
            'status'       => 'active',
            'manufacturer' => ['reference' => "Organization/{$this->orgId}"],
        ];
    }

    public function toArray(): array
    {
        $data = [
            'resourceType'     => 'MedicationDispense',
            'contained'        => [$this->buildContainedMedication()],
            'status'           => $this->status,
            'category'         => [
                'coding' => [
                    [
                        'system'  => 'http://terminology.kemkes.go.id/CodeSystem/medication-category',
                        'code'    => $this->category,
                        'display' => ucfirst($this->category),
                    ],
                ],
            ],
            'medicationReference' => ['reference' => "#{$this->medContainedId}"],
            'subject'             => $this->subject,
        ];

        if (!empty($this->dispensingNo)) {
            $data['identifier'] = [
                [
                    'system' => "http://sys-ids.kemkes.go.id/dispensing/{$this->orgId}",
                    'value'  => $this->dispensingNo,
                ],
            ];
        }

        if ($this->encounterId !== null) {
            $data['context'] = ['reference' => "Encounter/{$this->encounterId}"];
        }

        if ($this->performer !== null) {
            $data['performer'] = $this->performer;
        }

        if ($this->medicationRequestId !== null) {
            $data['authorizingPrescription'] = [
                ['reference' => "MedicationRequest/{$this->medicationRequestId}"],
            ];
        }

        if ($this->quantity !== null) {
            $data['quantity'] = $this->quantity;
        }

        if ($this->daysSupply !== null) {
            $data['daysSupply'] = $this->daysSupply;
        }

        if ($this->whenHandedOver !== null) {
            $data['whenHandedOver'] = $this->whenHandedOver;
        }

        if ($this->dosageInstructions !== []) {
            $data['dosageInstruction'] = $this->dosageInstructions;
        }

        return $data;
    }
}
