<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The API domain's root shows plain text instead of Laravel's default
     * welcome page, which needlessly advertises the exact framework/PHP
     * version to any browser visitor here.
     */
    public function test_the_root_shows_plain_text_instead_of_the_default_welcome_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Chinesechess Online API');
    }
}
