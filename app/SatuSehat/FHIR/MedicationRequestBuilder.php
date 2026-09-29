<?php

namespace App\SatuSehat\FHIR;

class MedicationRequestBuilder extends BaseFhirResource
{
    private string  $status           = 'active';
    private string  $intent           = 'original-order';
    private string  $category         = 'outpatient';
    private string  $priority         = 'routine';
    private ?array  $subject          = null;
    private ?string $encounterId      = null;
    private ?string $authoredOn       = null;
    private ?array  $requester        = null;
    private array   $dosageInstructions = [];
    private ?array  $dispenseRequest  = null;

    // Medication (contained)
    private string  $medContainedId   = 'medication-1';
    private string  $kfaCode          = '';
    private string  $kfaDisplay       = '';
    private string  $prescriptionNo   = '';

    public function setMedication(string $kfaCode, string $kfaDisplay, string $prescriptionNo = ''): self
    {
        $this->kfaCode       = $kfaCode;
        $this->kfaDisplay    = $kfaDisplay;
        $this->prescriptionNo = $prescriptionNo;
        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setCategory(string $category = 'outpatient'): self
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

    public function setAuthoredOn(string $datetime): self
    {
        $this->authoredOn = $datetime;
        return $this;
    }

    public function setRequester(string $practitionerId): self
    {
        $this->requester = ['reference' => "Practitioner/{$practitionerId}"];
        return $this;
    }

    public function addDosageInstruction(int $frequency, int $period, string $periodUnit, float $doseValue, string $doseUnit = 'TAB', string $routeCode = 'oral', string $routeDisplay = 'Oral'): self
    {
        $this->dosageInstructions[] = [
            'sequence'  => count($this->dosageInstructions) + 1,
            'timing'    => [
                'repeat' => [
                    'frequency'  => $frequency,
                    'period'     => $period,
                    'periodUnit' => $periodUnit,
                ],
            ],
            'route' => [
                'coding' => [
                    [
                        'system'  => 'http://www.whocc.no/atc',
                        'code'    => $routeCode,
                        'display' => $routeDisplay,
                    ],
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

    public function setDispenseRequest(int $quantity, string $unit, int $daysSupply): self
    {
        $this->dispenseRequest = [
            'quantity' => [
                'value'  => $quantity,
                'unit'   => $unit,
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm',
                'code'   => $unit,
            ],
            'expectedSupplyDuration' => [
                'value'  => $daysSupply,
                'unit'   => 'days',
                'system' => 'http://unitsofmeasure.org',
                'code'   => 'd',
            ],
        ];
        return $this;
    }

    private function buildContainedMedication(): array
    {
        $medication = [
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

        return $medication;
    }

    public function toArray(): array
    {
        $data = [
            'resourceType' => 'MedicationRequest',
            'contained'    => [$this->buildContainedMedication()],
            'status'       => $this->status,
            'intent'       => $this->intent,
            'category'     => [
                [
                    'coding' => [
                        [
                            'system'  => 'http://terminology.hl7.org/CodeSystem/medicationrequest-category',
                            'code'    => $this->category,
                            'display' => ucfirst($this->category),
                        ],
                    ],
                ],
            ],
            'priority'             => $this->priority,
            'medicationReference'  => ['reference' => "#{$this->medContainedId}"],
            'subject'              => $this->subject,
            'dosageInstruction'    => $this->dosageInstructions,
        ];

        if (!empty($this->prescriptionNo)) {
            $data['identifier'] = [
                [
                    'system' => "http://sys-ids.kemkes.go.id/prescription/{$this->orgId}",
                    'value'  => $this->prescriptionNo,
                ],
            ];
        }

        if ($this->encounterId !== null) {
            $data['encounter'] = ['reference' => "Encounter/{$this->encounterId}"];
        }

        if ($this->authoredOn !== null) {
            $data['authoredOn'] = $this->authoredOn;
        }

        if ($this->requester !== null) {
            $data['requester'] = $this->requester;
        }

        if ($this->dispenseRequest !== null) {
            $data['dispenseRequest'] = $this->dispenseRequest;
        }

        return $data;
    }
}
