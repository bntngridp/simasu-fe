<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class KalenderController extends Controller
{
    public function index(Request $request)
    {
        $baseUrl = config('api.base_url');
        $token = session('api_token');

        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $currentDate = Carbon::create($year, $month, 1);

        try {
            $http = Http::withoutVerifying()->timeout(10);

            if ($token) {
                $http = $http->withToken($token);
            }

            $bookingsResponse = $http->get($baseUrl . '/api/bookings');
            $inventoryResponse = Http::withoutVerifying()->timeout(5)->get($baseUrl . '/api/inventory');
            $roomsResponse = Http::withoutVerifying()->timeout(5)->get($baseUrl . '/api/rooms');

            Log::info('Bookings Response Status: ' . $bookingsResponse->status());
            Log::info('Bookings Response Body: ' . $bookingsResponse->body());

            $bookings = [];
            $inventory = [];
            $rooms = [];

            if ($bookingsResponse->successful()) {
                try {
                    $bookingsData = $bookingsResponse->json();
                    Log::info('Parsed Bookings JSON:', ['type' => gettype($bookingsData)]);

                    if (is_array($bookingsData) && !isset($bookingsData['data']) && !isset($bookingsData['message'])) {
                        $bookings = $bookingsData;
                        Log::info('Using direct array, count: ' . count($bookings));
                    } elseif (isset($bookingsData['data']) && is_array($bookingsData['data'])) {
                        $bookings = $bookingsData['data'];
                        Log::info('Using data wrapper, count: ' . count($bookings));
                    } elseif (isset($bookingsData['bookings']) && is_array($bookingsData['bookings'])) {
                        $bookings = $bookingsData['bookings'];
                        Log::info('Using bookings wrapper, count: ' . count($bookings));
                    } elseif (isset($bookingsData['success']) && isset($bookingsData['data'])) {
                        $bookings = is_array($bookingsData['data']) ? $bookingsData['data'] : [];
                        Log::info('Using success wrapper, count: ' . count($bookings));
                    }

                    Log::info('Final bookings count: ' . count($bookings));
                    if (count($bookings) > 0) {
                        Log::info('First booking sample:', ['booking' => $bookings[0]]);
                        Log::info('Available keys:', ['keys' => array_keys($bookings[0])]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error parsing bookings JSON: ' . $e->getMessage());
                }
            } else {
                Log::warning('Bookings API failed with status: ' . $bookingsResponse->status());
                Log::warning('Response body: ' . $bookingsResponse->body());

                if ($bookingsResponse->status() === 401) {
                    Log::error('Authentication required for /api/bookings endpoint');
                }
            }

            if ($inventoryResponse->successful()) {
                $inventoryData = $inventoryResponse->json();
                $inventory = isset($inventoryData['data']) ? $inventoryData['data'] : $inventoryData;
            }

            if ($roomsResponse->successful()) {
                $roomsData = $roomsResponse->json();
                $rooms = isset($roomsData['data']) ? $roomsData['data'] : $roomsData;
            }

            $inventory = collect($inventory)->map(function ($item) {
                return [
                    'id' => $item['id'] ?? 0,
                    'name' => $item['name'] ?? $item['nama_barang'] ?? '-',
                    'stock' => $item['stock'] ?? $item['jumlah'] ?? 0,
                    'category' => $item['category'] ?? $item['kategori'] ?? '-',
                ];
            })->toArray();

            $rooms = collect($rooms)->map(function ($item) {
                $status = $item['status'] ?? 'tersedia';
                $isAvailable = isset($item['is_available'])
                    ? (bool)$item['is_available']
                    : in_array(strtolower($status), ['tersedia', 'available', 'aktif', 'active']);

                return [
                    'id' => $item['id'] ?? 0,
                    'name' => $item['name'] ?? $item['nama_ruangan'] ?? '-',
                    'status' => $status,
                    'is_available' => $isAvailable,
                    'capacity' => $item['capacity'] ?? $item['kapasitas'] ?? 0,
                ];
            })->toArray();

            Log::info('Processing ' . count($bookings) . ' bookings...');

            $bookingsByDate = $this->processBookingsByDate($bookings, $currentDate);

            $monthlyBookings = $this->getMonthlyBookings($bookings, $currentDate);
            $bookingSummary = $this->getMonthlyBookingSummary($monthlyBookings);

            Log::info('=== FINAL RESULTS ===');
            Log::info('Bookings by Date count: ' . count($bookingsByDate));
            Log::info('Monthly Bookings count: ' . count($monthlyBookings));
        } catch (\Exception $e) {
            Log::error('=== KALENDER ERROR ===');
            Log::error('Error message: ' . $e->getMessage());
            Log::error('Error trace: ' . $e->getTraceAsString());

            $bookingsByDate = [];
            $monthlyBookings = [];
            $bookingSummary = ['total' => 0, 'room' => 0, 'inventory' => 0, 'pending' => 0];
            $inventory = [];
            $rooms = [];
        }

        return view('kalender.index', [
            'currentDate' => $currentDate,
            'bookingsByDate' => $bookingsByDate,
            'monthlyBookings' => $monthlyBookings,
            'bookingSummary' => $bookingSummary,
            'inventory' => $inventory,
            'rooms' => $rooms,
        ]);
    }

    private function processBookingsByDate($bookings, $currentDate)
    {
        $bookingsByDate = [];

        if (!is_array($bookings)) {
            Log::warning('processBookingsByDate: bookings is not array, type: ' . gettype($bookings));
            return $bookingsByDate;
        }

        Log::info('Processing bookings by date, total: ' . count($bookings));

        foreach ($bookings as $index => $booking) {
            try {
                if (!is_array($booking)) {
                    Log::warning("Booking index $index is not array, type: " . gettype($booking));
                    continue;
                }

                $possibleDateFields = [
                    'start_time',
                    'tanggal_mulai',
                    'start_date',
                    'startTime',
                    'tanggalMulai',
                    'date',
                    'booking_date',
                    'created_at',
                    'start',
                    'mulai',
                    'waktu_mulai'
                ];

                $startDateStr = null;
                $foundField = null;

                foreach ($possibleDateFields as $field) {
                    if (isset($booking[$field]) && !empty($booking[$field])) {
                        $startDateStr = $booking[$field];
                        $foundField = $field;
                        break;
                    }
                }

                if (!$startDateStr) {
                    Log::warning("No date field found in booking $index", [
                        'available_keys' => array_keys($booking),
                        'booking_id' => $booking['id'] ?? 'unknown'
                    ]);
                    continue;
                }

                $possibleEndFields = [
                    'end_time',
                    'tanggal_selesai',
                    'end_date',
                    'endTime',
                    'tanggalSelesai',
                    'end',
                    'selesai',
                    'waktu_selesai'
                ];

                $endDateStr = null;
                foreach ($possibleEndFields as $field) {
                    if (isset($booking[$field]) && !empty($booking[$field])) {
                        $endDateStr = $booking[$field];
                        break;
                    }
                }

                $startDate = Carbon::parse($startDateStr)->startOfDay();
                $endDate = $endDateStr ? Carbon::parse($endDateStr)->startOfDay() : $startDate->copy();

                Log::info("Booking $index date range: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

                $type = strtolower($booking['type'] ?? '');
                $itemType = strtolower($booking['item_type'] ?? '');

                $isRoomBooking =
                    $type === 'room' ||
                    $type === 'ruangan' ||
                    $itemType === 'room' ||
                    $itemType === 'ruangan' ||
                    isset($booking['room_id']) ||
                    isset($booking['ruangan_id']);

                $currentDatePointer = $startDate->copy();

                while ($currentDatePointer->lte($endDate)) {
                    if (
                        $currentDatePointer->month == $currentDate->month &&
                        $currentDatePointer->year == $currentDate->year
                    ) {

                        $dateKey = $currentDatePointer->format('Y-m-d');

                        if (!isset($bookingsByDate[$dateKey])) {
                            $bookingsByDate[$dateKey] = [
                                'ruangan' => false,
                                'barang' => false,
                            ];
                        }

                        if ($isRoomBooking) {
                            $bookingsByDate[$dateKey]['ruangan'] = true;
                            Log::info("✓ Marked $dateKey as having RUANGAN booking");
                        } else {
                            $bookingsByDate[$dateKey]['barang'] = true;
                            Log::info("✓ Marked $dateKey as having BARANG booking");
                        }
                    }
                    $currentDatePointer->addDay();
                }
            } catch (\Exception $e) {
                Log::error("Error processing booking index $index:", [
                    'error' => $e->getMessage(),
                    'booking' => $booking
                ]);
                continue;
            }
        }

        Log::info('Total dates marked with bookings: ' . count($bookingsByDate));
        return $bookingsByDate;
    }

    private function getMonthlyBookings($bookings, $currentDate)
    {
        $monthlyBookings = [];

        if (!is_array($bookings)) {
            return $monthlyBookings;
        }

        foreach ($bookings as $booking) {
            try {
                if (!is_array($booking)) {
                    continue;
                }
                $possibleDateFields = [
                    'start_time',
                    'tanggal_mulai',
                    'start_date',
                    'startTime',
                    'tanggalMulai',
                    'date',
                    'booking_date',
                    'created_at'
                ];

                $startDateStr = null;
                foreach ($possibleDateFields as $field) {
                    if (isset($booking[$field]) && !empty($booking[$field])) {
                        $startDateStr = $booking[$field];
                        break;
                    }
                }

                if (!$startDateStr) {
                    continue;
                }

                $startDate = Carbon::parse($startDateStr);

                if ($startDate->month == $currentDate->month && $startDate->year == $currentDate->year) {
                    $possibleEndFields = [
                        'end_time',
                        'tanggal_selesai',
                        'end_date',
                        'endTime',
                        'tanggalSelesai',
                        'end',
                        'selesai'
                    ];

                    $endDateStr = null;
                    foreach ($possibleEndFields as $field) {
                        if (isset($booking[$field]) && !empty($booking[$field])) {
                            $endDateStr = $booking[$field];
                            break;
                        }
                    }

                    $endDate = $endDateStr ? Carbon::parse($endDateStr) : null;
                    $type = strtolower($booking['type'] ?? '');
                    $itemType = strtolower($booking['item_type'] ?? '');

                    $isRoomBooking =
                        $type === 'room' ||
                        $type === 'ruangan' ||
                        $itemType === 'room' ||
                        $itemType === 'ruangan' ||
                        isset($booking['room_id']) ||
                        isset($booking['ruangan_id']);

                    $itemName = $booking['item_name']
                        ?? $booking['nama_item']
                        ?? $booking['name']
                        ?? $booking['nama']
                        ?? '-';

                    $userName = $booking['user_name']
                        ?? $booking['nama_peminjam']
                        ?? $booking['borrower']
                        ?? $booking['notes']
                        ?? '-';

                    $monthlyBookings[] = [
                        'id' => $booking['id'] ?? 0,
                        'nama' => $itemName,
                        'peminjam' => $userName,
                        'tanggal' => $startDate->format('d'),
                        'tanggal_lengkap' => $startDate->format('d-m-Y'),
                        'tanggal_mulai' => $startDate->format('d-m-Y H:i'),
                        'tanggal_selesai' => $endDate ? $endDate->format('d-m-Y H:i') : '-',
                        'status' => $booking['status'] ?? 'pending',
                        'type' => $isRoomBooking ? 'room' : 'inventory',
                        'quantity' => $booking['quantity'] ?? $booking['jumlah'] ?? 1,
                    ];
                }
            } catch (\Exception $e) {
                Log::error("Error processing monthly booking: " . $e->getMessage());
                continue;
            }
        }

        usort($monthlyBookings, function ($a, $b) {
            return strcmp($b['tanggal'], $a['tanggal']);
        });

        return $monthlyBookings;
    }

    private function getMonthlyBookingSummary(array $monthlyBookings): array
    {
        return [
            'total' => count($monthlyBookings),
            'room' => collect($monthlyBookings)->where('type', 'room')->count(),
            'inventory' => collect($monthlyBookings)->where('type', 'inventory')->count(),
            'pending' => collect($monthlyBookings)->where('status', 'pending')->count(),
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:room,inventory',
            'item_id' => 'required|integer',
            'item_name' => 'required|string|max:255',
            'start_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required',
            'end_date'   => 'required|date',
            'end_time' => 'required',
            'quantity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $baseUrl = config('api.base_url');
        $token = session('api_token');
        $http = Http::withoutVerifying()->timeout(10);

        $startDateTime = $validated['start_date'] . ' ' . $validated['start_time'];
        $endDateTime = $validated['end_date'] . ' ' . $validated['end_time'];

        if (Carbon::parse($endDateTime)->lessThanOrEqualTo(Carbon::parse($startDateTime))) {
            return redirect()->route('kalender')
                ->withInput()
                ->with('error', 'Waktu selesai harus setelah waktu mulai.');
        }

        $bookingData = [
            'type' => $validated['type'],
            'item_id' => (int)$validated['item_id'],
            'item_name' => $validated['item_name'],
            'start_time' => $startDateTime,
            'end_time' => $endDateTime,
            'quantity' => (int)($validated['quantity'] ?? 1),
            'notes' => $validated['notes'] ?? '',
            'status' => 'pending',
        ];

        $response = $token
            ? $http->withToken($token)->post($baseUrl . '/api/bookings', $bookingData)
            : $http->post($baseUrl . '/api/bookings', $bookingData);

        if ($response->successful()) {
            return redirect()->route('kalender')
                ->with('success', 'Peminjaman/Sewa berhasil ditambahkan!');
        }

        $errorMessage = 'Gagal menambahkan peminjaman';

        if ($response->status() >= 400) {
            $responseData = $response->json();
            $errorMessage .= ': ' . ($responseData['message'] ?? $responseData['error'] ?? 'Silakan coba lagi');
        }

        return redirect()->route('kalender')
            ->with('error', $errorMessage);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,completed'
        ]);

        $baseUrl = config('api.base_url');
        $token = session('api_token');
        $http = Http::withoutVerifying()->timeout(10);

        $response = $token
            ? $http->withToken($token)->put($baseUrl . '/api/bookings/' . $id, ['status' => $validated['status']])
            : $http->put($baseUrl . '/api/bookings/' . $id, ['status' => $validated['status']]);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diperbarui!'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal memperbarui status'
        ], 500);
    }

    public function destroy($id)
    {
        $baseUrl = config('api.base_url');
        $token = session('api_token');
        $http = Http::withoutVerifying()->timeout(10);

        $response = $token
            ? $http->withToken($token)->delete($baseUrl . '/api/bookings/' . $id)
            : $http->delete($baseUrl . '/api/bookings/' . $id);

        if ($response->successful()) {
            return redirect()->route('kalender')
                ->with('success', 'Peminjaman berhasil dihapus!');
        }

        return redirect()->route('kalender')
            ->with('error', 'Gagal menghapus peminjaman.');
    }

    public function show($id)
    {
        $baseUrl = config('api.base_url');
        $token = session('api_token');
        $http = Http::withoutVerifying()->timeout(5);

        $response = $token
            ? $http->withToken($token)->get($baseUrl . '/api/bookings/' . $id)
            : $http->get($baseUrl . '/api/bookings/' . $id);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Data tidak ditemukan'], 404);
    }
}
