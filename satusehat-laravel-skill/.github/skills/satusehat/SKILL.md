---
name: satusehat
description: "Use when: building Satu Sehat FHIR R4 integration in Laravel. Use for: membuat integrasi Satu Sehat Kemkes Indonesia, FHIR R4 resource builders, OAuth2 token management, ServiceProvider, Facade, kirim data encounter/condition/observation/procedure ke API Satu Sehat, integrasi rumah sakit dengan platform Kemenkes."
argument-hint: "Describe the FHIR resource or feature to build (e.g. EncounterBuilder, send condition, find patient by NIK)"
---

# Satu Sehat FHIR R4 — Laravel Integration Skill

## When to Use
- Membangun integrasi Satu Sehat (Kementerian Kesehatan Indonesia)
- Membuat atau memodifikasi FHIR R4 resource builder
- Konfigurasi OAuth2 token untuk Satu Sehat
- Mengirim data klinis: encounter, condition, observation, procedure, medication, dll
- Membuat ServiceProvider, Facade, atau config untuk package Laravel
- Troubleshoot error 401/403/422 dari API Kemkes

## Architecture Overview

```
app/SatuSehat/               ← atau package: src/
  Auth/
    OAuth2Client.php          — Client credentials grant + token cache
  Contracts/
    HttpClientInterface.php   — Interface untuk mock/custom HTTP
    TokenStoreInterface.php   — Interface untuk Redis/DB/File token store
  Http/
    HttpClient.php            — Pure cURL implementation
    FhirClient.php            — FHIR HTTP client + 401 auto-retry
    HttpException.php         — Exception dengan getStatusCode() / getBody()
  Support/
    Config.php                — Load config dari Laravel config/satusehat.php
    TokenStore.php            — File JSON cache (default), implements TokenStoreInterface
    DateHelper.php            — safeDateTime(), skip 0000-00-00 dan epoch 1970
  FHIR/
    BaseFhirResource.php      — Abstract; $orgId di-inject via constructor
    EncounterBuilder.php
    ConditionBuilder.php
    ObservationBuilder.php
    ProcedureBuilder.php
    ServiceRequestBuilder.php
    MedicationBuilder.php
    MedicationRequestBuilder.php
    ImagingStudyBuilder.php
    CompositionBuilder.php
    PatientBuilder.php
    LocationBuilder.php
  SatuSehatManager.php        — Entry point, factory semua builder
  SatuSehatServiceProvider.php
  SatuSehatFacade.php
```

## Step-by-Step Procedures

### 1. Membuat ServiceProvider
```php
// app/Providers/SatuSehatServiceProvider.php
public function register(): void
{
    $this->mergeConfigFrom(__DIR__.'/../../config/satusehat.php', 'satusehat');

    $this->app->singleton('satusehat', function ($app) {
        $config = new Config($app['config']['satusehat']);
        $http   = new HttpClient();
        $store  = new TokenStore(storage_path('framework/cache/satusehat_token.json'));
        $oauth  = new OAuth2Client($config, $http, $store);
        $fhir   = new FhirClient($config, $http, $oauth);
        return new SatuSehatManager($config, $fhir, $oauth);
    });
}

public function boot(): void
{
    $this->publishes([
        __DIR__.'/../../config/satusehat.php' => config_path('satusehat.php'),
    ], 'satusehat-config');
}
```

### 2. Config Structure (wajib diikuti)
Lihat: [references/api-config.md](./references/api-config.md)

### 3. Membuat FHIR Builder
Setiap builder **wajib**:
- Extend `BaseFhirResource`
- Menerima `string $orgId` via constructor (delegated dari Base)
- Method chaining (`return $this`) untuk semua setter
- `toArray(): array` — return state tanpa validasi
- `json(): string` — validasi + `json_encode(toArray())`

```php
class ConditionBuilder extends BaseFhirResource
{
    private array $condition = [];

    public function setSubject(string $patientId, string $name): self
    {
        $this->condition['subject'] = [
            'reference' => "Patient/{$patientId}",
            'display'   => $name,
        ];
        return $this;
    }

    public function toArray(): array { return $this->condition; }
    public function json(): string   { return json_encode($this->toArray()); }
}
```

### 4. OAuth2 Token Flow
```
OAuth2Client::token()
  → cek TokenStore::get()
  → jika expired/null: POST /oauth2/v1/accesstoken
  → simpan ke TokenStore::put()

FhirClient::execute()
  → inject Bearer token
  → jika 401: OAuth2Client::flushToken() → retry 1x
  → jika masih 401: throw HttpException
```

### 5. Mengirim Resource ke Satu Sehat
```php
$ss = app('satusehat');

// Buat encounter
$encounter = $ss->encounter()
    ->setStatus('in-progress')
    ->setConsultationMethod('RAJAL')
    ->setSubject($patientIhsId, $patientName)
    ->setParticipant($practitionerIhsId, $practitionerName)
    ->createEncounter($dateTimeStart, $locationId);

$response = $ss->fhir()->post('Encounter', $encounter->toArray());
```

### 6. Error Handling Pattern
```php
use App\SatuSehat\Http\HttpException;

try {
    $response = $ss->fhir()->post('Encounter', $payload);
} catch (HttpException $e) {
    Log::error('Satu Sehat error', [
        'status' => $e->getStatusCode(),
        'body'   => $e->getBody(),
    ]);
}
```

### 7. Find Patient by NIK
```php
$result = $ss->fhir()->get('Patient', ['identifier' => 'https://fhir.kemkes.go.id/id/nik|' . $nik]);
$ihsNumber = $result['entry'][0]['resource']['id'] ?? null;
```

## FHIR Builder Reference
Detail semua builder, method, dan payload: [references/fhir-builders.md](./references/fhir-builders.md)

## API Endpoints & Config
URL sandbox/production dan struktur config: [references/api-config.md](./references/api-config.md)

## Laravel-Specific Patterns
Facade, Artisan commands, Queue jobs: [references/laravel-structure.md](./references/laravel-structure.md)

## Important Rules
- **Tidak boleh** type-hint ke `HttpClient` langsung — selalu pakai `HttpClientInterface`
- **Tidak boleh** type-hint ke `TokenStore` langsung — selalu pakai `TokenStoreInterface`
- `$orgId` selalu di-inject via constructor, tidak pernah hardcode atau ambil dari `config()` langsung di builder
- `Config::credentials()` lempar `RuntimeException` (bukan `InvalidArgumentException`) saat field kosong
- Identifier system yang menyertakan orgId: `http://sys-ids.kemkes.go.id/{resource}/{orgId}`
- Untuk builder yang auto-set `managingOrganization` / identifier berbasis orgId: gunakan `$this->orgId` dari `BaseFhirResource`
