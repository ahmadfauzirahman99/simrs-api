<?php

namespace App\SatuSehat\FHIR;

class DiagnosticReportBuilder extends BaseFhirResource
{
    private string  $status           = 'final';
    private string  $categorySystem   = 'http://loinc.org';
    private string  $categoryCode     = 'LAB';
    private string  $categoryDisplay  = 'Laboratory';
    private ?array  $code             = null;
    private ?array  $subject          = null;
    private ?string $encounterId      = null;
    private ?string $effectiveDateTime = null;
    private ?string $issued           = null;
    private array   $performers       = [];
    private array   $results          = [];
    private string  $reportNo         = '';

    private static array $categoryPresets = [
        'lab'       => ['system' => 'http://loinc.org', 'code' => 'LAB',  'display' => 'Laboratory'],
        'radiology' => ['system' => 'http://loinc.org', 'code' => 'RAD',  'display' => 'Radiology'],
        'pathology' => ['system' => 'http://loinc.org', 'code' => 'PAT',  'display' => 'Pathology'],
    ];

    public function setReportNo(string $no): self
    {
        $this->reportNo = $no;
        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setCategory(string $preset = 'lab'): self
    {
        $cat = self::$categoryPresets[$preset] ?? self::$categoryPresets['lab'];
        $this->categorySystem  = $cat['system'];
        $this->categoryCode    = $cat['code'];
        $this->categoryDisplay = $cat['display'];
        return $this;
    }

    public function setCode(string $loincCode, string $display): self
    {
        $this->code = [
            'coding' => [
                [
                    'system'  => 'http://loinc.org',
                    'code'    => $loincCode,
                    'display' => $display,
                ],
            ],
        ];
        return $this;
    }

    public function setSubject(string $patientId): self
    {
        $this->subject = ['reference' => "Patient/{$patientId}"];
        return $this;
    }

    public function setEncounter(string $encounterId): self
    {
        $this->encounterId = $encounterId;
        return $this;
    }

    public function setEffectiveDateTime(string $datetime): self
    {
        $this->effectiveDateTime = $datetime;
        return $this;
    }

    public function setIssued(string $datetime): self
    {
        $this->issued = $datetime;
        return $this;
    }

    public function addPerformer(string $practitionerId): self
    {
        $this->performers[] = ['reference' => "Practitioner/{$practitionerId}"];
        return $this;
    }

    public function addResult(string $observationId): self
    {
        $this->results[] = ['reference' => "Observation/{$observationId}"];
        return $this;
    }

    public function toArray(): array
    {
        $data = [
            'resourceType' => 'DiagnosticReport',
            'status'       => $this->status,
            'category'     => [
                [
                    'coding' => [
                        [
                            'system'  => $this->categorySystem,
                            'code'    => $this->categoryCode,
                            'display' => $this->categoryDisplay,
                        ],
                    ],
                ],
            ],
            'code'    => $this->code,
            'subject' => $this->subject,
        ];

        if (!empty($this->reportNo)) {
            $data['identifier'] = [
                [
                    'system' => "http://sys-ids.kemkes.go.id/diagnostic-report/{$this->orgId}",
                    'value'  => $this->reportNo,
                ],
            ];
        }

        if ($this->encounterId !== null) {
            $data['encounter'] = ['reference' => "Encounter/{$this->encounterId}"];
        }

        if ($this->effectiveDateTime !== null) {
            $data['effectiveDateTime'] = $this->effectiveDateTime;
        }

        if ($this->issued !== null) {
            $data['issued'] = $this->issued;
        }

        if ($this->performers !== []) {
            $data['performer'] = $this->performers;
        }

        if ($this->results !== []) {
            $data['result'] = $this->results;
        }

        return $data;
    }
}
