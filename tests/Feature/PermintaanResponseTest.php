<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PermintaanResponseTest extends TestCase
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

    public function test_index_handles_plain_array_response(): void
    {
        $this->withoutVite();
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/bookings' => Http::response([
                $this->makeFakeBooking(1, 'pending'),
                $this->makeFakeBooking(2, 'approved'),
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('permintaan.index'));

        $response->assertOk();
        $response->assertSee('User 1');
        $response->assertSee('User 2');
    }

    public function test_index_handles_wrapped_data_response(): void
    {
        $this->withoutVite();
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/bookings' => Http::response([
                'data' => [
                    $this->makeFakeBooking(1, 'pending'),
                    $this->makeFakeBooking(2, 'rejected'),
                ],
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('permintaan.index'));

        $response->assertOk();
        $response->assertSee('User 1');
        $response->assertSee('User 2');
    }

    public function test_index_handles_unexpected_response_format(): void
    {
        $this->withoutVite();
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/bookings' => Http::response([
                'message' => 'ok',
                'count' => 0,
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('permintaan.index'));

        $response->assertOk();
        $response->assertSee('Tidak ada data permintaan.');
    }

    public function test_index_sorts_pending_first(): void
    {
        $this->withoutVite();
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/bookings' => Http::response([
                $this->makeFakeBooking(1, 'approved'),
                $this->makeFakeBooking(2, 'pending'),
                $this->makeFakeBooking(3, 'rejected'),
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('permintaan.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['User 2', 'User 1', 'User 3']);
    }
}
