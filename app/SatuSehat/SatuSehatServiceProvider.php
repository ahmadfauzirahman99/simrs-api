<?php

namespace App\SatuSehat;

use App\SatuSehat\Auth\OAuth2Client;
use App\SatuSehat\Contracts\HttpClientInterface;
use App\SatuSehat\Contracts\TokenStoreInterface;
use App\SatuSehat\Http\FhirClient;
use App\SatuSehat\Http\HttpClient;
use App\SatuSehat\Support\Config;
use App\SatuSehat\Support\TokenStore;
use Illuminate\Support\ServiceProvider;
use App\Services\SatuSehat\ConditionSubmissionService;
use App\Services\SatuSehat\ObservationSubmissionService;
use App\Services\SatuSehat\ProcedureSubmissionService;
use App\Services\SatuSehat\ServiceRequestSubmissionService;

class SatuSehatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/satusehat.php', 'satusehat');

        $this->app->bind(HttpClientInterface::class, HttpClient::class);

        $this->app->singleton(TokenStoreInterface::class, function () {
            return new TokenStore(
                storage_path('framework/cache/satusehat_token.json')
            );
        });

        $this->app->singleton('satusehat', function ($app) {
            $config = new Config($app['config']['satusehat']);
            $http   = $app->make(HttpClientInterface::class);
            $store  = $app->make(TokenStoreInterface::class);
            $oauth  = new OAuth2Client($config, $http, $store);
            $fhir   = new FhirClient($config, $http, $oauth);

            return new SatuSehatManager($config, $fhir, $oauth);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/satusehat.php' => config_path('satusehat.php'),
        ], 'satusehat-config');

        // Bind submission services so controllers can type-hint SatuSehatManager
        $this->app->bind(SatuSehatManager::class, fn ($app) => $app->make('satusehat'));

        $this->app->bind(ConditionSubmissionService::class, fn ($app) =>
            new ConditionSubmissionService($app->make('satusehat'))
        );
        $this->app->bind(ObservationSubmissionService::class, fn ($app) =>
            new ObservationSubmissionService($app->make('satusehat'))
        );
        $this->app->bind(ProcedureSubmissionService::class, fn ($app) =>
            new ProcedureSubmissionService($app->make('satusehat'))
        );
        $this->app->bind(ServiceRequestSubmissionService::class, fn ($app) =>
            new ServiceRequestSubmissionService($app->make('satusehat'))
        );
    }
}
