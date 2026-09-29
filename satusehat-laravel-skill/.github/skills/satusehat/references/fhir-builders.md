# FHIR Builder Reference — Satu Sehat

## BaseFhirResource (Abstract)
```php
abstract class BaseFhirResource
{
    public function __construct(protected string $orgId) {}
    abstract public function toArray(): array;
    public function json(): string { return json_encode($this->toArray()); }
}
```

---

## EncounterBuilder

### Methods
| Method | Parameter | Keterangan |
|--------|-----------|------------|
| `setStatus(string)` | `arrived \| in-progress \| finished \| cancelled` | Status encounter |
| `setConsultationMethod(string)` | `RAJAL \| IGD \| RANAP \| HOMECARE \| TELEKONSULTASI` | Jenis konsultasi |
| `setSubject(string, string)` | `$patientIhsId, $patientName` | Wajib |
| `setParticipant(string, string)` | `$practitionerIhsId, $name` | Wajib |
| `setLocation(string, string)` | `$locationId, $locationName` | Lokasi pelayanan |
| `setServiceProvider(?string)` | orgId (default: `$this->orgId`) | Faskes pengirim |
| `addStatusHistory(array)` | `['arrived'=>DateTime, 'inprogress'=>DateTime, 'finished'=>DateTime]` | Riwayat status |
| `createEncounter(string, ?string, ?string)` | `$periodStart, $periodEnd, $classCode` | Rakit payload |

### Class Codes
`AMB` (outpatient/RAJAL), `EMER` (IGD), `IMP` (inpatient/RANAP), `HH` (home health)

### Contoh Payload
```php
$encounter = $ss->encounter()
    ->setStatus('finished')
    ->setConsultationMethod('RAJAL')
    ->setSubject('P02478375538', 'Budi Santoso')
    ->setParticipant('N10000001', 'dr. Ahmad')
    ->addStatusHistory([
        'arrived'    => '2024-01-15T08:00:00+07:00',
        'inprogress' => '2024-01-15T08:30:00+07:00',
        'finished'   => '2024-01-15T09:00:00+07:00',
    ])
    ->setLocation('a2355b76-...-location-id', 'Poli Umum')
    ->createEncounter('2024-01-15T08:00:00+07:00', '2024-01-15T09:00:00+07:00');
```

---

## ConditionBuilder

### Methods
| Method | Parameter | Keterangan |
|--------|-----------|------------|
| `addClinicalStatus(string)` | default `'active'` | active \| resolved \| inactive |
| `addCategory(string)` | `'diagnosis'` atau `'keluhan'` | Wajib |
| `addCode(string, string)` | `$icd10Code, $display` | Diagnosis ICD-10 |
| `setSubject(string, string)` | `$patientIhsId, $name` | **Wajib** sebelum json() |
| `setEncounter(string)` | encounterId | **Wajib** sebelum json() |
| `setOnsetDateTime(string)` | ISO 8601 | Waktu onset |
| `setRecordedDate(string)` | ISO 8601 | Waktu dicatat |

> `json()` melakukan validasi + auto-set defaults (clinicalStatus, verificationStatus, category)

---

## ObservationBuilder

### Methods
| Method | Parameter | Keterangan |
|--------|-----------|------------|
| `setStatus(string)` | `registered\|preliminary\|final\|amended` | Status observasi |
| `addCategory(string)` | `vital-signs\|laboratory\|imaging\|procedure\|survey\|exam` | Kategori |
| `addCode(string)` | kode LOINC | Auto-display untuk kode umum |
| `setSubject(string, string)` | patientIhsId, name | Wajib |
| `setEncounter(string)` | encounterId | Wajib |
| `setEffectiveDateTime(string)` | ISO 8601 | Waktu pengukuran |
| `setValueQuantity(float, string, string)` | value, unit, system | Hasil numerik |
| `setValueCodeableConcept(string, string, string)` | code, display, system | Hasil coded |
| `setValueString(string)` | — | Hasil teks |
| `addInterpretation(string, string)` | code, display | Normal/Abnormal |
| `addReferenceRange(float, float, string)` | low, high, unit | Rentang normal |
| `addComponent(string, array)` | loincCode, valueQuantity | Untuk multi-komponen |

### LOINC Codes dengan Auto-Display
| Code | Display |
|------|---------|
| `8480-6` | Systolic blood pressure |
| `8462-4` | Diastolic blood pressure |
| `8867-4` | Heart rate |
| `8310-5` | Body temperature |
| `9279-1` | Respiratory rate |
| `2708-6` | Oxygen saturation |
| `29463-7` | Body weight |
| `8302-2` | Body height |
| `39156-5` | BMI |

---

## ProcedureBuilder

### Methods
| Method | Parameter | Keterangan |
|--------|-----------|------------|
| `setStatus(string)` | default `'completed'` | Status prosedur |
| `addCode(string, string)` | SNOMED/ICD-9 code, display | Kode prosedur |
| `setCategoryDiagnostic()` | — | Shortcut SNOMED 103693007 |
| `setSubject(string, string)` | patientIhsId, name | Wajib |
| `setEncounter(string)` | encounterId | Wajib |
| `setPerformedPeriod(string, ?string)` | start, end | Periode prosedur |
| `addPerformer(string, string)` | practitionerIhsId, name | Dokter pelaksana |
| `setBodySite(string, string)` | SNOMED code, display | Lokasi tubuh |
| `addNote(string)` | teks catatan | Annotation |

---

## ServiceRequestBuilder

### Methods
| Method | Parameter | Keterangan |
|--------|-----------|------------|
| `setStatus(string)` | `draft\|active\|completed\|cancelled` | Status |
| `setIntent(string)` | default `'original-order'` | Intent permintaan |
| `setPriority(string)` | `routine\|urgent\|asap\|stat` | Prioritas |
| `addCode(string, string)` | SNOMED/internal code, display | Jenis permintaan |
| `setSubject(string, string)` | patientIhsId, name | Wajib |
| `setEncounter(string)` | encounterId | Wajib |
| `setRequester(string, string)` | practitionerIhsId, name | Dokter pemesan |
| `addReasonReference(string)` | conditionId | Alasan klinis |
| `setOccurrenceDateTime(string)` | ISO 8601 | Waktu dijadwalkan |

---

## MedicationBuilder

### Methods
| Method | Parameter | Keterangan |
|--------|-----------|------------|
| `setCodeKfa(string)` | kode KFA | System: `http://sys-ids.kemkes.go.id/kfa` |
| `addIdentifierOrg(string)` | nilai identifier | System: `http://sys-ids.kemkes.go.id/medication/{orgId}` |
| `setForm(string, string)` | code, display | System: `http://terminology.kemkes.go.id/CodeSystem/medication-form` |
| `addIngredientKfa(string, string, float, string, float, string)` | kfaCode, display, numeratorValue, numeratorUnit, denominatorValue, denominatorUnit | Komposisi bahan |
| `setStatus(string)` | `active\|inactive\|entered-in-error` | Status |
| `setManufacturer(string)` | orgId pabrik | Referensi produsen |

---

## MedicationRequestBuilder

### Methods
| Method | Parameter | Keterangan |
|--------|-----------|------------|
| `setStatus(string)` | `active\|completed\|cancelled` | Status resep |
| `setIntent(string)` | default `'order'` | Intent |
| `setMedication(string)` | medicationId | Referensi Medication resource |
| `setSubject(string, string)` | patientIhsId, name | Wajib |
| `setEncounter(string)` | encounterId | Wajib |
| `setRequester(string, string)` | practitionerIhsId, name | Dokter penulis resep |
| `addDosageInstruction(array)` | array dosage | Aturan pakai |
| `setDispenseRequest(int, string, int)` | quantity, unit, numberOfRepeatsAllowed | Permintaan dispensasi |

---

## ImagingStudyBuilder

### Methods
| Method | Parameter | Keterangan |
|--------|-----------|------------|
| `setStatus(string)` | `registered\|available\|cancelled\|entered-in-error` | Status |
| `addIdentifierAcsn(string)` | nilai ACSN | System: `http://sys-ids.kemkes.go.id/acsn/{orgId}` |
| `addIdentifierDicomUid(string)` | UID tanpa prefix | Prefix `urn:oid:` ditambah otomatis |
| `addModality(string)` | `CT\|MR\|DX\|CR\|US\|NM\|PT\|OT\|RF\|XA` | Modalitas alat |
| `setSubject(string, string)` | patientIhsId, name | Wajib |
| `setEncounter(string)` | encounterId | Wajib |
| `addSeries(array)` | array series data | Seri pencitraan |
| `setStarted(string)` | ISO 8601 | Waktu mulai studi |
| `setNumberOfSeries(int)` | — | Jumlah seri |
| `setNumberOfInstances(int)` | — | Jumlah instance |

---

## CompositionBuilder

### Methods
| Method | Parameter | Keterangan |
|--------|-----------|------------|
| `setIdentifierOrg(string)` | nilai identifier | System: `http://sys-ids.kemkes.go.id/composition/{orgId}` |
| `setStatus(string)` | `preliminary\|final\|amended\|entered-in-error` | Status |
| `setType(string, string)` | LOINC code, display | Tipe dokumen |
| `setSubject(string, string)` | patientIhsId, name | Wajib |
| `setEncounter(string)` | encounterId | Wajib |
| `setDate(string)` | ISO 8601 | Tanggal komposisi |
| `addAuthor(string, string)` | practitionerIhsId, name | Penulis |
| `setTitle(string)` | judul | Judul dokumen |
| `addSection(string, array)` | title, references | Bagian klinis |

---

## PatientBuilder

### Methods
| Method | Parameter | Keterangan |
|--------|-----------|------------|
| `addIdentifier(string, string)` | type, value | type: `nik\|nik-ibu\|paspor\|kk\|rekam-medis` |
| `addName(string, ?string)` | text, use | Nama pasien |
| `setGender(string)` | `male\|female\|other\|unknown` | Jenis kelamin |
| `setBirthDate(string)` | `YYYY-MM-DD` | Tanggal lahir |
| `addAddress(array)` | address data | Alamat pasien |
| `setActive(bool)` | — | Status aktif |

> Identifier system: `https://fhir.kemkes.go.id/id/{type}`

---

## LocationBuilder

### Methods
| Method | Parameter | Keterangan |
|--------|-----------|------------|
| `addIdentifier(string)` | nilai identifier | System: `http://sys-ids.kemkes.go.id/loc/{orgId}` |
| `setName(string)` | — | Nama lokasi |
| `setDescription(string)` | — | Deskripsi |
| `setMode(string)` | `instance\|kind` | Mode |
| `setStatus(string)` | `active\|suspended\|inactive` | Status |
| `addPhysicalType(string)` | `bu\|wi\|co\|ro\|ve\|ho\|ca\|rd\|area` | Tipe fisik |
| `addTelecom(string, string)` | system, value | Kontak |
| `setAddress(array)` | — | Alamat |
| `setManagingOrganization(?string)` | orgId (default: `$this->orgId`) | Auto-set jika null |

> `toArray()` auto-set `managingOrganization` dari `$this->orgId` jika belum di-set

---

## Identifier Systems Reference

| Resource | System |
|----------|--------|
| Patient NIK | `https://fhir.kemkes.go.id/id/nik` |
| Patient NIK Ibu | `https://fhir.kemkes.go.id/id/nik-ibu` |
| Medication | `http://sys-ids.kemkes.go.id/medication/{orgId}` |
| Location | `http://sys-ids.kemkes.go.id/loc/{orgId}` |
| ImagingStudy ACSN | `http://sys-ids.kemkes.go.id/acsn/{orgId}` |
| Composition | `http://sys-ids.kemkes.go.id/composition/{orgId}` |
| KFA (obat) | `http://sys-ids.kemkes.go.id/kfa` |
| Medication form | `http://terminology.kemkes.go.id/CodeSystem/medication-form` |
