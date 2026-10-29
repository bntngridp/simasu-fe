<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PermintaanViewTest extends TestCase
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

    public function test_permintaan_page_has_search_input(): void
    {
        $this->withoutVite();
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/bookings' => Http::response([
                $this->makeFakeBooking(1, 'pending'),
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('permintaan.index'));

        $response->assertOk();
        $response->assertSee('requestSearch', false);
        $response->assertSee('Cari peminjam, item, atau catatan...', false);
    }

    public function test_permintaan_page_has_status_filter(): void
    {
        $this->withoutVite();
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/bookings' => Http::response([
                $this->makeFakeBooking(1, 'pending'),
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('permintaan.index'));

        $response->assertOk();
        $response->assertSee('requestStatusFilter', false);
        $response->assertSee('Semua Status');
    }

    public function test_permintaan_rows_have_data_attributes(): void
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
        $response->assertSee('data-request-row', false);
        $response->assertSee('data-status="pending"', false);
        $response->assertSee('data-status="approved"', false);
    }

    public function test_permintaan_page_has_filter_script(): void
    {
        $this->withoutVite();
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/bookings' => Http::response([], 200),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('permintaan.index'));

        $response->assertOk();
        $response->assertSee('filterRequestRows', false);
    }
}
