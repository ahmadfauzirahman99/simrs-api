<?php

namespace App\SatuSehat;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\SatuSehat\FHIR\EncounterBuilder       encounter()
 * @method static \App\SatuSehat\FHIR\ConditionBuilder        condition()
 * @method static \App\SatuSehat\FHIR\ObservationBuilder      observation()
 * @method static \App\SatuSehat\FHIR\ProcedureBuilder        procedure()
 * @method static \App\SatuSehat\FHIR\ServiceRequestBuilder   serviceRequest()
 * @method static \App\SatuSehat\Http\FhirClient              fhir()
 * @method static \App\SatuSehat\Auth\OAuth2Client            oauth()
 * @method static string|null                                  findPatientByNik(string $nik)
 *
 * @see \App\SatuSehat\SatuSehatManager
 */
class SatuSehatFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'satusehat';
    }
}
