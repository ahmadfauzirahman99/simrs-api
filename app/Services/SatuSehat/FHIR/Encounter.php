<?php

namespace App\Services\SatuSehat\FHIR;

use App\Services\SatuSehat\OAuth2Client;

class Encounter extends OAuth2Client
{
    public array $encounter = [];

    public function setConsultationMethod(string $consultation_method): array|string
    {
        switch (strtoupper($consultation_method)) {
            case 'RAJAL':
                $class_code = 'AMB';
                $class_display = 'ambulatory';
                break;

            case 'IGD':
                $class_code = 'EMER';
                $class_display = 'emergency';
                break;

            case 'RANAP':
                $class_code = 'IMP';
                $class_display = 'inpatient encounter';
                break;

            case 'HOMECARE':
                $class_code = 'HH';
                $class_display = 'home health';
                break;

            case 'TELEKONSULTASI':
                $class_code = 'TELE';
                $class_display = 'teleconsultation';
                break;

            default:
                return 'consultation_method is invalid (Choose RAJAL / IGD / RANAP / HOMECARE / TELEKONSULTASI)';
        }

        return [
            'code' => $class_code,
            'display' => $class_display,
            'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
        ];
    }

    public function addStatusHistory(array $timestamp): array|string
    {
        if (!isset($timestamp['arrived']) || empty($timestamp['arrived'])) {
            return 'arrived is required';
        }

        $statusHistoryArrived = [
            'status' => 'arrived',
            'period' => [
                'start' => date('Y-m-d\TH:i:sP', strtotime($timestamp['arrived'])),
            ],
        ];

        $this->encounter['period']['start'] = $statusHistoryArrived['period']['start'];

        $statusHistoryInprogress = null;
        $statusHistoryFinished = null;

        if (!empty($timestamp['inprogress'])) {
            $statusHistoryInprogress = [
                'status' => 'in-progress',
                'period' => [
                    'start' => date('Y-m-d\TH:i:sP', strtotime($timestamp['inprogress'])),
                ],
            ];

            $statusHistoryArrived['period']['end'] = $statusHistoryInprogress['period']['start'];
        }

        if (!empty($timestamp['finished'])) {
            $finishedTime = date('Y-m-d\TH:i:sP', strtotime($timestamp['finished']));
            $this->encounter['period']['end'] = $finishedTime;

            $statusHistoryFinished = [
                'status' => 'finished',
                'period' => [
                    'start' => $finishedTime,
                    'end' => $finishedTime,
                ],
            ];

            if ($statusHistoryInprogress !== null) {
                $statusHistoryInprogress['period']['end'] = $finishedTime;
            }
        }

        $statusHistory = [];
        $statusHistory[] = $statusHistoryArrived;

        if ($statusHistoryInprogress !== null) {
            $statusHistory[] = $statusHistoryInprogress;
        }

        if ($statusHistoryFinished !== null) {
            $statusHistory[] = $statusHistoryFinished;
        }

        return $statusHistory;
    }

    public function addStatus(array $timestamp): array
    {
        $status = [
            'statusAkhir' => 'arrived',
        ];

        $start = [
            'startPoli' => null,
        ];

        if (array_key_exists('arrived', $timestamp) && !empty($timestamp['arrived'])) {
            $status['statusAkhir'] = 'arrived';
            $start['startPoli'] = date('Y-m-d\TH:i:sP', strtotime($timestamp['arrived']));
        }

        if (array_key_exists('inprogress', $timestamp) && !empty($timestamp['inprogress'])) {
            $status['statusAkhir'] = 'in-progress';
        }

        if (array_key_exists('finished', $timestamp) && !empty($timestamp['finished'])) {
            $status['statusAkhir'] = 'finished';
        }

        return [$status, $start];
    }

    public function createEncounter(
        string $reg_id,
        string $consultation_method,
        array $timestamp,
        object|array $pasien,
        string $id_dokter_satu_sehat,
        string $nama_unit,
        string $loc_id,
        string $namaloc
    ): array|string {
        $patientId = is_array($pasien) ? ($pasien['phis_satu_sehat'] ?? null) : ($pasien->phis_satu_sehat ?? null);
        $patientName = is_array($pasien) ? ($pasien['patient_name'] ?? null) : ($pasien->patient_name ?? null);

        if (empty($patientId)) {
            return 'Patient SATUSEHAT ID is required';
        }

        if (empty($patientName)) {
            return 'Patient name is required';
        }

        $consultationClass = $this->setConsultationMethod($consultation_method);
        if (is_string($consultationClass)) {
            return $consultationClass;
        }

        $statusHistory = $this->addStatusHistory($timestamp);
        if (is_string($statusHistory)) {
            return $statusHistory;
        }

        $status = $this->addStatus($timestamp);
        $startPoli = $status[1]['startPoli'] ?? null;

        if (empty($startPoli)) {
            return 'Start time is required';
        }

        $orgId = $this->organization_id;

        $array = [
            'resourceType' => 'Encounter',
            'identifier' => [
                [
                    'system' => 'http://sys-ids.kemkes.go.id/encounter/' . $orgId,
                    'value' => $reg_id,
                ],
            ],
            'status' => 'arrived',
            'class' => $consultationClass,
            'subject' => [
                'reference' => "Patient/{$patientId}",
                'display' => $patientName,
            ],
            'participant' => [
                [
                    'type' => [
                        [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                                    'code' => 'ATND',
                                    'display' => 'attender',
                                ],
                            ],
                        ],
                    ],
                    'individual' => [
                        'reference' => "Practitioner/{$id_dokter_satu_sehat}",
                        'display' => $nama_unit,
                    ],
                ],
            ],
            'period' => [
                'start' => $startPoli,
            ],
            'location' => [
                [
                    'location' => [
                        'reference' => "Location/{$loc_id}",
                        'display' => $namaloc,
                    ],
                    'extension' => [
                        [
                            'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass',
                            'extension' => [
                                [
                                    'url' => 'value',
                                    'valueCodeableConcept' => [
                                        'coding' => [
                                            [
                                                'system' => 'http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Outpatient',
                                                'code' => 'reguler',
                                                'display' => 'Kelas Reguler',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'statusHistory' => $statusHistory,
            'serviceProvider' => [
                'reference' => 'Organization/' . $orgId,
            ],
        ];

        return $array;
    }
}
