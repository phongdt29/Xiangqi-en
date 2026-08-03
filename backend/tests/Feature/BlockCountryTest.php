<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BlockCountryTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_by_default_allows_any_country(): void
    {
        Http::fake(['*' => Http::response('VN')]);

        $this->getJson('/api/leaderboard')->assertOk();

        Http::assertNothingSent();
    }

    public function test_blocks_a_request_geolocated_to_vietnam(): void
    {
        Config::set('geoblock.enabled', true);
        Http::fake(['ipapi.co/*' => Http::response('VN')]);

        $this->getJson('/api/leaderboard')->assertStatus(403);
    }

    public function test_allows_a_non_blocked_country(): void
    {
        Config::set('geoblock.enabled', true);
        Http::fake(['ipapi.co/*' => Http::response('US')]);

        $this->getJson('/api/leaderboard')->assertOk();
    }

    public function test_whitelisted_ip_bypasses_the_country_check(): void
    {
        Config::set('geoblock.enabled', true);
        Config::set('geoblock.whitelist_ips', ['127.0.0.1']);
        Http::fake(['ipapi.co/*' => Http::response('VN')]);

        $this->getJson('/api/leaderboard')->assertOk();

        Http::assertNothingSent();
    }

    public function test_fails_open_when_the_geolocation_lookup_errors(): void
    {
        Config::set('geoblock.enabled', true);
        Http::fake(['ipapi.co/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timed out')]);

        $this->getJson('/api/leaderboard')->assertOk();
    }
}
