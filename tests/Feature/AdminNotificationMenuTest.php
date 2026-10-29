<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminNotificationMenuTest extends TestCase
{
    public function test_notification_page_shows_admin_priority_summary(): void
    {
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/inventory' => Http::response([
                ['id' => 1, 'name' => 'Sajadah', 'stock' => 2],
                ['id' => 2, 'name' => 'Kursi Lipat', 'stock' => 8],
            ], 200),
            $baseUrl . '/api/bookings' => Http::response([
                ['id' => 10, 'item_name' => 'Aula Utama', 'user_name' => 'Indra', 'status' => 'pending'],
                ['id' => 11, 'item_name' => 'Mikrofon', 'user_name' => 'Rafa', 'status' => 'approved'],
            ], 200),
            $baseUrl . '/api/events' => Http::response([
                [
                    'id' => 20,
                    'title' => 'Kajian Subuh',
                    'location' => 'Masjid Utama',
                    'event_date' => now()->addDay()->format('Y-m-d H:i:s'),
                ],
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('notifikasi.index'));

        $response->assertOk();
        $response->assertSee('Notifikasi Admin');
        $response->assertSee('Stok Kritis');
        $response->assertSee('Sajadah');
        $response->assertSee('Booking Pending');
        $response->assertSee('Aula Utama');
        $response->assertSee('Event Terdekat');
        $response->assertSee('Kajian Subuh');
    }

    public function test_sidebar_and_command_palette_include_notification_menu(): void
    {
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
        $response->assertSee('Notifikasi');
        $response->assertSee(route('notifikasi.index'), false);
        $response->assertSee('data-command-keywords="notifikasi admin stok kritis booking pending event"', false);
    }
}
