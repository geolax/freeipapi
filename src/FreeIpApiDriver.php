<?php

namespace Geolax\FreeIpApi;

use Geolax\Geolocate\Contracts\Driver;
use Geolax\Geolocate\Data\GeolocationResult;
use Geolax\Geolocate\Exceptions\LookupFailedException;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Client\PendingRequest;

class FreeIpApiDriver implements Driver
{
    /**
     * Server hostname mapping.
     *
     * @var array<string, string>
     */
    protected const array SERVER_MAP = [
        'free' => 'freeipapi.com',
        'us' => 'us.freeipapi.com',
        'de' => 'de.freeipapi.com',
        'sgp' => 'sgp.freeipapi.com',
        'au' => 'au.freeipapi.com',
    ];

    /**
     * @param  array{
     *     base_url?: string,
     *     api_key?: string|null,
     *     server?: string,
     *     timeout?: int,
     * }  $config
     */
    public function __construct(
        protected HttpClient $http,
        protected array $config = [],
    ) {}

    public function lookup(?string $ip = null): GeolocationResult
    {
        try {
            $response = $this->buildRequest()
                ->get($this->buildUrl($ip));

            if ($response->failed()) {
                throw LookupFailedException::make(
                    'freeipapi',
                    "HTTP {$response->status()}: {$response->body()}",
                );
            }

            /** @var array<string, mixed> $data */
            $data = $response->json();

            return $this->mapToResult($data);
        } catch (LookupFailedException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw LookupFailedException::make('freeipapi', $exception->getMessage(), $exception);
        }
    }

    /**
     * Build the HTTP request with appropriate headers and timeout.
     */
    protected function buildRequest(): PendingRequest
    {
        $request = $this->http
            ->timeout($this->config['timeout'] ?? 5)
            ->acceptJson();

        $apiKey = $this->config['api_key'] ?? null;

        if ($apiKey) {
            $request->withToken($apiKey);
        }

        return $request;
    }

    /**
     * Build the full API URL for the given IP address.
     */
    protected function buildUrl(?string $ip): string
    {
        $server = $this->config['server'] ?? 'free';
        $host = self::SERVER_MAP[$server] ?? $this->config['base_url'] ?? 'https://freeipapi.com';

        // If the host is a full URL already, use it. Otherwise, build one.
        if (! str_starts_with($host, 'http')) {
            $host = "https://{$host}";
        }

        $path = '/api/json';

        if ($ip !== null) {
            $path .= '/' . urlencode($ip);
        }

        return rtrim($host, '/') . $path;
    }

    /**
     * Map the raw FreeIPAPI response data to a GeolocationResult DTO.
     *
     * @param  array<string, mixed>  $data
     */
    protected function mapToResult(array $data): GeolocationResult
    {
        return new GeolocationResult(
            ipVersion: $data['ipVersion'] ?? null,
            ipAddress: $data['ipAddress'] ?? null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            countryName: $data['countryName'] ?? null,
            countryCode: $data['countryCode'] ?? null,
            regionName: $data['regionName'] ?? null,
            regionCode: $data['regionCode'] ?? null,
            cityName: $data['cityName'] ?? null,
            zipCode: $data['zipCode'] ?? null,
            timezone: $this->extractTimezone($data),
            continent: $data['continent'] ?? null,
            continentCode: $data['continentCode'] ?? null,
            currency: $this->extractCurrency($data),
            driver: 'freeipapi',
            raw: $data,
        );
    }

    /**
     * Extract the primary timezone from the response.
     *
     * FreeIPAPI returns an array of timezones — we take the first one
     * and store the full list in raw.
     */
    protected function extractTimezone(array $data): ?string
    {
        $timezones = $data['timeZones'] ?? [];

        return is_array($timezones) && count($timezones) > 0
            ? $timezones[0]
            : null;
    }

    /**
     * Extract the primary currency from the response.
     *
     * FreeIPAPI returns an array of currencies — we take the first one.
     */
    protected function extractCurrency(array $data): ?string
    {
        $currencies = $data['currencies'] ?? [];

        return is_array($currencies) && count($currencies) > 0
            ? $currencies[0]
            : null;
    }
}
