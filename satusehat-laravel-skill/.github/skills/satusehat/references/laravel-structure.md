# Laravel Structure Reference — Satu Sehat

## Facade

```php
<?php

namespace App\SatuSehat;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\SatuSehat\FHIR\EncounterBuilder encounter()
 * @method static \App\SatuSehat\FHIR\ConditionBuilder condition()
 * @method static \App\SatuSehat\FHIR\ObservationBuilder observation()
 * @method static \App\SatuSehat\FHIR\ProcedureBuilder procedure()
 * @method static \App\SatuSehat\FHIR\ServiceRequestBuilder serviceRequest()
 * @method static \App\SatuSehat\FHIR\MedicationBuilder medication()
 * @method static \App\SatuSehat\FHIR\MedicationRequestBuilder medicationRequest()
 * @method static \App\SatuSehat\FHIR\ImagingStudyBuilder imagingStudy()
 * @method static \App\SatuSehat\FHIR\CompositionBuilder composition()
 * @method static \App\SatuSehat\FHIR\PatientBuilder patient()
 * @method static \App\SatuSehat\FHIR\LocationBuilder location()
 * @method static \App\SatuSehat\Http\FhirClient fhir()
 * @method static \App\SatuSehat\Auth\OAuth2Client oauth()
 * @method static string|null findPatientByNik(string $nik)
 */
class SatuSehatFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'satusehat';
    }
}
```

### Register Alias di config/app.php
```php
'aliases' => [
    // ...
    'SatuSehat' => \App\SatuSehat\SatuSehatFacade::class,
],
```

---

## SatuSehatManager (Entry Point)

```php
<?php

namespace App\SatuSehat;

class SatuSehatManager
{
    public function __construct(
        private Config $config,
        private FhirClient $fhirClient,
        private OAuth2Client $oauth,
    ) {}

    public function encounter(): EncounterBuilder
    {
        return new EncounterBuilder($this->config->credentials()['org_id']);
    }

    public function condition(): ConditionBuilder
    {
        return new ConditionBuilder($this->config->credentials()['org_id']);
    }

    // ... builder lainnya

    public function fhir(): FhirClient { return $this->fhirClient; }
    public function oauth(): OAuth2Client { return $this->oauth; }

    public function findPatientByNik(string $nik): ?string
    {
        $result = $this->fhirClient->get('Patient', [
            'identifier' => 'https://fhir.kemkes.go.id/id/nik|' . $nik,
        ]);
        return $result['entry'][0]['resource']['id'] ?? null;
    }
}
```

---

## Controller Example

```php
<?php

namespace App\Http\Controllers;

use App\SatuSehat\SatuSehatFacade as SatuSehat;
use App\SatuSehat\Http\HttpException;
use Illuminate\Http\JsonResponse;

class SatuSehatController extends Controller
{
    public function getToken(): JsonResponse
    {
        try {
            $token = SatuSehat::oauth()->token();
            return response()->json(['token' => $token]);
        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function findPatient(string $nik): JsonResponse
    {
        $ihsId = SatuSehat::findPatientByNik($nik);
        return response()->json(['ihs_number' => $ihsId]);
    }

    public function sendEncounter(Request $request): JsonResponse
    {
        $encounter = SatuSehat::encounter()
            ->setStatus('finished')
            ->setConsultationMethod($request->input('method', 'RAJAL'))
            ->setSubject($request->input('patient_ihs_id'), $request->input('patient_name'))
            ->setParticipant($request->input('practitioner_ihs_id'), $request->input('practitioner_name'))
            ->setLocation($request->input('location_id'), $request->input('location_name'))
            ->createEncounter($request->input('period_start'), $request->input('period_end'));

        try {
            $response = SatuSehat::fhir()->post('Encounter', $encounter->toArray());
            return response()->json($response, 201);
        } catch (HttpException $e) {
            return response()->json(json_decode($e->getBody(), true), $e->getStatusCode());
        }
    }
}
```

---

## Routes

```php
// routes/api.php
use App\Http\Controllers\SatuSehatController;

Route::prefix('satusehat')->middleware('auth:sanctum')->group(function () {
    Route::get('/token',           [SatuSehatController::class, 'getToken']);
    Route::get('/patient/{nik}',   [SatuSehatController::class, 'findPatient']);
    Route::post('/encounter',      [SatuSehatController::class, 'sendEncounter']);
    Route::post('/condition',      [SatuSehatController::class, 'sendCondition']);
    Route::post('/observation',    [SatuSehatController::class, 'sendObservation']);
});
```

---

## Queue Job (Async Send)

```php
<?php

namespace App\Jobs;

use App\SatuSehat\SatuSehatFacade as SatuSehat;
use App\SatuSehat\Http\HttpException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEncounterToSatuSehat implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;   // retry setelah 60 detik

    public function __construct(private array $encounterData) {}

    public function handle(): void
    {
        $encounter = SatuSehat::encounter()
            ->setStatus($this->encounterData['status'])
            ->setConsultationMethod($this->encounterData['method'])
            ->setSubject($this->encounterData['patient_ihs_id'], $this->encounterData['patient_name'])
            ->setParticipant($this->encounterData['practitioner_ihs_id'], $this->encounterData['practitioner_name'])
            ->createEncounter($this->encounterData['period_start'], $this->encounterData['period_end']);

        SatuSehat::fhir()->post('Encounter', $encounter->toArray());
    }

    public function failed(HttpException $e): void
    {
        \Log::error('SendEncounterToSatuSehat failed', [
            'status' => $e->getStatusCode(),
            'body'   => $e->getBody(),
            'data'   => $this->encounterData,
        ]);
    }
}
```

### Dispatch Job
```php
SendEncounterToSatuSehat::dispatch($encounterData)->onQueue('satusehat');
```

---

## Artisan Commands (Opsional)

```php
<?php

namespace App\Console\Commands;

use App\SatuSehat\SatuSehatFacade as SatuSehat;
use Illuminate\Console\Command;

class SatuSehatFlushToken extends Command
{
    protected $signature   = 'satusehat:flush-token';
    protected $description = 'Flush cached Satu Sehat OAuth token';

    public function handle(): void
    {
        SatuSehat::oauth()->flushToken();
        $this->info('Token cache flushed.');
    }
}
```

Register di `app/Console/Kernel.php` atau via `routes/console.php` (Laravel 11+):
```php
Artisan::command('satusehat:flush-token', function () {
    app('satusehat')->oauth()->flushToken();
    $this->info('Token cache flushed.');
});
```

---

## Testing (Unit)

```php
<?php

namespace Tests\Unit;

use App\SatuSehat\Auth\OAuth2Client;
use App\SatuSehat\Contracts\HttpClientInterface;
use App\SatuSehat\Contracts\TokenStoreInterface;
use App\SatuSehat\Http\FhirClient;
use App\SatuSehat\Support\Config;
use PHPUnit\Framework\TestCase;

class FhirClientTest extends TestCase
{
    private function makeConfig(): Config
    {
        return Config::fromArray([
            'env' => 'DEV',
            'dev' => [
                'auth_url'      => 'https://auth.example.com',
                'fhir_url'      => 'https://fhir.example.com',
                'client_id'     => 'test-id',
                'client_secret' => 'test-secret',
                'org_id'        => 'test-org',
            ],
            'prod' => [ /* ... */ ],
        ]);
    }

    public function test_post_sends_correct_request(): void
    {
        $http  = $this->createMock(HttpClientInterface::class);
        $store = $this->createMock(TokenStoreInterface::class);

        $store->method('get')->willReturn([
            'access_token' => 'mock-token',
            'expires_at'   => time() + 3600,
        ]);

        $http->expects($this->once())
            ->method('request')
            ->willReturn(['status' => 201, 'body' => '{"resourceType":"Encounter","id":"abc"}']);

        $config = $this->makeConfig();
        $oauth  = new OAuth2Client($config, $http, $store);
        $client = new FhirClient($config, $http, $oauth);

        $result = $client->post('Encounter', ['resourceType' => 'Encounter']);
        $this->assertSame('abc', $result['id']);
    }
}
```

---

## DateHelper Usage

```php
use App\SatuSehat\Support\DateHelper;

// Skip 0000-00-00 dan epoch 1970 dari database legacy
$tgl = DateHelper::safeDateTime(
    [$dbRow['tgl_kunjungan'], $dbRow['created_at']],  // fallback chain
    addHours: 7,          // WIB offset
    timezone: 'Asia/Jakarta'
);
// Returns formatted ISO 8601 string or null
```

`safeDateTime()` otomatis skip:
- String kosong / null
- `0000-00-00` / `0000-00-00 00:00:00`
- Epoch 1970
- Tanggal sebelum `2014-06-03` (launch Satu Sehat)
