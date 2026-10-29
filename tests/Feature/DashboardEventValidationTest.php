<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardEventValidationTest extends TestCase
{
    public function test_event_date_cannot_be_in_the_past(): void
    {
        $response = $this->withSession(['api_token' => 'token'])->post(route('events.store'), [
            'title' => 'Kajian Subuh',
            'subtitle' => 'Kajian rutin',
            'datetime' => now()->subDay()->format('Y-m-d\TH:i'),
            'location' => 'Aula Utama',
        ]);

        $response->assertSessionHasErrors('datetime');
    }

    public function test_event_payload_uses_consistent_datetime_format(): void
    {
        $baseUrl = config('api.base_url');
        $eventTime = Carbon::now()->addDay()->setTime(8, 30);

        Http::fake([
            $baseUrl . '/api/events' => Http::response(['id' => 10], 201),
        ]);

        $this->withSession(['api_token' => 'token'])->post(route('events.store'), [
            'title' => 'Rapat DKM',
            'subtitle' => 'Koordinasi bulanan',
            'datetime' => $eventTime->format('Y-m-d\TH:i'),
            'location' => 'Ruang Rapat',
        ])->assertRedirect(route('dashboard'));

        Http::assertSent(function ($request) use ($eventTime) {
            return $request->url() === config('api.base_url') . '/api/events'
                && $request['title'] === 'Rapat DKM'
                && $request['datetime'] === $eventTime->format('Y-m-d H:i');
        });
    }
}
