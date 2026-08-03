<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class BlockCountry
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('geoblock.enabled')) {
            return $next($request);
        }

        $ip = $request->ip();

        if (in_array($ip, config('geoblock.whitelist_ips'), true)) {
            return $next($request);
        }

        $country = $this->countryFor($ip);

        if ($country !== null && in_array($country, config('geoblock.blocked_countries'), true)) {
            return response()->json(['message' => 'This service is not available in your region.'], 403);
        }

        return $next($request);
    }

    /**
     * Looks up the 2-letter country code for an IP via a free geolocation
     * API, cached for a day per IP. Returns null (fail-open) on any lookup
     * failure - a third-party outage should degrade to "not enforced", not
     * take the whole API down for every country.
     */
    private function countryFor(string $ip): ?string
    {
        return Cache::remember("geoip:country:{$ip}", now()->addDay(), function () use ($ip) {
            try {
                return trim(Http::timeout(3)->get("https://ipapi.co/{$ip}/country/")->body());
            } catch (\Throwable $e) {
                report($e);

                return null;
            }
        });
    }
}
