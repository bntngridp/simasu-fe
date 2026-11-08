<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class RuanganController extends Controller
{
    /**
     * Menampilkan daftar ruangan beserta status okupansi real-time dan ringkasan metrik.
     */
    public function index(): View
    {
        $baseUrl = config('api.base_url');
        $token = session('api_token');

        $responseRooms = Http::withoutVerifying()->withToken($token)->get($baseUrl . '/api/rooms');
        $responseBookings = Http::withoutVerifying()->withToken($token)->get($baseUrl . '/api/bookings');

        $bookings = $responseBookings->successful() ? collect($responseBookings->json()) : collect([]);
        $rawRooms = $responseRooms->successful() ? $responseRooms->json() : [];

        $now = Carbon::now();

        $rooms = collect($rawRooms)->map(function ($item) use ($bookings, $now) {
            $roomId = $item['id'] ?? 0;
            $status = 'tersedia';

            $isBooked = $bookings->contains(function ($booking) use ($roomId, $now) {
                return isset($booking['item_id'], $booking['type'], $booking['status'], $booking['start_time'], $booking['end_time']) &&
                    $booking['type'] === 'room' &&
                    $booking['item_id'] == $roomId &&
                    $booking['status'] === 'approved' &&
                    $now->between(
                        Carbon::parse($booking['start_time']),
                        Carbon::parse($booking['end_time'])
                    );
            });

            if ($isBooked) {
                $status = 'dipakai';
            }

            return [
                'id'          => $roomId,
                'name'        => $item['name'] ?? '',
                'floor'       => $item['floor'] ?? '',
                'description' => $item['facilities'] ?? '-',
                'capacity'    => (int) ($item['capacity'] ?? 0),
                'status'      => $status,
            ];
        });

        // Metrik Ringkasan Okupansi untuk Dashboard & Pemantauan Cepat (ADR-03)
        $summary = [
            'total'           => $rooms->count(),
            'tersedia'        => $rooms->where('status', 'tersedia')->count(),
            'dipakai'         => $rooms->where('status', 'dipakai')->count(),
            'total_kapasitas' => $rooms->sum('capacity'),
        ];

        return view('ruangan.index', [
            'rooms'   => $rooms,
            'summary' => $summary,
        ]);
    }

    /**
     * Menyimpan data ruangan baru dengan validasi ketat dan sanitasi input (ADR-02).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'floor'       => 'required|string|max:50',
            'description' => 'required|string|max:1000',
            'capacity'    => 'required|integer|min:1|max:5000',
        ]);

        $baseUrl = config('api.base_url');
        $token = session('api_token');

        // Sanitasi input teks dari potensi payload XSS / HTML tags
        $cleanName = strip_tags(trim($validated['name']));
        $cleanFloor = strip_tags(trim($validated['floor']));
        $cleanFacilities = strip_tags(trim($validated['description']));

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->post($baseUrl . '/api/rooms', [
                'name'       => $cleanName,
                'floor'      => $cleanFloor,
                'capacity'   => (int) $validated['capacity'],
                'facilities' => $cleanFacilities,
            ]);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'Ruangan berhasil ditambahkan!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal menambahkan ruangan',
        ], 500);
    }

   
    public function show(int|string $id): JsonResponse
    {
        $baseUrl = config('api.base_url');
        $token = session('api_token');

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->get($baseUrl . '/api/rooms/' . $id);

        if ($response->successful()) {
            $data = $response->json();
            $data['description'] = $data['facilities'] ?? '';

            return response()->json($data);
        }

        $statusCode = $response->status() >= 400 && $response->status() < 600 ? $response->status() : 500;

        return response()->json([
            'error'          => 'API Error',
            'status_code'    => $statusCode,
            'server_message' => $response->body(),
        ], $statusCode);
    }

    
    public function update(Request $request, int|string $id): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'floor'       => 'required|string|max:50',
            'description' => 'required|string|max:1000',
            'capacity'    => 'required|integer|min:1|max:5000',
        ]);

        $baseUrl = config('api.base_url');
        $token = session('api_token');

        $cleanName = strip_tags(trim($validated['name']));
        $cleanFloor = strip_tags(trim($validated['floor']));
        $cleanFacilities = strip_tags(trim($validated['description']));

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->put($baseUrl . '/api/rooms/' . $id, [
                'name'       => $cleanName,
                'floor'      => $cleanFloor,
                'capacity'   => (int) $validated['capacity'],
                'facilities' => $cleanFacilities,
            ]);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'Ruangan berhasil diperbarui!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal memperbarui ruangan',
        ], 500);
    }

   
    public function destroy(int|string $id): JsonResponse
    {
        $baseUrl = config('api.base_url');
        $token = session('api_token');

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->delete($baseUrl . '/api/rooms/' . $id);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'Ruangan berhasil dihapus!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal menghapus ruangan',
        ], 500);
    }

    
    public function book(int|string $id): JsonResponse
    {
        $baseUrl = config('api.base_url');
        $token = session('api_token');

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->get($baseUrl . '/api/rooms/' . $id);

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Ruangan tidak ditemukan',
            ], 404);
        }

        $room = $response->json();

        // Pemeriksaan benturan jadwal peminjaman saat ini secara proaktif (ADR-01)
        $responseBookings = Http::withoutVerifying()->withToken($token)->get($baseUrl . '/api/bookings');
        $isCurrentlyBooked = false;

        if ($responseBookings->successful()) {
            $now = Carbon::now();
            $isCurrentlyBooked = collect($responseBookings->json())->contains(function ($booking) use ($id, $now) {
                return isset($booking['item_id'], $booking['type'], $booking['status'], $booking['start_time'], $booking['end_time']) &&
                    $booking['type'] === 'room' &&
                    $booking['item_id'] == $id &&
                    $booking['status'] === 'approved' &&
                    $now->between(
                        Carbon::parse($booking['start_time']),
                        Carbon::parse($booking['end_time'])
                    );
            });
        }

        return response()->json([
            'success'        => true,
            'is_available'   => !$isCurrentlyBooked,
            'current_status' => $isCurrentlyBooked ? 'dipakai' : 'tersedia',
            'message'        => $isCurrentlyBooked
                ? 'Peringatan: Ruangan ' . ($room['name'] ?? '') . ' sedang dipakai saat ini.'
                : 'Memproses pemesanan untuk ruangan: ' . ($room['name'] ?? ''),
            'room'           => [
                'id'       => $room['id'] ?? $id,
                'name'     => $room['name'] ?? '',
                'capacity' => (int) ($room['capacity'] ?? 0),
            ],
        ]);
    }

    /**
     * Memeriksa ketersediaan jadwal ruangan pada rentang waktu tertentu untuk mencegah benturan jadwal (ADR-01).
     */
    public function checkAvailability(Request $request, int|string $id): JsonResponse
    {
        $startTimeInput = $request->query('start_time');
        $endTimeInput = $request->query('end_time');

        $start = $startTimeInput ? Carbon::parse($startTimeInput) : Carbon::now();
        $end = $endTimeInput ? Carbon::parse($endTimeInput) : $start->copy()->addHours(2);

        if ($end->lessThanOrEqualTo($start)) {
            return response()->json([
                'success' => false,
                'message' => 'Waktu selesai harus setelah waktu mulai.',
            ], 422);
        }

        $baseUrl = config('api.base_url');
        $token = session('api_token');

        $responseBookings = Http::withoutVerifying()->withToken($token)->get($baseUrl . '/api/bookings');

        if (!$responseBookings->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data jadwal dari server.',
            ], 500);
        }

        $bookings = collect($responseBookings->json());

        $conflicts = $bookings->filter(function ($booking) use ($id, $start, $end) {
            if (!isset($booking['item_id'], $booking['type'], $booking['status'], $booking['start_time'], $booking['end_time'])) {
                return false;
            }

            if ($booking['type'] !== 'room' || (int) $booking['item_id'] !== (int) $id || $booking['status'] !== 'approved') {
                return false;
            }

            $bookingStart = Carbon::parse($booking['start_time']);
            $bookingEnd = Carbon::parse($booking['end_time']);

            // Benturan jadwal terjadi jika ($start < $bookingEnd) dan ($end > $bookingStart)
            return $start->lessThan($bookingEnd) && $end->greaterThan($bookingStart);
        })->values();

        $isAvailable = $conflicts->isEmpty();

        return response()->json([
            'success'          => true,
            'room_id'          => (int) $id,
            'is_available'     => $isAvailable,
            'requested_window' => [
                'start_time' => $start->toIso8601String(),
                'end_time'   => $end->toIso8601String(),
            ],
            'conflict_count'   => $conflicts->count(),
            'conflicts'        => $conflicts,
            'message'          => $isAvailable
                ? 'Ruangan tersedia untuk jadwal yang dipilih.'
                : 'Ruangan memiliki jadwal bentrok pada waktu yang dipilih.',
        ]);
    }
}
