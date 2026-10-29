<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    public function index()
    {
        $baseUrl = config('api.base_url');
        $token = session('api_token');
        $http = Http::withoutVerifying()->withToken($token);

        // Ambil semua data dari API
        $inventoryRes = $http->get($baseUrl . '/api/inventory');
        $roomsRes = $http->get($baseUrl . '/api/rooms');
        $bookingsRes = $http->get($baseUrl . '/api/bookings');

        // Olah Data Inventaris
        $inventory = collect($inventoryRes->successful() ? $inventoryRes->json() : []);
        $report['inventory'] = [
            'total_items' => $inventory->count(),
            'total_stock' => $inventory->sum('stock'),
            'low_stock'   => $inventory->where('stock', '<=', 10)->where('stock', '>', 0)->count(),
            'out_of_stock'=> $inventory->where('stock', '<=', 0)->count(),
        ];

        // Olah Data Ruangan
        $rooms = collect($roomsRes->successful() ? $roomsRes->json() : []);
        $report['rooms'] = [
            'total_rooms' => $rooms->count(),
            'capacity_sum'=> $rooms->sum('capacity'),
        ];

        // Olah Data Permintaan/Booking
        $bookings = collect($bookingsRes->successful() ? $bookingsRes->json() : []);
        $report['bookings'] = [
            'total'    => $bookings->count(),
            'pending'  => $bookings->where('status', 'pending')->count(),
            'approved' => $bookings->where('status', 'approved')->count(),
            'rejected' => $bookings->where('status', 'rejected')->count(),
        ];

        return view('laporan.index', compact('report'));
    }

    public function export()
    {
        $baseUrl = config('api.base_url');
        $token = session('api_token');
        $http = Http::withoutVerifying()->withToken($token);

        $inventoryRes = $http->get($baseUrl . '/api/inventory');
        $roomsRes = $http->get($baseUrl . '/api/rooms');
        $bookingsRes = $http->get($baseUrl . '/api/bookings');

        $inventory = collect($inventoryRes->successful() ? $inventoryRes->json() : []);
        $rooms = collect($roomsRes->successful() ? $roomsRes->json() : []);
        $bookings = collect($bookingsRes->successful() ? $bookingsRes->json() : []);

        $report = [
            'inventory' => [
                'total_items' => $inventory->count(),
                'total_stock' => $inventory->sum('stock'),
                'low_stock'   => $inventory->where('stock', '<=', 10)->where('stock', '>', 0)->count(),
                'out_of_stock'=> $inventory->where('stock', '<=', 0)->count(),
            ],
            'rooms' => [
                'total_rooms' => $rooms->count(),
                'capacity_sum'=> $rooms->sum('capacity'),
            ],
            'bookings' => [
                'total'    => $bookings->count(),
                'approved' => $bookings->where('status', 'approved')->count(),
                'pending'  => $bookings->where('status', 'pending')->count(),
                'rejected' => $bookings->where('status', 'rejected')->count(),
            ],
            'date' => now()->format('d F Y H:i'),
        ];

        $pdf = Pdf::loadView('laporan.pdf', compact('report'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('Laporan_SIMASU_' . now()->format('Ymd') . '.pdf');
        return back()->with('success', 'Laporan berhasil download.');
    }
}