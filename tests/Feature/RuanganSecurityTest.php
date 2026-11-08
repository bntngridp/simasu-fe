<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class RuanganSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        view()->share('errors', new ViewErrorBag());
    }

    private function getBaseUrl(): string
    {
        return config('api.base_url', 'http://127.0.0.1:7082');
    }

    /*
    |--------------------------------------------------------------------------
    | ADR-01: Kalkulasi Status Okupansi Waktu Nyata & Deteksi Benturan Jadwal
    |--------------------------------------------------------------------------
    */

    public function test_ruangan_berstatus_tersedia_jika_tidak_ada_booking_aktif()
    {
        $baseUrl = $this->getBaseUrl();

        Http::fake([
            $baseUrl . '/api/rooms' => Http::response([
                ['id' => 101, 'name' => 'Aula Utama', 'floor' => 'Lantai 1', 'capacity' => 200, 'facilities' => 'Sound System, AC']
            ], 200),
            $baseUrl . '/api/bookings' => Http::response([], 200),
        ]);

        $response = $this->withSession(['api_token' => 'mock-token-adrian'])
            ->get('/ruangan');

        $response->assertStatus(200);
        $response->assertSee('Aula Utama');
        $response->assertSee('Tersedia');
        $this->assertEquals('tersedia', $response->viewData('rooms')->first()['status']);
    }

    public function test_ruangan_berstatus_dipakai_jika_ada_booking_approved_pada_waktu_sekarang()
    {
        $baseUrl = $this->getBaseUrl();
        $now = Carbon::now();

        Http::fake([
            $baseUrl . '/api/rooms' => Http::response([
                ['id' => 102, 'name' => 'Ruang VIP', 'floor' => 'Lantai 2', 'capacity' => 20, 'facilities' => 'Sofa, Proyektor']
            ], 200),
            $baseUrl . '/api/bookings' => Http::response([
                [
                    'id'         => 1,
                    'item_id'    => 102,
                    'type'       => 'room',
                    'status'     => 'approved',
                    'start_time' => $now->copy()->subHour()->toIso8601String(),
                    'end_time'   => $now->copy()->addHour()->toIso8601String(),
                ]
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'mock-token-adrian'])
            ->get('/ruangan');

        $response->assertStatus(200);
        $response->assertSee('Ruang VIP');
        $response->assertSee('Dipakai');
    }

    public function test_ruangan_tetap_tersedia_jika_booking_berstatus_pending_atau_rejected()
    {
        $baseUrl = $this->getBaseUrl();
        $now = Carbon::now();

        Http::fake([
            $baseUrl . '/api/rooms' => Http::response([
                ['id' => 103, 'name' => 'Ruang Seminar', 'floor' => 'Lantai 3', 'capacity' => 80, 'facilities' => 'Mic, Kursi']
            ], 200),
            $baseUrl . '/api/bookings' => Http::response([
                [
                    'id'         => 2,
                    'item_id'    => 103,
                    'type'       => 'room',
                    'status'     => 'pending',
                    'start_time' => $now->copy()->subMinutes(30)->toIso8601String(),
                    'end_time'   => $now->copy()->addMinutes(30)->toIso8601String(),
                ],
                [
                    'id'         => 3,
                    'item_id'    => 103,
                    'type'       => 'room',
                    'status'     => 'rejected',
                    'start_time' => $now->copy()->subMinutes(15)->toIso8601String(),
                    'end_time'   => $now->copy()->addMinutes(45)->toIso8601String(),
                ]
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'mock-token-adrian'])
            ->get('/ruangan');

        $response->assertStatus(200);
        $response->assertSee('Ruang Seminar');
        $response->assertSee('Tersedia');
        $this->assertEquals('tersedia', $response->viewData('rooms')->first()['status']);
    }

    public function test_ruangan_tersedia_jika_waktu_booking_sudah_berlalu()
    {
        $baseUrl = $this->getBaseUrl();
        $now = Carbon::now();

        Http::fake([
            $baseUrl . '/api/rooms' => Http::response([
                ['id' => 104, 'name' => 'Ruang Rapat Utama', 'floor' => 'Lantai 1', 'capacity' => 15, 'facilities' => 'TV LED']
            ], 200),
            $baseUrl . '/api/bookings' => Http::response([
                [
                    'id'         => 4,
                    'item_id'    => 104,
                    'type'       => 'room',
                    'status'     => 'approved',
                    'start_time' => $now->copy()->subHours(3)->toIso8601String(),
                    'end_time'   => $now->copy()->subHour()->toIso8601String(),
                ]
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'mock-token-adrian'])
            ->get('/ruangan');

        $response->assertStatus(200);
        $response->assertSee('Tersedia');
        $this->assertEquals('tersedia', $response->viewData('rooms')->first()['status']);
    }

    public function test_pemesanan_ruangan_memberikan_peringatan_jika_ruangan_sedang_dipakai()
    {
        $baseUrl = $this->getBaseUrl();
        $now = Carbon::now();

        Http::fake([
            $baseUrl . '/api/rooms/105' => Http::response([
                'id'       => 105,
                'name'     => 'Aula Gedung B',
                'capacity' => 150,
            ], 200),
            $baseUrl . '/api/bookings' => Http::response([
                [
                    'id'         => 5,
                    'item_id'    => 105,
                    'type'       => 'room',
                    'status'     => 'approved',
                    'start_time' => $now->copy()->subMinutes(10)->toIso8601String(),
                    'end_time'   => $now->copy()->addMinutes(50)->toIso8601String(),
                ]
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'mock-token-adrian'])
            ->getJson('/ruangan/105/book');

        $response->assertStatus(200);
        $response->assertJson([
            'success'        => true,
            'is_available'   => false,
            'current_status' => 'dipakai',
        ]);
        $response->assertSee('sedang dipakai saat ini');
    }

    public function test_pemesanan_ruangan_berhasil_dan_tersedia_jika_tidak_ada_konflik()
    {
        $baseUrl = $this->getBaseUrl();

        Http::fake([
            $baseUrl . '/api/rooms/106' => Http::response([
                'id'       => 106,
                'name'     => 'Laboratorium Multimedia',
                'capacity' => 40,
            ], 200),
            $baseUrl . '/api/bookings' => Http::response([], 200),
        ]);

        $response = $this->withSession(['api_token' => 'mock-token-adrian'])
            ->getJson('/ruangan/106/book');

        $response->assertStatus(200);
        $response->assertJson([
            'success'        => true,
            'is_available'   => true,
            'current_status' => 'tersedia',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ADR-02: Validasi Ketat, Batas Kapasitas & Sanitasi Fasilitas (XSS)
    |--------------------------------------------------------------------------
    */

    public function test_tambah_ruangan_gagal_jika_kapasitas_melebihi_batas_maksimum()
    {
        $response = $this->withSession(['user' => ['role' => 'admin']])
            ->postJson('/ruangan', [
                'name'        => 'Stadion Mini',
                'floor'       => 'Lantai 1',
                'capacity'    => 999999, // Melebihi batas aman 5000
                'description' => 'Kapasitas berlebihan',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['capacity']);
    }

    public function test_tambah_ruangan_gagal_jika_field_wajib_kosong()
    {
        $response = $this->withSession(['user' => ['role' => 'admin']])
            ->postJson('/ruangan', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'floor', 'description', 'capacity']);
    }

    public function test_tambah_ruangan_membersihkan_tag_html_pada_nama_dan_fasilitas()
    {
        $baseUrl = $this->getBaseUrl();

        Http::fake([
            $baseUrl . '/api/rooms' => function ($request) {
                $payload = $request->data();
                // Verifikasi sanitasi: tag <script> dan <b> harus bersih
                $cleanName = strpos($payload['name'], '<script>') === false;
                $cleanFacilities = strpos($payload['facilities'], '<b>') === false;

                if ($cleanName && $cleanFacilities) {
                    return Http::response(['success' => true, 'id' => 999], 201);
                }
                return Http::response(['error' => 'Unsanitized input detected'], 400);
            },
        ]);

        $response = $this->withSession(['user' => ['role' => 'admin'], 'api_token' => 'mock-token-adrian'])
            ->postJson('/ruangan', [
                'name'        => '<script>alert("XSS")</script>Ruang Diskusi',
                'floor'       => 'Lantai 2',
                'capacity'    => 25,
                'description' => '<b>Proyektor</b> dan Whiteboard',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Ruangan berhasil ditambahkan!',
        ]);
    }

    public function test_update_ruangan_berhasil_memperbarui_data_dengan_validasi()
    {
        $baseUrl = $this->getBaseUrl();

        Http::fake([
            $baseUrl . '/api/rooms/1' => Http::response(['success' => true], 200),
        ]);

        $response = $this->withSession(['user' => ['role' => 'admin'], 'api_token' => 'mock-token-adrian'])
            ->putJson('/ruangan/1', [
                'name'        => 'Ruang Rapat DKM Updated',
                'floor'       => 'Lantai 1',
                'capacity'    => 35,
                'description' => 'Fasilitas baru AC 2 PK',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Ruangan berhasil diperbarui!',
        ]);
    }

    public function test_hapus_ruangan_berhasil_menghapus_data()
    {
        $baseUrl = $this->getBaseUrl();

        Http::fake([
            $baseUrl . '/api/rooms/10' => Http::response(['success' => true], 200),
        ]);

        $response = $this->withSession(['user' => ['role' => 'admin'], 'api_token' => 'mock-token-adrian'])
            ->deleteJson('/ruangan/10');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Ruangan berhasil dihapus!',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ADR-03: Metrik Ringkasan Okupansi, Multi-Kriteria Filter & Resiliensi API
    |--------------------------------------------------------------------------
    */

    public function test_halaman_index_menghitung_dan_menampilkan_kartu_ringkasan_metrik()
    {
        $baseUrl = $this->getBaseUrl();

        Http::fake([
            $baseUrl . '/api/rooms' => Http::response([
                ['id' => 1, 'name' => 'Ruang A', 'floor' => 'Lantai 1', 'capacity' => 10, 'facilities' => 'Meja'],
                ['id' => 2, 'name' => 'Ruang B', 'floor' => 'Lantai 2', 'capacity' => 20, 'facilities' => 'Kursi'],
                ['id' => 3, 'name' => 'Ruang C', 'floor' => 'Lantai 3', 'capacity' => 30, 'facilities' => 'AC'],
            ], 200),
            $baseUrl . '/api/bookings' => Http::response([], 200),
        ]);

        $response = $this->withSession(['api_token' => 'mock-token-adrian'])
            ->get('/ruangan');

        $response->assertStatus(200);
        $response->assertSee('Total Ruangan');
        $response->assertSee('Total Kapasitas');
        $response->assertSee('60 orang'); // 10 + 20 + 30
    }

    public function test_halaman_index_tetap_berjalan_stabil_jika_api_booking_gagal()
    {
        $baseUrl = $this->getBaseUrl();

        Http::fake([
            $baseUrl . '/api/rooms' => Http::response([
                ['id' => 1, 'name' => 'Ruang Resiliensi', 'floor' => 'Lantai 1', 'capacity' => 15, 'facilities' => '-']
            ], 200),
            $baseUrl . '/api/bookings' => Http::response(['error' => 'Database Timeout'], 500),
        ]);

        $response = $this->withSession(['api_token' => 'mock-token-adrian'])
            ->get('/ruangan');

        // Aplikasi tidak boleh crash 500 walau booking API sedang down (Graceful degradation)
        $response->assertStatus(200);
        $response->assertSee('Ruang Resiliensi');
        $response->assertSee('Tersedia');
    }

    public function test_detail_ruangan_mengembalikan_404_jika_ruangan_tidak_ditemukan()
    {
        $baseUrl = $this->getBaseUrl();

        Http::fake([
            $baseUrl . '/api/rooms/9999' => Http::response(['error' => 'Not found'], 404),
        ]);

        $response = $this->withSession(['api_token' => 'mock-token-adrian'])
            ->getJson('/ruangan/9999');

        $response->assertStatus(404);
        $response->assertJson([
            'error'       => 'API Error',
            'status_code' => 404,
        ]);
    }

    public function test_check_availability_berhasil_menemukan_ketersediaan_jika_tidak_ada_benturan()
    {
        $baseUrl = $this->getBaseUrl();
        $start = Carbon::now()->addDays(1)->setHour(10)->setMinute(0);
        $end = $start->copy()->addHours(2);

        Http::fake([
            $baseUrl . '/api/bookings' => Http::response([], 200),
        ]);

        $response = $this->withSession(['api_token' => 'mock-token-adrian'])
            ->getJson('/ruangan/101/availability?start_time=' . urlencode($start->toIso8601String()) . '&end_time=' . urlencode($end->toIso8601String()));

        $response->assertStatus(200);
        $response->assertJson([
            'success'      => true,
            'room_id'      => 101,
            'is_available' => true,
            'conflict_count' => 0,
        ]);
    }

    public function test_check_availability_mendeteksi_konflik_jika_jadwal_tumpang_tindih()
    {
        $baseUrl = $this->getBaseUrl();
        $start = Carbon::now()->addDays(2)->setHour(14)->setMinute(0);
        $end = $start->copy()->addHours(3);

        Http::fake([
            $baseUrl . '/api/bookings' => Http::response([
                [
                    'id'         => 88,
                    'item_id'    => 101,
                    'type'       => 'room',
                    'status'     => 'approved',
                    'start_time' => $start->copy()->addHour()->toIso8601String(), // Tengah-tengah rentang
                    'end_time'   => $start->copy()->addHours(2)->toIso8601String(),
                ]
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'mock-token-adrian'])
            ->getJson('/ruangan/101/availability?start_time=' . urlencode($start->toIso8601String()) . '&end_time=' . urlencode($end->toIso8601String()));

        $response->assertStatus(200);
        $response->assertJson([
            'success'      => true,
            'room_id'      => 101,
            'is_available' => false,
            'conflict_count' => 1,
        ]);
        $response->assertSee('Ruangan memiliki jadwal bentrok');
    }

    public function test_check_availability_gagal_jika_waktu_selesai_lebih_awal_dari_mulai()
    {
        $start = Carbon::now()->addDays(1)->setHour(12)->setMinute(0);
        $end = $start->copy()->subHours(1); // Invalid: selesai sebelum mulai

        $response = $this->withSession(['api_token' => 'mock-token-adrian'])
            ->getJson('/ruangan/101/availability?start_time=' . urlencode($start->toIso8601String()) . '&end_time=' . urlencode($end->toIso8601String()));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Waktu selesai harus setelah waktu mulai.',
        ]);
    }
}
