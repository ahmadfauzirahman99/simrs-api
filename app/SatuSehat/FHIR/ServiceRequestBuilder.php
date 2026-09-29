<?php

namespace App\SatuSehat\FHIR;

class ServiceRequestBuilder extends BaseFhirResource
{
    private string  $status              = 'active';
    private string  $intent              = 'original-order';
    private ?string $priority            = null;
    private array   $codes               = [];
    private ?array  $subject             = null;
    private ?string $encounterId         = null;
    private ?array  $requester           = null;
    private array   $reasonReferences    = [];
    private ?string $occurrenceDateTime  = null;

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setIntent(string $intent = 'original-order'): self
    {
        $this->intent = $intent;
        return $this;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function addCode(string $code, string $display): self
    {
        $this->codes[] = [
            'system'  => 'http://snomed.info/sct',
            'code'    => $code,
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

    public function setRequester(string $practitionerId, string $name): self
    {
        $this->requester = [
            'reference' => "Practitioner/{$practitionerId}",
            'display'   => $name,
        ];
        return $this;
    }

    public function addReasonReference(string $conditionId): self
    {
        $this->reasonReferences[] = [
            'reference' => "Condition/{$conditionId}",
        ];
        return $this;
    }

    public function setOccurrenceDateTime(string $dateTime): self
    {
        $this->occurrenceDateTime = $dateTime;
        return $this;
    }

    public function toArray(): array
    {
        $data = [
            'resourceType' => 'ServiceRequest',
            'status'       => $this->status,
            'intent'       => $this->intent,
        ];

        if ($this->priority !== null) {
            $data['priority'] = $this->priority;
        }

        if ($this->codes !== []) {
            $data['code'] = ['coding' => $this->codes];
        }

        if ($this->subject !== null) {
            $data['subject'] = $this->subject;
        }

        if ($this->encounterId !== null) {
            $data['encounter'] = ['reference' => "Encounter/{$this->encounterId}"];
        }

        if ($this->occurrenceDateTime !== null) {
            $data['occurrenceDateTime'] = $this->occurrenceDateTime;
        }

        if ($this->requester !== null) {
            $data['requester'] = $this->requester;
        }

        if ($this->reasonReferences !== []) {
            $data['reasonReference'] = $this->reasonReferences;
        }

        return $data;
    }
}
