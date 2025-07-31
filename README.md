# Bad IP Blocker for Laravel

Block incoming requests from known bad IP addresses using a centralized JSON feed.

This package fetches a list of bad IPs from a remote API hosted by [Mattitja AB](https://mattitja.se) and caches them locally. Requests from matching IPs are immediately blocked with a `418` response.

## Features

- Automatically blocks known malicious IPs
- Pulls IP list from a centralized JSON endpoint
- Caches data locally to avoid repeated API calls
- Designed to run globally across all routes

## Installation

```bash
composer require mattitjaab/bad-ip-blocker
```

## Usage

### Register as global middleware in Laravel 12

Edit `bootstrap/app.php`:

```php
use Mattitja\BadIpBlocker\Middleware\CheckBadIps;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        ...
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(CheckBadIps::class);
    })
    ->create();
```

This ensures the middleware runs globally on every incoming request.

## How it works

- On each request, the middleware checks if the client's IP exists in a cached JSON file.
- If the cache is missing or older than one hour, it attempts to refresh from `https://bad-ip.mattitja.cloud/api/json`.
- If the IP is found in the list, the request is blocked with a `418` response.

## Cached file location

Cached data is stored at:

```
storage/app/bad_ips.json
```

Delete this file to force a refresh.

## Example response when blocked

```
HTTP/1.1 418 Blocked
Content-Type: text/plain

Blocked.
```

## Maintained by Mattitja AB

This package is maintained by [Mattitja AB](https://mattitja.se) and is intended for internal use across multiple projects.

## License

MIT License © Mattitja AB
