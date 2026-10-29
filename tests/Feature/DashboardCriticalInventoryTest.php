<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardCriticalInventoryTest extends TestCase
{
    public function test_dashboard_shows_critical_inventory_count(): void
    {
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/announcements' => Http::response([], 200),
            $baseUrl . '/api/events' => Http::response([], 200),
            $baseUrl . '/api/inventory' => Http::response([
                ['id' => 1, 'name' => 'Sajadah', 'stock' => 0],
                ['id' => 2, 'name' => 'Mikrofon', 'stock' => 3],
                ['id' => 3, 'name' => 'Kursi', 'stock' => 8],
            ], 200),
            $baseUrl . '/api/rooms' => Http::response([], 200),
            $baseUrl . '/api/profile' => Http::response([], 200),
            $baseUrl . '/api/bookings' => Http::response([], 200),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Inventaris Kritis');
        $response->assertSee('<h3 class="fw-bold text-warning">2</h3>', false);
    }
}
