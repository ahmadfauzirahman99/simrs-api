<?php

namespace App\SatuSehat;

use App\SatuSehat\Auth\OAuth2Client;
use App\SatuSehat\FHIR\AccountBuilder;
use App\SatuSehat\FHIR\AllergyIntoleranceBuilder;
use App\SatuSehat\FHIR\ChargeItemBuilder;
use App\SatuSehat\FHIR\ClaimBuilder;
use App\SatuSehat\FHIR\ConditionBuilder;
use App\SatuSehat\FHIR\CoverageBuilder;
use App\SatuSehat\FHIR\DiagnosticReportBuilder;
use App\SatuSehat\FHIR\EncounterBuilder;
use App\SatuSehat\FHIR\ImmunizationBuilder;
use App\SatuSehat\FHIR\InvoiceBuilder;
use App\SatuSehat\FHIR\MedicationDispenseBuilder;
use App\SatuSehat\FHIR\MedicationRequestBuilder;
use App\SatuSehat\FHIR\ObservationBuilder;
use App\SatuSehat\FHIR\ProcedureBuilder;
use App\SatuSehat\FHIR\ServiceRequestBuilder;
use App\SatuSehat\Http\FhirClient;
use App\SatuSehat\Support\Config;

class SatuSehatManager
{
    public function __construct(
        private readonly Config      $config,
        private readonly FhirClient  $fhirClient,
        private readonly OAuth2Client $oauth,
    ) {}

    public function encounter(): EncounterBuilder
    {
        return new EncounterBuilder($this->config->credentials()['org_id']);
    }

    public function condition(): ConditionBuilder
    {
        return new ConditionBuilder($this->config->credentials()['org_id']);
    }

    public function observation(): ObservationBuilder
    {
        return new ObservationBuilder($this->config->credentials()['org_id']);
    }

    public function procedure(): ProcedureBuilder
    {
        return new ProcedureBuilder($this->config->credentials()['org_id']);
    }

    public function serviceRequest(): ServiceRequestBuilder
    {
        return new ServiceRequestBuilder($this->config->credentials()['org_id']);
    }

    public function fhir(): FhirClient
    {
        return $this->fhirClient;
    }

    public function oauth(): OAuth2Client
    {
        return $this->oauth;
    }

    public function coverage(): CoverageBuilder
    {
        $creds = $this->config->credentials();
        return new CoverageBuilder($creds['org_id'], $creds['bpjs_org_id'] ?? '');
    }

    public function account(): AccountBuilder
    {
        return new AccountBuilder($this->config->credentials()['org_id']);
    }

    public function chargeItem(): ChargeItemBuilder
    {
        return new ChargeItemBuilder($this->config->credentials()['org_id']);
    }

    public function invoice(): InvoiceBuilder
    {
        return new InvoiceBuilder($this->config->credentials()['org_id']);
    }

    public function claim(): ClaimBuilder
    {
        $creds = $this->config->credentials();
        return new ClaimBuilder($creds['org_id'], $creds['bpjs_org_id'] ?? '');
    }

    public function allergyIntolerance(): AllergyIntoleranceBuilder
    {
        return new AllergyIntoleranceBuilder($this->config->credentials()['org_id']);
    }

    public function medicationRequest(): MedicationRequestBuilder
    {
        return new MedicationRequestBuilder($this->config->credentials()['org_id']);
    }

    public function medicationDispense(): MedicationDispenseBuilder
    {
        return new MedicationDispenseBuilder($this->config->credentials()['org_id']);
    }

    public function diagnosticReport(): DiagnosticReportBuilder
    {
        return new DiagnosticReportBuilder($this->config->credentials()['org_id']);
    }

    public function immunization(): ImmunizationBuilder
    {
        return new ImmunizationBuilder($this->config->credentials()['org_id']);
    }

    public function findPatientByNik(string $nik): ?string
    {
        $result = $this->fhirClient->get('Patient', [
            'identifier' => 'https://fhir.kemkes.go.id/id/nik|' . $nik,
        ]);

        return $result['entry'][0]['resource']['id'] ?? null;
    }
}
