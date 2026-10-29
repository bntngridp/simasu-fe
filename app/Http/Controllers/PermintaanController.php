<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PermintaanController extends Controller
{
    private $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = config('api.base_url');
    }

    private function normalizeBookingsResponse($payload): array
    {
        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        if (is_array($payload) && array_is_list($payload)) {
            return $payload;
        }

        return [];
    }

    private function buildStatusSummary($bookings): array
    {
        $collection = collect($bookings);

        return [
            'total' => $collection->count(),
            'pending' => $collection->where('status', 'pending')->count(),
            'approved' => $collection->where('status', 'approved')->count(),
            'rejected' => $collection->where('status', 'rejected')->count(),
        ];
    }

    public function index()
    {
        $token = session('api_token'); 

        $response = Http::withToken($token)->get($this->apiBaseUrl . '/api/bookings');

        $bookings = [];
        $statusSummary = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];

        if ($response->successful()) {
            $data = $response->json();
            $items = $this->normalizeBookingsResponse($data);

            $bookings = collect($items)->sortBy(function ($item) {
                return match ($item['status'] ?? '') {
                    'pending' => 0,
                    'approved' => 1,
                    'rejected' => 2,
                    default => 3,
                };
            })->values();

            $statusSummary = $this->buildStatusSummary($bookings);
        }

        return view('permintaan.index', compact('bookings', 'statusSummary'));
    }

    public function updateStatus(Request $request, $id)
    {
        $token = session('api_token');
        
        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $response = Http::withToken($token)->put($this->apiBaseUrl . '/api/bookings/' . $id . '/status', [
            'status' => $request->status
        ]);

        if ($response->successful()) {
            return redirect()->back()->with('success', 'Status berhasil diubah ke ' . $request->status);
        } else {
            return redirect()->back()->with('error', 'Gagal mengubah status: ' . $response->body());
        }
    }
}