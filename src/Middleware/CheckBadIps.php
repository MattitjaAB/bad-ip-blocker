<?php

namespace Mattitja\BadIpBlocker\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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
        $data = [];

        if (Storage::exists($this->cachePath)) {
            $data = json_decode(Storage::get($this->cachePath), true);

            $updatedAt = $data['updated_at'] ?? null;

            if ($updatedAt && Carbon::parse($updatedAt)->diffInHours() <= 24) {
                return $data['ips'] ?? [];
            }
        }

        $this->updateCache();

        $data = json_decode(Storage::get($this->cachePath), true);

        return $data['ips'] ?? [];
    }

    protected function updateCache(): void
    {
        try {
            $response = Http::get($this->source);

            if ($response->ok()) {
                Storage::put($this->cachePath, $response->body());
            }
        } catch (\Throwable $e) {
        }
    }
}
