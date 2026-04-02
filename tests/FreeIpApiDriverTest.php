<?php

use Geolax\FreeIpApi\FreeIpApiDriver;
use Geolax\Geolocate\Contracts\Driver;
use Geolax\Geolocate\Data\GeolocationResult;
use Geolax\Geolocate\Exceptions\LookupFailedException;
use Geolax\Geolocate\Facades\Geolocate;
use Geolax\Geolocate\GeolocateManager;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Http;

it('registers the freeipapi driver with the manager', function () {
    $manager = app(GeolocateManager::class);
    $driver = $manager->driver('freeipapi');

    expect($driver)->toBeInstanceOf(Driver::class);
    expect($driver)->toBeInstanceOf(FreeIpApiDriver::class);
});

it('is the default driver', function () {
    $manager = app(GeolocateManager::class);

    expect($manager->getDefaultDriver())->toBe('freeipapi');
});

it('looks up an IP address and returns a GeolocationResult', function () {
    Http::fake([
        'freeipapi.com/api/json/1.1.1.1' => Http::response([
            'ipVersion' => 4,
            'ipAddress' => '1.1.1.1',
            'latitude' => -33.8688,
            'longitude' => 151.209,
            'countryName' => 'Australia',
            'countryCode' => 'AU',
            'capital' => 'Canberra',
            'phoneCodes' => [61],
            'timeZones' => ['Australia/Sydney', 'Australia/Melbourne'],
            'zipCode' => '4000',
            'cityName' => 'Sydney',
            'regionName' => 'New South Wales',
            'regionCode' => 'NSW',
            'continent' => 'Oceania',
            'continentCode' => 'OC',
            'currencies' => ['AUD'],
            'languages' => ['en'],
            'asn' => '13335',
            'asnOrganization' => 'Cloudflare, Inc.',
            'isProxy' => false,
        ]),
    ]);

    $result = Geolocate::lookup('1.1.1.1');

    expect($result)
        ->toBeInstanceOf(GeolocationResult::class)
        ->ipVersion->toBe(4)
        ->ipAddress->toBe('1.1.1.1')
        ->latitude->toBe(-33.8688)
        ->longitude->toBe(151.209)
        ->countryName->toBe('Australia')
        ->countryCode->toBe('AU')
        ->regionName->toBe('New South Wales')
        ->regionCode->toBe('NSW')
        ->cityName->toBe('Sydney')
        ->zipCode->toBe('4000')
        ->timezone->toBe('Australia/Sydney')
        ->continent->toBe('Oceania')
        ->continentCode->toBe('OC')
        ->currency->toBe('AUD')
        ->driver->toBe('freeipapi');
});

it('preserves raw API response in the DTO', function () {
    Http::fake([
        'freeipapi.com/api/json/8.8.8.8' => Http::response([
            'ipVersion' => 4,
            'ipAddress' => '8.8.8.8',
            'countryName' => 'United States',
            'countryCode' => 'US',
            'asn' => '15169',
            'asnOrganization' => 'Google LLC',
            'isProxy' => false,
        ]),
    ]);

    $result = Geolocate::lookup('8.8.8.8');

    expect($result->raw)
        ->toBeArray()
        ->toHaveKey('asn', '15169')
        ->toHaveKey('asnOrganization', 'Google LLC')
        ->toHaveKey('isProxy', false);
});

it('throws LookupFailedException on HTTP error', function () {
    Http::fake([
        'freeipapi.com/api/json/invalid' => Http::response('Not Found', 404),
    ]);

    Geolocate::lookup('invalid');
})->throws(LookupFailedException::class);

it('throws LookupFailedException on network error', function () {
    Http::fake([
        'freeipapi.com/*' => function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        },
    ]);

    Geolocate::lookup('1.1.1.1');
})->throws(LookupFailedException::class);

it('looks up without specifying an IP (current request)', function () {
    Http::fake([
        'freeipapi.com/api/json' => Http::response([
            'ipVersion' => 4,
            'ipAddress' => '203.0.113.1',
            'countryName' => 'Example',
            'countryCode' => 'EX',
        ]),
    ]);

    $result = Geolocate::lookup();

    expect($result)
        ->ipAddress->toBe('203.0.113.1')
        ->countryCode->toBe('EX');
});

it('sends authorization header when api_key is configured', function () {
    config(['geolocate.drivers.freeipapi.api_key' => 'test-secret-key']);

    Http::fake([
        '*' => Http::response([
            'ipVersion' => 4,
            'ipAddress' => '1.1.1.1',
            'countryName' => 'Australia',
            'countryCode' => 'AU',
        ]),
    ]);

    // Force a fresh manager so it picks up the new config
    app()->forgetInstance(GeolocateManager::class);
    app(GeolocateManager::class)->lookup('1.1.1.1');

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer test-secret-key');
    });
});

it('uses the correct server URL based on config', function () {
    config(['geolocate.drivers.freeipapi.server' => 'us']);

    Http::fake([
        'us.freeipapi.com/*' => Http::response([
            'ipVersion' => 4,
            'ipAddress' => '1.1.1.1',
            'countryName' => 'Australia',
            'countryCode' => 'AU',
        ]),
    ]);

    app()->forgetInstance(GeolocateManager::class);
    app(GeolocateManager::class)->lookup('1.1.1.1');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'us.freeipapi.com');
    });
});
