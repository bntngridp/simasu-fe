<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\Facades\Http;

class RuanganViewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        view()->share('errors', new ViewErrorBag);
    }

    private function getBaseUrl()
    {
        return config('api.base_url');
    }

    public function test_halaman_index_ruangan_berhasil_dimuat()
    {
        $baseUrl = $this->getBaseUrl();

        Http::fake([
            $baseUrl . '/api/rooms' => Http::response([
                ['id' => 1, 'name' => 'Ruang Teori 1', 'floor' => '1', 'capacity' => 30]
            ], 200),
            $baseUrl . '/api/bookings' => Http::response([], 200),
        ]);

        $response = $this->withSession(['api_token' => 'mock-token'])
                         ->get('/ruangan');
        
        $response->assertStatus(200);
        $response->assertSee('Sewa Ruangan');
    }

    public function test_terdapat_input_pencarian_untuk_javascript()
    {
        $baseUrl = $this->getBaseUrl();

        Http::fake([
            $baseUrl . '/api/rooms' => Http::response([], 200),
            $baseUrl . '/api/bookings' => Http::response([], 200),
        ]);

        $response = $this->withSession(['api_token' => 'mock-token'])
                         ->get('/ruangan');
        
        $response->assertSee('id="roomSearch"', false);
    }

    public function test_terdapat_dropdown_filter_status_ruangan()
    {
        $baseUrl = $this->getBaseUrl();

        Http::fake([
            $baseUrl . '/api/rooms' => Http::response([], 200),
            $baseUrl . '/api/bookings' => Http::response([], 200),
        ]);

        $response = $this->withSession(['api_token' => 'mock-token'])
                         ->get('/ruangan');
        
        $response->assertSee('id="roomStatusFilter"', false);
    }

    public function test_tampilan_saat_data_ruangan_kosong_menampilkan_pesan_yang_sesuai()
    {
        $baseUrl = $this->getBaseUrl();

        // return array kosong
        Http::fake([
            $baseUrl . '/api/rooms' => Http::response([], 200),
            $baseUrl . '/api/bookings' => Http::response([], 200),
        ]);

        $response = $this->withSession(['api_token' => 'mock-token'])
                         ->get('/ruangan');

        $response->assertSee('Belum ada ruangan tersedia');
    }

    public function test_tampilan_saat_data_ruangan_terisi_menampilkan_nama_ruangan()
    {
        $baseUrl = $this->getBaseUrl();

        Http::fake([
            $baseUrl . '/api/rooms' => Http::response([
                [
                    'id' => 1, 
                    'name' => 'Laboratorium Komputer', 
                    'floor' => '2', 
                    'capacity' => 25, 
                    'facilities' => 'AC, LAN'
                ]
            ], 200),
            $baseUrl . '/api/bookings' => Http::response([], 200),
        ]);

        $response = $this->withSession(['api_token' => 'mock-token'])
                         ->get('/ruangan');

        $response->assertStatus(200);
        $response->assertSee('Laboratorium Komputer');
        $response->assertDontSee('Belum ada ruangan tersedia');
    }
}