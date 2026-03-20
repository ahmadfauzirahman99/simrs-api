<?php

namespace App\Support\SatuSehat;

class SatuSehatTesterCatalog
{
    public function groups(): array
    {
        return [
            [
                'id' => 'auth',
                'label' => 'Auth',
                'items' => [
                    $this->query(
                        id: 'token',
                        title: 'Ambil Token SATUSEHAT',
                        method: 'GET',
                        path: '/api/v1/satu-sehat/token',
                        description: 'Mengambil access token SATUSEHAT yang aktif.',
                        fields: [],
                        mockResponse: [
                            'metaData' => [
                                'code' => '200',
                                'message' => 'Sukses',
                            ],
                            'response' => [
                                'token' => 'demo-satusehat-token',
                                'organization_id' => 'ORG-123',
                                'base_url' => 'https://api-satusehat-dev.dto.kemkes.go.id/fhir-r4/v1',
                                'environment' => 'DEV',
                            ],
                        ],
                    ),
                ],
            ],
            [
                'id' => 'fhir',
                'label' => 'FHIR Encounter',
                'items' => [
                    $this->form(
                        id: 'encounter-send',
                        title: 'Kirim Encounter',
                        method: 'POST',
                        path: '/api/v1/satu-sehat/encounter/send',
                        description: 'Mengirim payload Encounter ke SATUSEHAT.',
                        fields: [
                            ['path' => 'reg_id', 'label' => 'Registration ID', 'type' => 'text', 'value' => 'REG-001'],
                            ['path' => 'consultation_method', 'label' => 'Consultation Method', 'type' => 'select', 'value' => 'RAJAL', 'options' => [
                                ['label' => 'RAJAL', 'value' => 'RAJAL'],
                                ['label' => 'IGD', 'value' => 'IGD'],
                                ['label' => 'RANAP', 'value' => 'RANAP'],
                                ['label' => 'HOMECARE', 'value' => 'HOMECARE'],
                                ['label' => 'TELEKONSULTASI', 'value' => 'TELEKONSULTASI'],
                            ]],
                            ['path' => 'arrived', 'label' => 'Arrived', 'type' => 'datetime-local', 'value' => '2026-03-20T08:00'],
                            ['path' => 'inprogress', 'label' => 'In Progress', 'type' => 'datetime-local', 'value' => '2026-03-20T08:15'],
                            ['path' => 'finished', 'label' => 'Finished', 'type' => 'datetime-local', 'value' => '2026-03-20T09:00'],
                            ['path' => 'patient_satu_sehat', 'label' => 'Patient SATUSEHAT ID', 'type' => 'text', 'value' => '100000030009'],
                            ['path' => 'patient_name', 'label' => 'Patient Name', 'type' => 'text', 'value' => 'Budi Santoso'],
                            ['path' => 'doctor_satu_sehat', 'label' => 'Doctor SATUSEHAT ID', 'type' => 'text', 'value' => '100000020001'],
                            ['path' => 'unit_name', 'label' => 'Unit Name', 'type' => 'text', 'value' => 'Poli Penyakit Dalam'],
                            ['path' => 'location_id', 'label' => 'Location ID', 'type' => 'text', 'value' => 'LOC-001'],
                            ['path' => 'location_name', 'label' => 'Location Name', 'type' => 'text', 'value' => 'Ruang Poli Dalam'],
                        ],
                        mockResponse: [
                            'metaData' => [
                                'code' => '200',
                                'message' => 'Berhasil kirim Encounter ke SATUSEHAT',
                            ],
                            'response' => [
                                'satusehat_response' => [
                                    'resourceType' => 'Encounter',
                                    'id' => 'encounter-001',
                                    'status' => 'arrived',
                                    'class' => [
                                        'code' => 'AMB',
                                        'display' => 'ambulatory',
                                    ],
                                    'subject' => [
                                        'reference' => 'Patient/100000030009',
                                        'display' => 'Budi Santoso',
                                    ],
                                    'participant' => [
                                        [
                                            'individual' => [
                                                'reference' => 'Practitioner/100000020001',
                                                'display' => 'dr. Rina Wulandari',
                                            ],
                                        ],
                                    ],
                                    'period' => [
                                        'start' => '2026-03-20T08:00:00+07:00',
                                        'end' => '2026-03-20T09:00:00+07:00',
                                    ],
                                    'location' => [
                                        [
                                            'location' => [
                                                'reference' => 'Location/LOC-001',
                                                'display' => 'Ruang Poli Dalam',
                                            ],
                                        ],
                                    ],
                                    'serviceProvider' => [
                                        'reference' => 'Organization/ORG-123',
                                        'display' => 'RS Demo SATUSEHAT',
                                    ],
                                ],
                                'request_payload' => [
                                    'resourceType' => 'Encounter',
                                    'status' => 'arrived',
                                    'class' => [
                                        'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                                        'code' => 'AMB',
                                        'display' => 'ambulatory',
                                    ],
                                    'subject' => [
                                        'reference' => 'Patient/100000030009',
                                        'display' => 'Budi Santoso',
                                    ],
                                    'participant' => [
                                        [
                                            'individual' => [
                                                'reference' => 'Practitioner/100000020001',
                                                'display' => 'dr. Rina Wulandari',
                                            ],
                                        ],
                                    ],
                                    'period' => [
                                        'start' => '2026-03-20T08:00:00+07:00',
                                        'end' => '2026-03-20T09:00:00+07:00',
                                    ],
                                ],
                                'status_history_fhir' => [
                                    [
                                        'status' => 'arrived',
                                        'period' => [
                                            'start' => '2026-03-20T08:00:00+07:00',
                                            'end' => '2026-03-20T08:15:00+07:00',
                                        ],
                                    ],
                                    [
                                        'status' => 'in-progress',
                                        'period' => [
                                            'start' => '2026-03-20T08:15:00+07:00',
                                            'end' => '2026-03-20T09:00:00+07:00',
                                        ],
                                    ],
                                ],
                                'related_fhir_resources' => [
                                    [
                                        'resourceType' => 'Patient',
                                        'id' => '100000030009',
                                        'identifier' => [
                                            [
                                                'system' => 'https://fhir.kemkes.go.id/id/nik',
                                                'value' => '3319022010810007',
                                            ],
                                            [
                                                'system' => 'https://fhir.kemkes.go.id/id/ihs-number',
                                                'value' => '100000030009',
                                            ],
                                        ],
                                        'name' => [
                                            [
                                                'text' => 'Budi Santoso',
                                                'given' => ['Budi'],
                                                'family' => 'Santoso',
                                            ],
                                        ],
                                        'gender' => 'male',
                                        'birthDate' => '1981-10-10',
                                    ],
                                    [
                                        'resourceType' => 'Observation',
                                        'id' => 'observation-001',
                                        'status' => 'final',
                                        'code' => [
                                            'text' => 'Systolic Blood Pressure',
                                        ],
                                        'subject' => [
                                            'reference' => 'Patient/100000030009',
                                            'display' => 'Budi Santoso',
                                        ],
                                        'effectiveDateTime' => '2026-03-20T08:30:00+07:00',
                                        'valueQuantity' => [
                                            'value' => 120,
                                            'unit' => 'mmHg',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ),
                ],
            ],
        ];
    }

    protected function query(
        string $id,
        string $title,
        string $method,
        string $path,
        string $description,
        array $fields,
        array $mockResponse
    ): array {
        return [
            'id' => $id,
            'title' => $title,
            'method' => $method,
            'path' => $path,
            'description' => $description,
            'mode' => 'query',
            'fields' => $fields,
            'mockResponses' => [
                'success' => $mockResponse,
                'failed' => $this->failedMockResponse(),
            ],
        ];
    }

    protected function form(
        string $id,
        string $title,
        string $method,
        string $path,
        string $description,
        array $fields,
        array $mockResponse
    ): array {
        return [
            'id' => $id,
            'title' => $title,
            'method' => $method,
            'path' => $path,
            'description' => $description,
            'mode' => 'form',
            'formFields' => $fields,
            'mockResponses' => [
                'success' => $mockResponse,
                'failed' => $this->failedMockResponse(),
            ],
        ];
    }

    protected function failedMockResponse(): array
    {
        return [
            'metaData' => [
                'code' => '500',
                'message' => 'gagal',
            ],
            'response' => [],
        ];
    }
}
