<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_adds_security_headers_to_every_response(): void
    {
        $response = $this->getJson('/api/leaderboard');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'geolocation=(), camera=(), microphone=()');
    }

    public function test_does_not_force_https_or_send_hsts_outside_production(): void
    {
        $response = $this->getJson('/api/leaderboard');

        $response->assertOk();
        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_redirects_plain_http_to_https_in_production(): void
    {
        // app()->environment() reads the container's 'env' binding set at
        // boot, not config('app.env') - this is the documented way to flip
        // it at runtime for a test.
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->get('/api/leaderboard');

        $response->assertRedirect('https://localhost/api/leaderboard');
    }

    public function test_sends_hsts_header_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        // Symfony's Request::create() derives the HTTPS server var from the
        // URI's own scheme (overriding anything passed in $server), so the
        // URI itself has to be https:// to make $request->secure() true.
        $response = $this->get('https://localhost/api/leaderboard');

        $response->assertOk();
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
