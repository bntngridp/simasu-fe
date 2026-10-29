<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HaikalLayoutTest extends TestCase
{
    public function test_sidebar_shows_logged_in_user_information(): void
    {
        $this->withoutVite();
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/announcements' => Http::response([], 200),
            $baseUrl . '/api/events' => Http::response([], 200),
            $baseUrl . '/api/inventory' => Http::response([], 200),
            $baseUrl . '/api/rooms' => Http::response([], 200),
            $baseUrl . '/api/profile' => Http::response([], 200),
            $baseUrl . '/api/bookings' => Http::response([], 200),
        ]);

        $response = $this->withSession([
            'api_token' => 'token',
            'user' => [
                'name' => 'Haikal Admin',
                'role' => 'admin',
            ],
        ])->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Haikal Admin');
        $response->assertSee('Admin');
    }

    public function test_global_success_flash_message_is_rendered_from_layout(): void
    {
        $this->withoutVite();
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/announcements' => Http::response([], 200),
            $baseUrl . '/api/events' => Http::response([], 200),
            $baseUrl . '/api/inventory' => Http::response([], 200),
            $baseUrl . '/api/rooms' => Http::response([], 200),
            $baseUrl . '/api/profile' => Http::response([], 200),
            $baseUrl . '/api/bookings' => Http::response([], 200),
        ]);

        $response = $this->withSession([
            'api_token' => 'token',
            'success' => 'Data berhasil disimpan',
        ])->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Data berhasil disimpan');
        $response->assertSee('alert-success');
        $response->assertSee('btn-close');
    }

    public function test_global_error_flash_message_is_rendered_from_layout(): void
    {
        $this->withoutVite();
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/announcements' => Http::response([], 200),
            $baseUrl . '/api/events' => Http::response([], 200),
            $baseUrl . '/api/inventory' => Http::response([], 200),
            $baseUrl . '/api/rooms' => Http::response([], 200),
            $baseUrl . '/api/profile' => Http::response([], 200),
            $baseUrl . '/api/bookings' => Http::response([], 200),
        ]);

        $response = $this->withSession([
            'api_token' => 'token',
            'error' => 'Data gagal disimpan',
        ])->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Data gagal disimpan');
        $response->assertSee('alert-danger');
        $response->assertSee('btn-close');
    }
}
