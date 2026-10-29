<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CommandPaletteTest extends TestCase
{
    public function test_layout_contains_command_palette_navigation(): void
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

        $response = $this->withSession(['api_token' => 'token'])->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Navigasi Cepat');
        $response->assertSee('commandPaletteModal');
        $response->assertSee('Ctrl');
        $response->assertSee('Inventaris');
        $response->assertSee('Permintaan');
        $response->assertSee('Tidak ada menu yang cocok');
    }
}
