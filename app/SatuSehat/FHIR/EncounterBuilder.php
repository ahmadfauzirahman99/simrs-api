<?php

namespace App\SatuSehat\FHIR;

use App\SatuSehat\Support\DateHelper;

class EncounterBuilder extends BaseFhirResource
{
    private string  $status           = 'arrived';
    private ?array  $class            = null;
    private ?array  $subject          = null;
    private array   $participants     = [];
    private ?array  $location         = null;
    private ?string $serviceProviderId = null;
    private array   $statusHistory    = [];
    private ?string $periodStart      = null;
    private ?string $periodEnd        = null;
    private ?string $registrationId   = null;

    private static array $consultationMap = [
        'RAJAL'          => ['code' => 'AMB',  'display' => 'ambulatory'],
        'IGD'            => ['code' => 'EMER', 'display' => 'emergency'],
        'RANAP'          => ['code' => 'IMP',  'display' => 'inpatient encounter'],
        'HOMECARE'       => ['code' => 'HH',   'display' => 'home health'],
        'TELEKONSULTASI' => ['code' => 'TELE', 'display' => 'teleconsultation'],
    ];

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setRegistrationId(string $regId): self
    {
        $this->registrationId = $regId;
        return $this;
    }

    public function setConsultationMethod(string $method): self
    {
        $method = strtoupper($method);
        $entry  = self::$consultationMap[$method] ?? null;

        if ($entry === null) {
            throw new \InvalidArgumentException(
                "Invalid consultation method: {$method}. Accepted: RAJAL, IGD, RANAP, HOMECARE, TELEKONSULTASI"
            );
        }

        $this->class = array_merge($entry, [
            'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
        ]);

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

    public function setParticipant(string $practitionerId, string $name): self
    {
        $this->participants[] = [
            'type' => [
                [
                    'coding' => [
                        [
                            'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                            'code'    => 'ATND',
                            'display' => 'attender',
                        ],
                    ],
                ],
            ],
            'individual' => [
                'reference' => "Practitioner/{$practitionerId}",
                'display'   => $name,
            ],
        ];
        return $this;
    }

    public function setLocation(string $locationId, string $locationName): self
    {
        $this->location = [
            'location' => [
                'reference' => "Location/{$locationId}",
                'display'   => $locationName,
            ],
            'extension' => [
                [
                    'url'       => 'https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass',
                    'extension' => [
                        [
                            'url'                  => 'value',
                            'valueCodeableConcept'  => [
                                'coding' => [
                                    [
                                        'system'  => 'http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Outpatient',
                                        'code'    => 'reguler',
                                        'display' => 'Kelas Reguler',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        return $this;
    }

    public function setServiceProvider(?string $orgId = null): self
    {
        $this->serviceProviderId = $orgId ?? $this->orgId;
        return $this;
    }

    /**
     * Build status history from timestamp map.
     *
     * @param  array{arrived?: string, inprogress?: string, finished?: string} $timestamps
     */
    public function addStatusHistory(array $timestamps): self
    {
        $arrived    = DateHelper::safeDateTime($timestamps['arrived']    ?? null);
        $inprogress = DateHelper::safeDateTime($timestamps['inprogress'] ?? null);
        $finished   = DateHelper::safeDateTime($timestamps['finished']   ?? null);

        $history = [];

        if ($arrived) {
            $entry = ['status' => 'arrived', 'period' => ['start' => $arrived]];
            if ($inprogress) {
                $entry['period']['end'] = $inprogress;
            }
            $history[]          = $entry;
            $this->periodStart  = $arrived;
        }

        if ($inprogress) {
            $entry = ['status' => 'in-progress', 'period' => ['start' => $inprogress]];
            if ($finished) {
                $entry['period']['end'] = $finished;
            }
            $history[]    = $entry;
            $this->status = 'in-progress';
        }

        if ($finished) {
            $history[]         = ['status' => 'finished', 'period' => ['start' => $finished, 'end' => $finished]];
            $this->periodEnd   = $finished;
            $this->status      = 'finished';
        }

        $this->statusHistory = $history;
        return $this;
    }

    /**
     * Finalise the encounter period. Must be called as the last builder step.
     *
     * @param  string      $periodStart   ISO 8601 start datetime
     * @param  string|null $periodEnd     ISO 8601 end datetime
     * @param  string|null $classCode     Override class code (AMB|EMER|IMP|HH|TELE)
     */
    public function createEncounter(string $periodStart, ?string $periodEnd = null, ?string $classCode = null): self
    {
        if ($this->periodStart === null) {
            $this->periodStart = DateHelper::safeDateTime($periodStart) ?? $periodStart;
        }

        if ($periodEnd !== null && $this->periodEnd === null) {
            $this->periodEnd = DateHelper::safeDateTime($periodEnd) ?? $periodEnd;
        }

        if ($classCode !== null) {
            $this->setConsultationMethod($classCode);
        }

        return $this;
    }

    public function toArray(): array
    {
        $data = [
            'resourceType' => 'Encounter',
            'status'       => $this->status,
        ];

        if ($this->registrationId !== null) {
            $data['identifier'] = [
                [
                    'system' => 'http://sys-ids.kemkes.go.id/encounter/' . $this->orgId,
                    'value'  => $this->registrationId,
                ],
            ];
        }

        if ($this->class !== null) {
            $data['class'] = $this->class;
        }

        if ($this->subject !== null) {
            $data['subject'] = $this->subject;
        }

        if ($this->participants !== []) {
            $data['participant'] = $this->participants;
        }

        $period = [];
        if ($this->periodStart !== null) {
            $period['start'] = $this->periodStart;
        }
        if ($this->periodEnd !== null) {
            $period['end'] = $this->periodEnd;
        }
        if ($period !== []) {
            $data['period'] = $period;
        }

        if ($this->location !== null) {
            $data['location'] = [$this->location];
        }

        if ($this->statusHistory !== []) {
            $data['statusHistory'] = $this->statusHistory;
        }

        $data['serviceProvider'] = [
            'reference' => 'Organization/' . ($this->serviceProviderId ?? $this->orgId),
        ];

        return $data;
    }
}
