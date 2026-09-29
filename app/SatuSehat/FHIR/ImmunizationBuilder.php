<?php

namespace App\SatuSehat\FHIR;

class ImmunizationBuilder extends BaseFhirResource
{
    private string  $status             = 'completed';
    private ?array  $vaccineCode        = null;
    private ?array  $patient            = null;
    private ?string $encounterId        = null;
    private ?string $occurrenceDateTime = null;
    private ?string $locationId         = null;
    private array   $performers         = [];
    private ?string $lotNumber          = null;
    private ?array  $site               = null;
    private ?array  $route              = null;
    private ?array  $doseQuantity       = null;
    private string  $immunizationNo     = '';

    public function setImmunizationNo(string $no): self
    {
        $this->immunizationNo = $no;
        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setVaccine(string $kfaCode, string $display): self
    {
        $this->vaccineCode = [
            'coding' => [
                [
                    'system'  => 'http://sys-ids.kemkes.go.id/kfa',
                    'code'    => $kfaCode,
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

    public function setOccurrenceDateTime(string $datetime): self
    {
        $this->occurrenceDateTime = $datetime;
        return $this;
    }

    public function setLocation(string $locationId): self
    {
        $this->locationId = $locationId;
        return $this;
    }

    public function addPerformer(string $practitionerId): self
    {
        $this->performers[] = [
            'actor' => ['reference' => "Practitioner/{$practitionerId}"],
        ];
        return $this;
    }

    public function setLotNumber(string $lot): self
    {
        $this->lotNumber = $lot;
        return $this;
    }

    public function setSite(string $code, string $display): self
    {
        $this->site = [
            'coding' => [
                [
                    'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ActSite',
                    'code'    => $code,
                    'display' => $display,
                ],
            ],
        ];
        return $this;
    }

    public function setRoute(string $code, string $display): self
    {
        $this->route = [
            'coding' => [
                [
                    'system'  => 'http://terminology.hl7.org/CodeSystem/v3-RouteOfAdministration',
                    'code'    => $code,
                    'display' => $display,
                ],
            ],
        ];
        return $this;
    }

    public function setDoseQuantity(float $value, string $unit = 'mL'): self
    {
        $this->doseQuantity = [
            'value'  => $value,
            'unit'   => $unit,
            'system' => 'http://unitsofmeasure.org',
            'code'   => $unit,
        ];
        return $this;
    }

    public function toArray(): array
    {
        $data = [
            'resourceType'       => 'Immunization',
            'status'             => $this->status,
            'vaccineCode'        => $this->vaccineCode,
            'patient'            => $this->patient,
            'occurrenceDateTime' => $this->occurrenceDateTime,
        ];

        if (!empty($this->immunizationNo)) {
            $data['identifier'] = [
                [
                    'system' => "http://sys-ids.kemkes.go.id/immunization/{$this->orgId}",
                    'value'  => $this->immunizationNo,
                ],
            ];
        }

        if ($this->encounterId !== null) {
            $data['encounter'] = ['reference' => "Encounter/{$this->encounterId}"];
        }

        if ($this->locationId !== null) {
            $data['location'] = ['reference' => "Location/{$this->locationId}"];
        }

        if ($this->performers !== []) {
            $data['performer'] = $this->performers;
        }

        if ($this->lotNumber !== null) {
            $data['lotNumber'] = $this->lotNumber;
        }

        if ($this->site !== null) {
            $data['site'] = $this->site;
        }

        if ($this->route !== null) {
            $data['route'] = $this->route;
        }

        if ($this->doseQuantity !== null) {
            $data['doseQuantity'] = $this->doseQuantity;
        }

        return $data;
    }
}
