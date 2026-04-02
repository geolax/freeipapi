<?php

namespace Geolax\FreeIpApi\Tests;

use Geolax\FreeIpApi\FreeIpApiServiceProvider;
use Geolax\Geolocate\GeolocateServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            GeolocateServiceProvider::class,
            FreeIpApiServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Geolocate' => \Geolax\Geolocate\Facades\Geolocate::class,
        ];
    }
}
