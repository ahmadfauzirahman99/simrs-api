<?php

namespace App\SatuSehat\FHIR;

class ProcedureBuilder extends BaseFhirResource
{
    private string  $status       = 'completed';
    private ?array  $category     = null;
    private array   $codes        = [];
    private ?array  $subject      = null;
    private ?string $encounterId  = null;
    private ?array  $performed    = null;
    private array   $performers   = [];
    private ?array  $bodySite     = null;
    private array   $notes        = [];

    public function setStatus(string $status = 'completed'): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Shortcut: set category to "Diagnostic procedure" (SNOMED 103693007).
     */
    public function setCategoryDiagnostic(): self
    {
        $this->category = [
            'coding' => [
                [
                    'system'  => 'http://snomed.info/sct',
                    'code'    => '103693007',
                    'display' => 'Diagnostic procedure',
                ],
            ],
        ];
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

    public function setPerformedPeriod(string $start, ?string $end = null): self
    {
        $this->performed = ['start' => $start];
        if ($end !== null) {
            $this->performed['end'] = $end;
        }
        return $this;
    }

    public function addPerformer(string $practitionerId, string $name): self
    {
        $this->performers[] = [
            'actor' => [
                'reference' => "Practitioner/{$practitionerId}",
                'display'   => $name,
            ],
        ];
        return $this;
    }

    public function setBodySite(string $snomedCode, string $display): self
    {
        $this->bodySite = [
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

    public function addNote(string $text): self
    {
        $this->notes[] = ['text' => $text];
        return $this;
    }

    public function toArray(): array
    {
        $data = [
            'resourceType' => 'Procedure',
            'status'       => $this->status,
        ];

        if ($this->category !== null) {
            $data['category'] = $this->category;
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

        if ($this->performed !== null) {
            $data['performedPeriod'] = $this->performed;
        }

        if ($this->performers !== []) {
            $data['performer'] = $this->performers;
        }

        if ($this->bodySite !== null) {
            $data['bodySite'] = [$this->bodySite];
        }

        if ($this->notes !== []) {
            $data['note'] = $this->notes;
        }

        return $data;
    }
}
