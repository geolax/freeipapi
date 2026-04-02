<?php

namespace Geolax\FreeIpApi;

use Geolax\Geolocate\GeolocateManager;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\ServiceProvider;

class FreeIpApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->resolving(GeolocateManager::class, function (GeolocateManager $manager) {
            $manager->extend('freeipapi', function ($app, array $config) {
                return new FreeIpApiDriver(
                    http: $app->make(HttpClient::class),
                    config: $config,
                );
            });
        });
    }
}
