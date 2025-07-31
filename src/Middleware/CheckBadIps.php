<?php

namespace Mattitja\BadIpBlocker\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class CheckBadIps
{
    protected string $source = 'https://bad-ip.mattitja.cloud/api/json';

    protected string $cachePath = 'bad_ips.json';

    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $ips = $this->getCachedIps();

        if (in_array($ip, $ips)) {
            return response('Blocked.', 418);
        }

        return $next($request);
    }

    protected function getCachedIps(): array
    {
        $fullPath = storage_path("app/{$this->cachePath}");

        $shouldUpdate = true;

        if (file_exists($fullPath)) {
            $data = json_decode(file_get_contents($fullPath), true);

            if (
                isset($data['updated_at']) &&
                now()->diffInHours(Carbon::parse($data['updated_at'])) <= 1
            ) {
                $shouldUpdate = false;
            }
        }

        if ($shouldUpdate) {
            $this->updateCache($fullPath);
            $data = json_decode(file_get_contents($fullPath), true);
        }

        return $data['ips'] ?? [];
    }

    protected function updateCache(string $path): void
    {
        try {
            $json = file_get_contents($this->source);
            if ($json) {
                file_put_contents($path, $json);
            }
        } catch (\Throwable $e) {
            // Silent fail
        }
    }
}
