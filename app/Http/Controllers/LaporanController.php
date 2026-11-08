<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $report = $this->buildReport($request);

        return view('laporan.index', compact('report'));
    }

    public function export(Request $request)
    {
        $report = $this->buildReport($request);
        $report['date'] = now()->format('d F Y H:i');

        $pdf = Pdf::loadView('laporan.pdf', compact('report'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('Laporan_SIMASU_' . now()->format('Ymd') . '.pdf');
    }

    public function exportCsv(Request $request)
    {
        $report = $this->buildReport($request);
        $rows = [
            ['Bagian', 'Metrik', 'Jumlah'],
            ['Inventaris', 'Total barang', $report['inventory']['total_items']],
            ['Inventaris', 'Total stok', $report['inventory']['total_stock']],
            ['Inventaris', 'Stok terbatas', $report['inventory']['low_stock']],
            ['Inventaris', 'Stok habis', $report['inventory']['out_of_stock']],
            ['Ruangan', 'Total ruangan', $report['rooms']['total_rooms']],
            ['Ruangan', 'Total kapasitas', $report['rooms']['capacity_sum']],
            ['Permintaan', 'Total pengajuan', $report['bookings']['total']],
            ['Permintaan', 'Disetujui', $report['bookings']['approved']],
            ['Permintaan', 'Pending', $report['bookings']['pending']],
            ['Permintaan', 'Ditolak', $report['bookings']['rejected']],
        ];

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            foreach ($rows as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        }, 'Laporan_SIMASU_' . now()->format('Ymd') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildReport(Request $request): array
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date', 'required_with:end_date'],
            'end_date' => ['nullable', 'date', 'required_with:start_date', 'after_or_equal:start_date'],
        ]);
        $baseUrl = config('api.base_url');
        $token = session('api_token');
        $http = Http::withoutVerifying()->withToken($token);

        $inventoryRes = $http->get($baseUrl . '/api/inventory');
        $roomsRes = $http->get($baseUrl . '/api/rooms');
        $bookingsRes = $http->get($baseUrl . '/api/bookings');

        $inventory = collect($this->responseItems($inventoryRes));
        $rooms = collect($this->responseItems($roomsRes));
        $bookings = collect($this->responseItems($bookingsRes));
        $bookings = $this->filterBookingsByDate($bookings, $filters);

        return [
            'inventory' => [
                'total_items' => $inventory->count(),
                'total_stock' => $inventory->sum('stock'),
                'low_stock' => $inventory->where('stock', '<=', 10)->where('stock', '>', 0)->count(),
                'out_of_stock' => $inventory->where('stock', '<=', 0)->count(),
            ],
            'rooms' => [
                'total_rooms' => $rooms->count(),
                'capacity_sum' => $rooms->sum('capacity'),
            ],
            'bookings' => [
                'total' => $bookings->count(),
                'pending' => $bookings->where('status', 'pending')->count(),
                'approved' => $bookings->where('status', 'approved')->count(),
                'rejected' => $bookings->where('status', 'rejected')->count(),
            ],
            'filters' => [
                'start_date' => $filters['start_date'] ?? '',
                'end_date' => $filters['end_date'] ?? '',
            ],
        ];
    }

    private function responseItems($response): array
    {
        if (! $response->successful()) {
            return [];
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return [];
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        return array_is_list($payload) ? $payload : [];
    }

    private function filterBookingsByDate($bookings, array $filters)
    {
        if (empty($filters['start_date']) && empty($filters['end_date'])) {
            return $bookings;
        }

        $dateFields = [
            'start_time', 'tanggal_mulai', 'start_date', 'startTime', 'tanggalMulai',
            'date', 'booking_date', 'created_at', 'start', 'mulai', 'waktu_mulai',
        ];

        return $bookings->filter(function ($booking) use ($dateFields, $filters) {
            if (! is_array($booking)) {
                return false;
            }

            $bookingDate = null;
            foreach ($dateFields as $field) {
                if (! empty($booking[$field])) {
                    $bookingDate = $booking[$field];
                    break;
                }
            }

            if (! $bookingDate) {
                return false;
            }

            try {
                $bookingDate = Carbon::parse($bookingDate)->toDateString();
            } catch (\Throwable) {
                return false;
            }

            return (! isset($filters['start_date']) || $bookingDate >= $filters['start_date'])
                && (! isset($filters['end_date']) || $bookingDate <= $filters['end_date']);
        })->values();
    }
}