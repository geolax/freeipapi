# Geolax FreeIPAPI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/geolax/freeipapi.svg?style=flat-square)](https://packagist.org/packages/geolax/freeipapi)
[![Total Downloads](https://img.shields.io/packagist/dt/geolax/freeipapi.svg?style=flat-square)](https://packagist.org/packages/geolax/freeipapi)

[FreeIPAPI](https://freeipapi.com) driver addon for [geolax/geolocate](https://github.com/geolax/geolocate).

## Installation

```bash
composer require geolax/freeipapi
```

That's it. The package is auto-discovered by Laravel and registers itself as the `freeipapi` driver.

## Configuration

Add or update the driver config in `config/geolocate.php`:

```php
'drivers' => [
    'freeipapi' => [
        'driver'   => 'freeipapi',
        'base_url' => env('GEOLOCATE_FREEIPAPI_URL', 'https://freeipapi.com'),
        'api_key'  => env('GEOLOCATE_FREEIPAPI_KEY'),
        'server'   => env('GEOLOCATE_FREEIPAPI_SERVER', 'free'),
        'timeout'  => 5,
    ],
],
```

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `GEOLOCATE_DRIVER` | `freeipapi` | Set this in the base package to use FreeIPAPI as default |
| `GEOLOCATE_FREEIPAPI_URL` | `https://freeipapi.com` | Base URL override |
| `GEOLOCATE_FREEIPAPI_KEY` | `null` | API key for paid plans (Bearer token) |
| `GEOLOCATE_FREEIPAPI_SERVER` | `free` | Server region: `free`, `us`, `de`, `sgp`, `au` |

### Servers

| Key | Host | Plan |
|-----|------|------|
| `free` | `freeipapi.com` | Free (rate limited) |
| `us` | `us.freeipapi.com` | Paid |
| `de` | `de.freeipapi.com` | Paid |
| `sgp` | `sgp.freeipapi.com` | Paid |
| `au` | `au.freeipapi.com` | Paid |

## Usage

```php
use Geolax\Geolocate\Facades\Geolocate;

$result = Geolocate::lookup('1.1.1.1');

$result->ipAddress;    // "1.1.1.1"
$result->countryName;  // "Australia"
$result->countryCode;  // "AU"
$result->cityName;     // "Sydney"
$result->latitude;     // -33.8688
$result->longitude;    // 151.209
$result->timezone;     // "Australia/Sydney"
$result->currency;     // "AUD"
```

### FreeIPAPI-Specific Data

FreeIPAPI returns additional fields beyond the standard DTO. Access them via `raw`:

```php
$result = Geolocate::lookup('1.1.1.1');

$result->raw['capital'];         // "Canberra"
$result->raw['phoneCodes'];      // [61]
$result->raw['timeZones'];       // ["Australia/Sydney", "Australia/Melbourne", ...]
$result->raw['currencies'];      // ["AUD"]
$result->raw['languages'];       // ["en"]
$result->raw['asn'];             // "13335"
$result->raw['asnOrganization']; // "Cloudflare, Inc."
$result->raw['isProxy'];         // false
```

### Authentication (Paid Plans)

For paid/unlimited plans, set your API key:

```env
GEOLOCATE_FREEIPAPI_KEY=your-api-key-here
GEOLOCATE_FREEIPAPI_SERVER=us
```

The driver automatically sends the API key as a Bearer token in the `Authorization` header.

## Testing

```bash
composer test
```

## Credits

- [Bishwajit Adhikary](https://github.com/bishwajitcadhikary)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
