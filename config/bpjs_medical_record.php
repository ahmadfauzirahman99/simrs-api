<?php

return [
    'resources' => [
        'bundle' => [
            'label' => 'Bundle',
            'description' => 'Wadah utama seluruh resource rekam medis yang akan dienkripsi menjadi dataMR.',
            'multiple' => false,
            'required_fields' => [
                ['path' => 'resourceType', 'type' => 'string', 'example' => 'Bundle', 'description' => 'Harus bernilai Bundle.'],
                ['path' => 'id', 'type' => 'string', 'example' => 'bundle-001', 'description' => 'ID unik bundle.'],
                ['path' => 'type', 'type' => 'string', 'example' => 'Document', 'description' => 'Jenis bundle rekam medis.'],
                ['path' => 'identifier.system', 'type' => 'string', 'example' => 'https://rs.example.id/bundle', 'description' => 'Sistem identifier bundle.'],
                ['path' => 'identifier.value', 'type' => 'string', 'example' => 'MR-202503-0001', 'description' => 'Nomor dokumen bundle.'],
                ['path' => 'entry', 'type' => 'array', 'example' => [], 'description' => 'Daftar resource satu per satu.'],
            ],
            'template' => [
                'resourceType' => 'Bundle',
                'id' => 'bundle-001',
                'type' => 'Document',
                'identifier' => [
                    'system' => 'https://rs.example.id/bundle',
                    'value' => 'MR-202503-0001',
                ],
                'entry' => [],
            ],
        ],
        'composition' => [
            'label' => 'Composition',
            'description' => 'Dokumen ringkasan rekam medis atau resume medis pasien.',
            'multiple' => false,
            'required_fields' => [
                ['path' => 'resourceType', 'type' => 'string', 'example' => 'Composition', 'description' => 'Harus bernilai Composition.'],
                ['path' => 'id', 'type' => 'string', 'example' => 'composition-001', 'description' => 'ID unik composition.'],
                ['path' => 'status', 'type' => 'string', 'example' => 'final', 'description' => 'Status dokumen.'],
                ['path' => 'type.coding[0].code', 'type' => 'string', 'example' => '18842-5', 'description' => 'Kode jenis dokumen.'],
                ['path' => 'subject.reference', 'type' => 'string', 'example' => 'Patient/patient-001', 'description' => 'Referensi ke pasien.'],
                ['path' => 'encounter.reference', 'type' => 'string', 'example' => 'Encounter/encounter-001', 'description' => 'Referensi ke encounter.'],
                ['path' => 'date', 'type' => 'datetime', 'example' => '2025-03-24T09:00:00+07:00', 'description' => 'Tanggal dokumen.'],
                ['path' => 'author[0].reference', 'type' => 'string', 'example' => 'Practitioner/practitioner-001', 'description' => 'Dokter penulis dokumen.'],
                ['path' => 'title', 'type' => 'string', 'example' => 'Resume Medis Pasien', 'description' => 'Judul dokumen.'],
                ['path' => 'section', 'type' => 'array', 'example' => [], 'description' => 'Daftar section dokumen.'],
            ],
            'template' => [
                'resourceType' => 'Composition',
                'id' => 'composition-001',
                'status' => 'final',
                'type' => [
                    'coding' => [
                        [
                            'system' => 'http://loinc.org',
                            'code' => '18842-5',
                            'display' => 'Discharge summary',
                        ],
                    ],
                ],
                'subject' => ['reference' => 'Patient/patient-001'],
                'encounter' => ['reference' => 'Encounter/encounter-001'],
                'date' => '2025-03-24T09:00:00+07:00',
                'author' => [
                    ['reference' => 'Practitioner/practitioner-001'],
                ],
                'title' => 'Resume Medis Pasien',
                'section' => [],
            ],
        ],
        'patient' => [
            'label' => 'Patient',
            'description' => 'Identitas pasien yang menjadi subjek rekam medis.',
            'multiple' => false,
            'required_fields' => [
                ['path' => 'resourceType', 'type' => 'string', 'example' => 'Patient', 'description' => 'Harus bernilai Patient.'],
                ['path' => 'id', 'type' => 'string', 'example' => 'patient-001', 'description' => 'ID unik pasien di bundle.'],
                ['path' => 'identifier', 'type' => 'array', 'example' => [
                    [
                        'use' => 'usual',
                        'type' => [
                            'coding' => [[
                                'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                                'code' => 'MR',
                                'display' => 'Medical record number',
                            ]],
                            'text' => 'Medical record number',
                        ],
                        'value' => '042790',
                        'assigner' => ['display' => 'RS Contoh'],
                    ],
                    [
                        'use' => 'official',
                        'type' => [
                            'coding' => [[
                                'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                                'code' => 'MB',
                                'display' => 'Member Number',
                            ]],
                            'text' => 'No. Peserta BPJS',
                        ],
                        'value' => '0002034340953',
                        'assigner' => ['display' => 'BPJS KESEHATAN'],
                    ],
                ], 'description' => 'Identifier pasien, minimal berisi MRN, no BPJS, dan bila ada NIK.'],
                ['path' => 'active', 'type' => 'boolean', 'example' => true, 'description' => 'Status aktif pasien.'],
                ['path' => 'name[0].use', 'type' => 'string', 'example' => 'official', 'description' => 'Jenis nama pasien.'],
                ['path' => 'name[0].text', 'type' => 'string', 'example' => 'Budi Santoso', 'description' => 'Nama lengkap pasien.'],
                ['path' => 'maritalStatus.coding[0].code', 'type' => 'string', 'example' => 'M', 'description' => 'Kode status pernikahan pasien.'],
                ['path' => 'maritalStatus.coding[0].display', 'type' => 'string', 'example' => 'Married', 'description' => 'Label status pernikahan pasien.'],
                ['path' => 'maritalStatus.text', 'type' => 'string', 'example' => 'Married', 'description' => 'Teks status pernikahan pasien.'],
                ['path' => 'telecom', 'type' => 'array', 'example' => [
                    ['system' => 'phone', 'value' => '', 'use' => 'work'],
                    ['system' => 'phone', 'value' => '0812233665544', 'use' => 'mobile'],
                ], 'description' => 'Kontak pasien seperti telepon kerja, mobile, atau rumah.'],
                ['path' => 'gender', 'type' => 'string', 'example' => 'male', 'description' => 'Jenis kelamin pasien.'],
                ['path' => 'birthDate', 'type' => 'date', 'example' => '1980-01-10', 'description' => 'Tanggal lahir pasien.'],
                ['path' => 'deceasedBoolean', 'type' => 'boolean', 'example' => false, 'description' => 'Status pasien meninggal atau tidak.'],
                ['path' => 'address', 'type' => 'array', 'example' => [[
                    'use' => 'home',
                    'type' => 'both',
                    'text' => 'Tes',
                    'line' => ['Tes'],
                    'city' => '-',
                    'district' => '-',
                    'state' => '-',
                    'postalCode' => '-',
                ]], 'description' => 'Alamat pasien lengkap.'],
                ['path' => 'managingOrganization.reference', 'type' => 'string', 'example' => 'Organization/org-rs', 'description' => 'Referensi unit atau organisasi pengelola pasien.'],
                ['path' => 'managingOrganization.display', 'type' => 'string', 'example' => 'Instalasi Gawat Darurat', 'description' => 'Label organisasi pengelola pasien.'],
            ],
            'template' => [
                'resourceType' => 'Patient',
                'id' => 'patient-001',
                'identifier' => [
                    [
                        'use' => 'usual',
                        'type' => [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                                    'code' => 'MR',
                                    'display' => 'Medical record number',
                                ],
                            ],
                            'text' => 'Medical record number',
                        ],
                        'value' => '042790',
                        'assigner' => [
                            'display' => 'RS Contoh',
                        ],
                    ],
                    [
                        'use' => 'official',
                        'type' => [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                                    'code' => 'MB',
                                    'display' => 'Member Number',
                                ],
                            ],
                            'text' => 'No. Peserta BPJS',
                        ],
                        'value' => '0001112223334',
                        'assigner' => [
                            'display' => 'BPJS KESEHATAN',
                        ],
                    ],
                ],
                'active' => true,
                'name' => [
                    [
                        'use' => 'official',
                        'text' => 'Budi Santoso',
                    ],
                ],
                'maritalStatus' => [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/v3-MaritalStatus',
                            'code' => 'M',
                            'display' => 'Married',
                        ],
                    ],
                    'text' => 'Married',
                ],
                'telecom' => [
                    ['system' => 'phone', 'value' => '', 'use' => 'work'],
                    ['system' => 'phone', 'value' => '081234567890', 'use' => 'mobile'],
                    ['system' => 'phone', 'value' => '', 'use' => 'home'],
                ],
                'gender' => 'male',
                'birthDate' => '1980-01-10',
                'deceasedBoolean' => false,
                'address' => [
                    [
                        'use' => 'home',
                        'type' => 'both',
                        'text' => 'Jl. Contoh No. 1',
                        'line' => ['Jl. Contoh No. 1'],
                        'city' => 'Pekanbaru',
                        'district' => '-',
                        'state' => 'Riau',
                        'postalCode' => '28111',
                    ],
                ],
                'managingOrganization' => [
                    'reference' => 'Organization/org-rs',
                    'display' => 'Instalasi Gawat Darurat',
                ],
            ],
        ],
        'encounter' => [
            'label' => 'Encounter',
            'description' => 'Episode pelayanan yang menjadi konteks rekam medis.',
            'multiple' => false,
            'required_fields' => [
                ['path' => 'resourceType', 'type' => 'string', 'example' => 'Encounter', 'description' => 'Harus bernilai Encounter.'],
                ['path' => 'id', 'type' => 'string', 'example' => 'encounter-001', 'description' => 'ID unik encounter.'],
                ['path' => 'status', 'type' => 'string', 'example' => 'finished', 'description' => 'Status pelayanan.'],
                ['path' => 'class.code', 'type' => 'string', 'example' => 'IMP', 'description' => 'Kelas encounter seperti rawat inap/rawat jalan.'],
                ['path' => 'subject.reference', 'type' => 'string', 'example' => 'Patient/patient-001', 'description' => 'Referensi ke pasien.'],
                ['path' => 'period.start', 'type' => 'datetime', 'example' => '2025-03-20T08:00:00+07:00', 'description' => 'Mulai encounter.'],
                ['path' => 'period.end', 'type' => 'datetime', 'example' => '2025-03-24T10:30:00+07:00', 'description' => 'Selesai encounter.'],
            ],
            'template' => [
                'resourceType' => 'Encounter',
                'id' => 'encounter-001',
                'status' => 'finished',
                'class' => [
                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                    'code' => 'IMP',
                    'display' => 'inpatient encounter',
                ],
                'subject' => ['reference' => 'Patient/patient-001'],
                'period' => [
                    'start' => '2025-03-20T08:00:00+07:00',
                    'end' => '2025-03-24T10:30:00+07:00',
                ],
                'serviceProvider' => ['reference' => 'Organization/org-rs'],
            ],
        ],
        'organization' => [
            'label' => 'Organization',
            'description' => 'Rumah sakit, poli, laboratorium, atau unit yang terlibat.',
            'multiple' => true,
            'required_fields' => [
                ['path' => 'resourceType', 'type' => 'string', 'example' => 'Organization', 'description' => 'Harus bernilai Organization.'],
                ['path' => 'id', 'type' => 'string', 'example' => 'org-rs', 'description' => 'ID unik organisasi.'],
                ['path' => 'identifier', 'type' => 'array', 'example' => [
                    ['use' => 'official', 'system' => 'urn:oid:bpjs', 'value' => '0220R002'],
                    ['use' => 'official', 'system' => 'urn:oid:kemkes', 'value' => '1277015'],
                ], 'description' => 'Identifier organisasi seperti kode BPJS dan kode Kemenkes.'],
                ['path' => 'type', 'type' => 'array', 'example' => [[
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/organization-type',
                        'code' => 'prov',
                        'display' => 'Healthcare Provider',
                    ]],
                    'text' => 'Healthcare Provider',
                ]], 'description' => 'Jenis organisasi atau unit layanan.'],
                ['path' => 'name', 'type' => 'string', 'example' => 'RS Contoh Sehat', 'description' => 'Nama organisasi.'],
                ['path' => 'alias', 'type' => 'array', 'example' => ['IGD'], 'description' => 'Alias atau singkatan organisasi.'],
                ['path' => 'telecom', 'type' => 'array', 'example' => [
                    ['system' => 'phone', 'value' => '021-1234567', 'use' => 'work'],
                ], 'description' => 'Kontak organisasi.'],
                ['path' => 'address', 'type' => 'array', 'example' => [[
                    'use' => 'work',
                    'text' => 'Jalan Sudirman No. 1',
                    'line' => ['Jalan Sudirman No. 1'],
                    'city' => 'Kota Padangsidimpuan',
                    'state' => 'Sumatera Utara',
                    'postalCode' => '22713',
                    'country' => 'Indonesia',
                ]], 'description' => 'Alamat organisasi.'],
                ['path' => 'contact', 'type' => 'array', 'example' => [[
                    'purpose' => [
                        'coding' => [[
                            'system' => 'http://terminology.hl7.org/CodeSystem/contactentity-type',
                            'code' => 'ADMIN',
                            'display' => 'Administrative',
                        ]],
                    ],
                    'telecom' => [[
                        'system' => 'phone',
                        'value' => '021-1234567',
                        'use' => 'work',
                    ]],
                ]], 'description' => 'Kontak person atau administrasi organisasi.'],
            ],
            'template' => [
                'resourceType' => 'Organization',
                'id' => 'org-rs',
                'identifier' => [
                    ['use' => 'official', 'system' => 'urn:oid:bpjs', 'value' => '0220R002'],
                    ['use' => 'official', 'system' => 'urn:oid:kemkes', 'value' => '1277015'],
                ],
                'type' => [
                    [
                        'coding' => [
                            [
                                'system' => 'http://terminology.hl7.org/CodeSystem/organization-type',
                                'code' => 'prov',
                                'display' => 'Healthcare Provider',
                            ],
                        ],
                        'text' => 'Healthcare Provider',
                    ],
                ],
                'name' => 'RS Contoh Sehat',
                'alias' => ['IGD'],
                'telecom' => [
                    ['system' => 'phone', 'value' => '021-1234567', 'use' => 'work'],
                ],
                'address' => [
                    [
                        'use' => 'work',
                        'text' => 'Jalan Sudirman No. 1',
                        'line' => ['Jalan Sudirman No. 1'],
                        'city' => 'Kota Padangsidimpuan',
                        'state' => 'Sumatera Utara',
                        'postalCode' => '22713',
                        'country' => 'Indonesia',
                    ],
                ],
                'contact' => [
                    [
                        'purpose' => [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/contactentity-type',
                                    'code' => 'ADMIN',
                                    'display' => 'Administrative',
                                ],
                            ],
                        ],
                        'telecom' => [
                            ['system' => 'phone', 'value' => '021-1234567', 'use' => 'work'],
                        ],
                    ],
                ],
            ],
        ],
        'practitioner' => [
            'label' => 'Practitioner',
            'description' => 'Dokter atau tenaga kesehatan yang terlibat dalam pelayanan.',
            'multiple' => true,
            'required_fields' => [
                ['path' => 'resourceType', 'type' => 'string', 'example' => 'Practitioner', 'description' => 'Harus bernilai Practitioner.'],
                ['path' => 'id', 'type' => 'string', 'example' => 'practitioner-001', 'description' => 'ID unik praktisi.'],
                ['path' => 'identifier', 'type' => 'array', 'example' => [
                    [
                        'use' => 'official',
                        'type' => [
                            'coding' => [[
                                'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                                'code' => 'MD',
                                'display' => 'Medical license number',
                            ]],
                            'text' => 'Nomor SIP',
                        ],
                        'system' => 'https://satusehat.kemkes.go.id/sdmk',
                        'value' => '430/DPMPTSP/SIPD/2023',
                        'assigner' => ['display' => 'Kemenkes'],
                    ],
                ], 'description' => 'Identifier tenaga kesehatan seperti SIP dan NIK.'],
                ['path' => 'name[0].use', 'type' => 'string', 'example' => 'official', 'description' => 'Jenis nama praktisi.'],
                ['path' => 'name[0].text', 'type' => 'string', 'example' => 'dr. Ahmad', 'description' => 'Nama tenaga kesehatan.'],
                ['path' => 'telecom', 'type' => 'array', 'example' => [
                    ['system' => 'phone', 'value' => '00000000', 'use' => 'work'],
                    ['system' => 'email', 'value' => '-', 'use' => 'work'],
                ], 'description' => 'Kontak praktisi.'],
                ['path' => 'address', 'type' => 'array', 'example' => [[
                    'use' => 'home',
                    'type' => 'physical',
                    'text' => 'JL.STN.PANINDOAN NO 30',
                    'line' => ['JL.STN.PANINDOAN NO 30'],
                    'city' => 'PADANGSIDIMPUAN',
                    'district' => '-',
                    'state' => '-',
                    'postalCode' => '-',
                    'country' => 'INDONESIA',
                ]], 'description' => 'Alamat praktisi.'],
                ['path' => 'gender', 'type' => 'string', 'example' => 'female', 'description' => 'Jenis kelamin praktisi.'],
                ['path' => 'birthDate', 'type' => 'date', 'example' => '1992-07-22', 'description' => 'Tanggal lahir praktisi.'],
            ],
            'template' => [
                'resourceType' => 'Practitioner',
                'id' => 'practitioner-001',
                'identifier' => [
                    [
                        'use' => 'official',
                        'type' => [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                                    'code' => 'MD',
                                    'display' => 'Medical license number',
                                ],
                            ],
                            'text' => 'Nomor SIP',
                        ],
                        'system' => 'https://satusehat.kemkes.go.id/sdmk',
                        'value' => '430/DPMPTSP/SIPD/2023',
                        'assigner' => [
                            'display' => 'Kemenkes',
                        ],
                    ],
                ],
                'name' => [
                    [
                        'use' => 'official',
                        'text' => 'dr. Ahmad',
                    ],
                ],
                'telecom' => [
                    ['system' => 'phone', 'value' => '00000000', 'use' => 'work'],
                    ['system' => 'email', 'value' => '-', 'use' => 'work'],
                    ['system' => 'fax', 'value' => '-', 'use' => 'work'],
                ],
                'address' => [
                    [
                        'use' => 'home',
                        'type' => 'physical',
                        'text' => 'JL.STN.PANINDOAN NO 30',
                        'line' => ['JL.STN.PANINDOAN NO 30'],
                        'city' => 'PADANGSIDIMPUAN',
                        'district' => '-',
                        'state' => '-',
                        'postalCode' => '-',
                        'country' => 'INDONESIA',
                    ],
                ],
                'gender' => 'female',
                'birthDate' => '1992-07-22',
            ],
        ],
        'condition' => [
            'label' => 'Condition',
            'description' => 'Diagnosis atau masalah klinis pasien.',
            'multiple' => true,
            'required_fields' => [
                ['path' => 'resourceType', 'type' => 'string', 'example' => 'Condition', 'description' => 'Harus bernilai Condition.'],
                ['path' => 'id', 'type' => 'string', 'example' => 'condition-001', 'description' => 'ID unik diagnosis.'],
                ['path' => 'code.coding[0].code', 'type' => 'string', 'example' => 'I10', 'description' => 'Kode diagnosis.'],
                ['path' => 'code.coding[0].display', 'type' => 'string', 'example' => 'Essential (primary) hypertension', 'description' => 'Nama diagnosis.'],
                ['path' => 'subject.reference', 'type' => 'string', 'example' => 'Patient/patient-001', 'description' => 'Referensi ke pasien.'],
            ],
            'template' => [
                'resourceType' => 'Condition',
                'id' => 'condition-001',
                'clinicalStatus' => ['text' => 'active'],
                'verificationStatus' => ['text' => 'confirmed'],
                'code' => [
                    'coding' => [
                        [
                            'system' => 'http://hl7.org/fhir/sid/icd-10',
                            'code' => 'I10',
                            'display' => 'Essential (primary) hypertension',
                        ],
                    ],
                ],
                'subject' => ['reference' => 'Patient/patient-001'],
                'onsetDateTime' => '2025-03-20T09:00:00+07:00',
            ],
        ],
        'procedure' => [
            'label' => 'Procedure',
            'description' => 'Tindakan atau prosedur medis yang dilakukan.',
            'multiple' => true,
            'required_fields' => [
                ['path' => 'resourceType', 'type' => 'string', 'example' => 'Procedure', 'description' => 'Harus bernilai Procedure.'],
                ['path' => 'id', 'type' => 'string', 'example' => 'procedure-001', 'description' => 'ID unik tindakan.'],
                ['path' => 'status', 'type' => 'string', 'example' => 'completed', 'description' => 'Status tindakan.'],
                ['path' => 'code.coding[0].code', 'type' => 'string', 'example' => '89.52', 'description' => 'Kode tindakan.'],
                ['path' => 'subject.reference', 'type' => 'string', 'example' => 'Patient/patient-001', 'description' => 'Referensi ke pasien.'],
            ],
            'template' => [
                'resourceType' => 'Procedure',
                'id' => 'procedure-001',
                'status' => 'completed',
                'code' => [
                    'coding' => [
                        [
                            'system' => 'https://rs.example.id/procedure',
                            'code' => '89.52',
                            'display' => 'Electrocardiogram',
                        ],
                    ],
                ],
                'subject' => ['reference' => 'Patient/patient-001'],
                'encounter' => ['reference' => 'Encounter/encounter-001'],
                'performedPeriod' => [
                    'start' => '2025-03-21T09:00:00+07:00',
                    'end' => '2025-03-21T09:30:00+07:00',
                ],
            ],
        ],
        'diagnostic_report' => [
            'label' => 'DiagnosticReport',
            'description' => 'Hasil lab, radiologi, atau pemeriksaan penunjang lain.',
            'multiple' => true,
            'required_fields' => [
                ['path' => 'resourceType', 'type' => 'string', 'example' => 'DiagnosticReport', 'description' => 'Harus bernilai DiagnosticReport.'],
                ['path' => 'id', 'type' => 'string', 'example' => 'diag-report-001', 'description' => 'ID unik laporan.'],
                ['path' => 'status', 'type' => 'string', 'example' => 'final', 'description' => 'Status laporan.'],
                ['path' => 'code.coding[0].code', 'type' => 'string', 'example' => 'LAB-001', 'description' => 'Kode jenis pemeriksaan.'],
                ['path' => 'subject.reference', 'type' => 'string', 'example' => 'Patient/patient-001', 'description' => 'Referensi ke pasien.'],
            ],
            'template' => [
                'resourceType' => 'DiagnosticReport',
                'id' => 'diag-report-001',
                'status' => 'final',
                'code' => [
                    'coding' => [
                        [
                            'system' => 'https://rs.example.id/lab',
                            'code' => 'LAB-001',
                            'display' => 'Hematology panel',
                        ],
                    ],
                ],
                'subject' => ['reference' => 'Patient/patient-001'],
                'encounter' => ['reference' => 'Encounter/encounter-001'],
                'issued' => '2025-03-22T10:15:00+07:00',
                'conclusion' => 'Hasil dalam batas normal',
            ],
        ],
        'observation' => [
            'label' => 'Observation',
            'description' => 'Nilai hasil pemeriksaan detail yang bisa dipakai di dalam DiagnosticReport.',
            'multiple' => true,
            'required_fields' => [
                ['path' => 'resourceType', 'type' => 'string', 'example' => 'Observation', 'description' => 'Harus bernilai Observation.'],
                ['path' => 'id', 'type' => 'string', 'example' => 'observation-001', 'description' => 'ID unik observation.'],
                ['path' => 'status', 'type' => 'string', 'example' => 'final', 'description' => 'Status observation.'],
                ['path' => 'code.coding[0].code', 'type' => 'string', 'example' => '718-7', 'description' => 'Kode observasi.'],
                ['path' => 'subject.reference', 'type' => 'string', 'example' => 'Patient/patient-001', 'description' => 'Referensi ke pasien.'],
            ],
            'template' => [
                'resourceType' => 'Observation',
                'id' => 'observation-001',
                'status' => 'final',
                'code' => [
                    'coding' => [
                        [
                            'system' => 'http://loinc.org',
                            'code' => '718-7',
                            'display' => 'Hemoglobin',
                        ],
                    ],
                ],
                'subject' => ['reference' => 'Patient/patient-001'],
                'effectiveDateTime' => '2025-03-22T10:10:00+07:00',
                'valueQuantity' => [
                    'value' => 13.1,
                    'unit' => 'g/dL',
                ],
            ],
        ],
        'medication_request' => [
            'label' => 'MedicationRequest',
            'description' => 'Resep atau permintaan obat pasien.',
            'multiple' => true,
            'required_fields' => [
                ['path' => 'resourceType', 'type' => 'string', 'example' => 'MedicationRequest', 'description' => 'Harus bernilai MedicationRequest.'],
                ['path' => 'id', 'type' => 'string', 'example' => 'medication-001', 'description' => 'ID unik resep.'],
                ['path' => 'status', 'type' => 'string', 'example' => 'completed', 'description' => 'Status resep.'],
                ['path' => 'intent', 'type' => 'string', 'example' => 'order', 'description' => 'Intent resep.'],
                ['path' => 'subject.reference', 'type' => 'string', 'example' => 'Patient/patient-001', 'description' => 'Referensi ke pasien.'],
                ['path' => 'medicationCodeableConcept.coding[0].code', 'type' => 'string', 'example' => 'OBT-001', 'description' => 'Kode obat.'],
            ],
            'template' => [
                'resourceType' => 'MedicationRequest',
                'id' => 'medication-001',
                'status' => 'completed',
                'intent' => 'order',
                'subject' => ['reference' => 'Patient/patient-001'],
                'encounter' => ['reference' => 'Encounter/encounter-001'],
                'authoredOn' => '2025-03-22T11:00:00+07:00',
                'requester' => ['reference' => 'Practitioner/practitioner-001'],
                'medicationCodeableConcept' => [
                    'coding' => [
                        [
                            'system' => 'https://rs.example.id/formularium',
                            'code' => 'OBT-001',
                            'display' => 'Paracetamol 500 mg tablet',
                        ],
                    ],
                ],
                'dosageInstruction' => [
                    [
                        'text' => '3x1 sesudah makan',
                    ],
                ],
            ],
        ],
    ],
];
