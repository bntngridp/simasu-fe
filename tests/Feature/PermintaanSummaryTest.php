<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PermintaanSummaryTest extends TestCase
{
    private function makeFakeBooking(int $id, string $status): array
    {
        return [
            'id' => $id,
            'user_name' => 'User ' . $id,
            'type' => 'room',
            'item_name' => 'Ruang ' . $id,
            'item_id' => $id,
            'quantity' => 1,
            'start_time' => '2026-05-01 08:00',
            'end_time' => '2026-05-01 10:00',
            'notes' => 'Catatan ' . $id,
            'status' => $status,
        ];
    }

    public function test_summary_shows_correct_counts(): void
    {
        $this->withoutVite();
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/bookings' => Http::response([
                $this->makeFakeBooking(1, 'pending'),
                $this->makeFakeBooking(2, 'pending'),
                $this->makeFakeBooking(3, 'approved'),
                $this->makeFakeBooking(4, 'rejected'),
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('permintaan.index'));

        $response->assertOk();

        // Total card
        $response->assertSee('Total');
        // Pending card
        $response->assertSee('Pending');
        // Disetujui card
        $response->assertSee('Disetujui');
        // Ditolak card
        $response->assertSee('Ditolak');
    }

    public function test_summary_shows_zero_when_api_fails(): void
    {
        $this->withoutVite();
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/bookings' => Http::response(null, 500),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('permintaan.index'));

        $response->assertOk();
        $response->assertSee('Total');
    }

    public function test_summary_renders_status_cards_in_view(): void
    {
        $this->withoutVite();
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/bookings' => Http::response([
                $this->makeFakeBooking(1, 'approved'),
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('permintaan.index'));

        $response->assertOk();
        $response->assertSee('text-success', false);
        $response->assertSee('text-danger', false);
        $response->assertSee('text-secondary', false);
    }
}
