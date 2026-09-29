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
            [
                'id' => 'fhir-condition',
                'label' => 'FHIR Condition',
                'items' => [
                    $this->form(
                        id: 'condition-send',
                        title: 'Kirim Condition',
                        method: 'POST',
                        path: '/api/v1/satu-sehat/condition/send',
                        description: 'Mengirim payload Condition (Diagnosis/Keluhan) ke SATUSEHAT.',
                        fields: [
                            ['path' => 'patient_satu_sehat', 'label' => 'Patient SATUSEHAT ID', 'type' => 'text', 'value' => '100000030009'],
                            ['path' => 'patient_name', 'label' => 'Patient Name', 'type' => 'text', 'value' => 'Budi Santoso'],
                            ['path' => 'encounter_id', 'label' => 'Encounter ID', 'type' => 'text', 'value' => 'encounter-001'],
                            ['path' => 'icd10_code', 'label' => 'ICD-10 Code', 'type' => 'text', 'value' => 'J06.9'],
                            ['path' => 'icd10_display', 'label' => 'ICD-10 Display', 'type' => 'text', 'value' => 'Acute upper respiratory infection, unspecified'],
                            ['path' => 'category', 'label' => 'Category', 'type' => 'select', 'value' => 'diagnosis', 'options' => [
                                ['label' => 'Diagnosis', 'value' => 'diagnosis'],
                                ['label' => 'Keluhan', 'value' => 'keluhan'],
                            ]],
                            ['path' => 'onset_datetime', 'label' => 'Onset Datetime', 'type' => 'datetime-local', 'value' => '2026-03-20T08:00'],
                        ],
                        mockResponse: [
                            'metaData' => [
                                'code' => '201',
                                'message' => 'Berhasil kirim Condition ke SATUSEHAT',
                            ],
                            'response' => [
                                'satusehat_response' => [
                                    'resourceType' => 'Condition',
                                    'id' => 'condition-001',
                                    'clinicalStatus' => [
                                        'coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical', 'code' => 'active', 'display' => 'Active']],
                                    ],
                                    'verificationStatus' => [
                                        'coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/condition-ver-status', 'code' => 'confirmed', 'display' => 'Confirmed']],
                                    ],
                                    'category' => [
                                        ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/condition-category', 'code' => 'encounter-diagnosis', 'display' => 'Encounter Diagnosis']]],
                                    ],
                                    'code' => [
                                        'coding' => [['system' => 'http://hl7.org/fhir/sid/icd-10', 'code' => 'J06.9', 'display' => 'Acute upper respiratory infection, unspecified']],
                                    ],
                                    'subject' => ['reference' => 'Patient/100000030009', 'display' => 'Budi Santoso'],
                                    'encounter' => ['reference' => 'Encounter/encounter-001'],
                                    'onsetDateTime' => '2026-03-20T08:00:00+07:00',
                                ],
                                'request_payload' => [
                                    'resourceType' => 'Condition',
                                    'code' => ['coding' => [['system' => 'http://hl7.org/fhir/sid/icd-10', 'code' => 'J06.9']]],
                                ],
                            ],
                        ],
                    ),
                ],
            ],
            [
                'id' => 'fhir-observation',
                'label' => 'FHIR Observation',
                'items' => [
                    $this->form(
                        id: 'observation-send',
                        title: 'Kirim Observation',
                        method: 'POST',
                        path: '/api/v1/satu-sehat/observation/send',
                        description: 'Mengirim payload Observation (Tanda Vital/Lab) ke SATUSEHAT.',
                        fields: [
                            ['path' => 'patient_satu_sehat', 'label' => 'Patient SATUSEHAT ID', 'type' => 'text', 'value' => '100000030009'],
                            ['path' => 'patient_name', 'label' => 'Patient Name', 'type' => 'text', 'value' => 'Budi Santoso'],
                            ['path' => 'encounter_id', 'label' => 'Encounter ID', 'type' => 'text', 'value' => 'encounter-001'],
                            ['path' => 'loinc_code', 'label' => 'LOINC Code', 'type' => 'text', 'value' => '8480-6'],
                            ['path' => 'value', 'label' => 'Value', 'type' => 'number', 'value' => '120'],
                            ['path' => 'unit', 'label' => 'Unit', 'type' => 'text', 'value' => 'mmHg'],
                            ['path' => 'category', 'label' => 'Category', 'type' => 'select', 'value' => 'vital-signs', 'options' => [
                                ['label' => 'Vital Signs', 'value' => 'vital-signs'],
                                ['label' => 'Laboratory', 'value' => 'laboratory'],
                                ['label' => 'Imaging', 'value' => 'imaging'],
                                ['label' => 'Exam', 'value' => 'exam'],
                            ]],
                            ['path' => 'effective_datetime', 'label' => 'Effective Datetime', 'type' => 'datetime-local', 'value' => '2026-03-20T08:30'],
                        ],
                        mockResponse: [
                            'metaData' => [
                                'code' => '201',
                                'message' => 'Berhasil kirim Observation ke SATUSEHAT',
                            ],
                            'response' => [
                                'satusehat_response' => [
                                    'resourceType' => 'Observation',
                                    'id' => 'observation-001',
                                    'status' => 'final',
                                    'category' => [
                                        ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/observation-category', 'code' => 'vital-signs']]],
                                    ],
                                    'code' => [
                                        'coding' => [['system' => 'http://loinc.org', 'code' => '8480-6', 'display' => 'Systolic blood pressure']],
                                    ],
                                    'subject' => ['reference' => 'Patient/100000030009', 'display' => 'Budi Santoso'],
                                    'encounter' => ['reference' => 'Encounter/encounter-001'],
                                    'effectiveDateTime' => '2026-03-20T08:30:00+07:00',
                                    'valueQuantity' => ['value' => 120, 'unit' => 'mmHg', 'system' => 'http://unitsofmeasure.org', 'code' => 'mmHg'],
                                ],
                                'request_payload' => [
                                    'resourceType' => 'Observation',
                                    'status' => 'final',
                                ],
                            ],
                        ],
                    ),
                ],
            ],
            [
                'id' => 'fhir-procedure',
                'label' => 'FHIR Procedure',
                'items' => [
                    $this->form(
                        id: 'procedure-send',
                        title: 'Kirim Procedure',
                        method: 'POST',
                        path: '/api/v1/satu-sehat/procedure/send',
                        description: 'Mengirim payload Procedure ke SATUSEHAT.',
                        fields: [
                            ['path' => 'patient_satu_sehat', 'label' => 'Patient SATUSEHAT ID', 'type' => 'text', 'value' => '100000030009'],
                            ['path' => 'patient_name', 'label' => 'Patient Name', 'type' => 'text', 'value' => 'Budi Santoso'],
                            ['path' => 'encounter_id', 'label' => 'Encounter ID', 'type' => 'text', 'value' => 'encounter-001'],
                            ['path' => 'snomed_code', 'label' => 'SNOMED Code', 'type' => 'text', 'value' => '386053000'],
                            ['path' => 'snomed_display', 'label' => 'SNOMED Display', 'type' => 'text', 'value' => 'Evaluation procedure'],
                            ['path' => 'performed_start', 'label' => 'Performed Start', 'type' => 'datetime-local', 'value' => '2026-03-20T08:30'],
                            ['path' => 'performed_end', 'label' => 'Performed End', 'type' => 'datetime-local', 'value' => '2026-03-20T08:45'],
                            ['path' => 'practitioner_id', 'label' => 'Practitioner SATUSEHAT ID', 'type' => 'text', 'value' => '100000020001'],
                            ['path' => 'practitioner_name', 'label' => 'Practitioner Name', 'type' => 'text', 'value' => 'dr. Ahmad'],
                        ],
                        mockResponse: [
                            'metaData' => [
                                'code' => '201',
                                'message' => 'Berhasil kirim Procedure ke SATUSEHAT',
                            ],
                            'response' => [
                                'satusehat_response' => [
                                    'resourceType' => 'Procedure',
                                    'id' => 'procedure-001',
                                    'status' => 'completed',
                                    'code' => [
                                        'coding' => [['system' => 'http://snomed.info/sct', 'code' => '386053000', 'display' => 'Evaluation procedure']],
                                    ],
                                    'subject' => ['reference' => 'Patient/100000030009', 'display' => 'Budi Santoso'],
                                    'encounter' => ['reference' => 'Encounter/encounter-001'],
                                    'performedPeriod' => [
                                        'start' => '2026-03-20T08:30:00+07:00',
                                        'end' => '2026-03-20T08:45:00+07:00',
                                    ],
                                    'performer' => [
                                        ['actor' => ['reference' => 'Practitioner/100000020001', 'display' => 'dr. Ahmad']],
                                    ],
                                ],
                                'request_payload' => [
                                    'resourceType' => 'Procedure',
                                    'status' => 'completed',
                                ],
                            ],
                        ],
                    ),
                ],
            ],
            [
                'id' => 'fhir-service-request',
                'label' => 'FHIR ServiceRequest',
                'items' => [
                    $this->form(
                        id: 'service-request-send',
                        title: 'Kirim ServiceRequest',
                        method: 'POST',
                        path: '/api/v1/satu-sehat/service-request/send',
                        description: 'Mengirim payload ServiceRequest (Permintaan Tindakan/Lab/Rad) ke SATUSEHAT.',
                        fields: [
                            ['path' => 'patient_satu_sehat', 'label' => 'Patient SATUSEHAT ID', 'type' => 'text', 'value' => '100000030009'],
                            ['path' => 'patient_name', 'label' => 'Patient Name', 'type' => 'text', 'value' => 'Budi Santoso'],
                            ['path' => 'encounter_id', 'label' => 'Encounter ID', 'type' => 'text', 'value' => 'encounter-001'],
                            ['path' => 'snomed_code', 'label' => 'SNOMED Code', 'type' => 'text', 'value' => '108252007'],
                            ['path' => 'snomed_display', 'label' => 'SNOMED Display', 'type' => 'text', 'value' => 'Laboratory procedure'],
                            ['path' => 'practitioner_id', 'label' => 'Practitioner SATUSEHAT ID', 'type' => 'text', 'value' => '100000020001'],
                            ['path' => 'practitioner_name', 'label' => 'Practitioner Name', 'type' => 'text', 'value' => 'dr. Ahmad'],
                            ['path' => 'priority', 'label' => 'Priority', 'type' => 'select', 'value' => 'routine', 'options' => [
                                ['label' => 'Routine', 'value' => 'routine'],
                                ['label' => 'Urgent', 'value' => 'urgent'],
                                ['label' => 'ASAP', 'value' => 'asap'],
                                ['label' => 'STAT', 'value' => 'stat'],
                            ]],
                            ['path' => 'occurrence_datetime', 'label' => 'Occurrence Datetime', 'type' => 'datetime-local', 'value' => '2026-03-20T09:00'],
                            ['path' => 'condition_id', 'label' => 'Condition ID (opsional)', 'type' => 'text', 'value' => ''],
                        ],
                        mockResponse: [
                            'metaData' => [
                                'code' => '201',
                                'message' => 'Berhasil kirim ServiceRequest ke SATUSEHAT',
                            ],
                            'response' => [
                                'satusehat_response' => [
                                    'resourceType' => 'ServiceRequest',
                                    'id' => 'sr-001',
                                    'status' => 'active',
                                    'intent' => 'original-order',
                                    'priority' => 'routine',
                                    'code' => [
                                        'coding' => [['system' => 'http://snomed.info/sct', 'code' => '108252007', 'display' => 'Laboratory procedure']],
                                    ],
                                    'subject' => ['reference' => 'Patient/100000030009', 'display' => 'Budi Santoso'],
                                    'encounter' => ['reference' => 'Encounter/encounter-001'],
                                    'occurrenceDateTime' => '2026-03-20T09:00:00+07:00',
                                    'requester' => ['reference' => 'Practitioner/100000020001', 'display' => 'dr. Ahmad'],
                                ],
                                'request_payload' => [
                                    'resourceType' => 'ServiceRequest',
                                    'status' => 'active',
                                    'intent' => 'original-order',
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
