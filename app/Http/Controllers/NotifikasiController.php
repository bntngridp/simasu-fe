<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class NotifikasiController extends Controller
{
    private function apiGet(string $path): array
    {
        $response = Http::withoutVerifying()
            ->timeout(10)
            ->withToken(session('api_token'))
            ->get(config('api.base_url') . $path);

        if (!$response->successful()) {
            return [];
        }

        return $this->normalizeList($response->json());
    }

    private function normalizeList($payload): array
    {
        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        if (is_array($payload) && array_is_list($payload)) {
            return $payload;
        }

        return [];
    }

    private function criticalInventory(array $inventory): array
    {
        return collect($inventory)
            ->filter(fn ($item) => (int) ($item['stock'] ?? $item['jumlah'] ?? 0) <= 5)
            ->map(fn ($item) => [
                'id' => $item['id'] ?? null,
                'name' => $item['name'] ?? $item['nama_barang'] ?? 'Barang',
                'stock' => (int) ($item['stock'] ?? $item['jumlah'] ?? 0),
                'category' => $item['category'] ?? $item['kategori'] ?? '-',
            ])
            ->values()
            ->all();
    }

    private function pendingBookings(array $bookings): array
    {
        return collect($bookings)
            ->filter(fn ($item) => ($item['status'] ?? '') === 'pending')
            ->map(fn ($item) => [
                'id' => $item['id'] ?? null,
                'item_name' => $item['item_name'] ?? $item['name'] ?? 'Item',
                'user_name' => $item['user_name'] ?? $item['user']['name'] ?? 'User',
                'status' => $item['status'] ?? 'pending',
            ])
            ->values()
            ->all();
    }

    private function upcomingEvents(array $events): array
    {
        return collect($events)
            ->map(function ($item) {
                $date = $item['event_date'] ?? $item['datetime'] ?? null;

                return [
                    'id' => $item['id'] ?? null,
                    'title' => $item['title'] ?? 'Event',
                    'location' => $item['location'] ?? '-',
                    'datetime' => $date,
                    'date_object' => $date ? Carbon::parse($date) : null,
                ];
            })
            ->filter(fn ($item) => $item['date_object'] && $item['date_object']->isFuture())
            ->sortBy('date_object')
            ->take(5)
            ->values()
            ->all();
    }

    public function index()
    {
        $criticalInventory = $this->criticalInventory($this->apiGet('/api/inventory'));
        $pendingBookings = $this->pendingBookings($this->apiGet('/api/bookings'));
        $upcomingEvents = $this->upcomingEvents($this->apiGet('/api/events'));

        $summary = [
            'total' => count($criticalInventory) + count($pendingBookings) + count($upcomingEvents),
            'critical_inventory' => count($criticalInventory),
            'pending_bookings' => count($pendingBookings),
            'upcoming_events' => count($upcomingEvents),
        ];

        return view('notifikasi.index', compact(
            'summary',
            'criticalInventory',
            'pendingBookings',
            'upcomingEvents'
        ));
    }
}
