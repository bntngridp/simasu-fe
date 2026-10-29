<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KalenderYudhaTest extends TestCase
{
    public function test_calendar_shows_today_button_and_monthly_summary(): void
    {
        $this->withoutVite();
        $baseUrl = config('api.base_url');
        $now = Carbon::now();

        Http::fake([
            $baseUrl . '/api/bookings' => Http::response([
                [
                    'id' => 1,
                    'type' => 'room',
                    'item_name' => 'Aula Utama',
                    'user_name' => 'Rafa',
                    'start_time' => $now->copy()->day(5)->format('Y-m-d H:i:s'),
                    'end_time' => $now->copy()->day(5)->addHours(2)->format('Y-m-d H:i:s'),
                    'status' => 'pending',
                ],
                [
                    'id' => 2,
                    'type' => 'inventory',
                    'item_name' => 'Sajadah',
                    'user_name' => 'Haikal',
                    'start_time' => $now->copy()->day(6)->format('Y-m-d H:i:s'),
                    'end_time' => $now->copy()->day(6)->addHours(2)->format('Y-m-d H:i:s'),
                    'status' => 'approved',
                    'quantity' => 2,
                ],
            ], 200),
            $baseUrl . '/api/inventory' => Http::response([], 200),
            $baseUrl . '/api/rooms' => Http::response([], 200),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('kalender'));

        $response->assertOk();
        $response->assertSee('Hari Ini');
        $response->assertSee('Ringkasan Bulan Ini');
        $response->assertSee('Total');
        $response->assertSee('Pending');
        $response->assertSee('Ruangan');
        $response->assertSee('Barang');
    }

    public function test_booking_end_time_must_be_after_start_time(): void
    {
        $response = $this->withSession(['api_token' => 'token'])->post(route('kalender.store'), [
            'type' => 'room',
            'item_id' => 1,
            'item_name' => 'Aula Utama',
            'start_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '17:00',
            'end_date' => now()->addDay()->format('Y-m-d'),
            'end_time' => '08:00',
            'notes' => 'Test',
        ]);

        $response->assertRedirect(route('kalender'));
        $response->assertSessionHas('error', 'Waktu selesai harus setelah waktu mulai.');
    }

    public function test_valid_booking_posts_payload_to_api(): void
    {
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/bookings' => Http::response(['id' => 20], 201),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->post(route('kalender.store'), [
            'type' => 'inventory',
            'item_id' => 5,
            'item_name' => 'Sajadah',
            'start_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_date' => now()->addDay()->format('Y-m-d'),
            'end_time' => '10:00',
            'quantity' => 3,
            'notes' => 'Dipakai acara pagi',
        ]);

        $response->assertRedirect(route('kalender'));

        Http::assertSent(function ($request) use ($baseUrl) {
            return $request->url() === $baseUrl . '/api/bookings'
                && $request->method() === 'POST'
                && $request['type'] === 'inventory'
                && $request['item_id'] === 5
                && $request['quantity'] === 3
                && $request['status'] === 'pending';
        });
    }
}
