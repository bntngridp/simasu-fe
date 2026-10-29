<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardBookingActivityTest extends TestCase
{
    public function test_dashboard_shows_recent_booking_activity(): void
    {
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/announcements' => Http::response([], 200),
            $baseUrl . '/api/events' => Http::response([], 200),
            $baseUrl . '/api/inventory' => Http::response([], 200),
            $baseUrl . '/api/rooms' => Http::response([], 200),
            $baseUrl . '/api/profile' => Http::response([], 200),
            $baseUrl . '/api/bookings' => Http::response([
                [
                    'id' => 15,
                    'item_name' => 'Aula Utama',
                    'user_name' => 'Indra',
                ],
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Permintaan baru masuk');
        $response->assertSee('Aula Utama oleh Indra');
    }
}
