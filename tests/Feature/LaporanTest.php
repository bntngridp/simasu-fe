<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    private function getBaseUrl()
    {
        return config('api.base_url');
    }

    private function mockApiResponses(): void
    {
        $baseUrl = $this->getBaseUrl();

        Http::fake([
            $baseUrl . '/api/inventory' => Http::response([
                ['id' => 1, 'name' => 'Barang A', 'stock' => 20],
                ['id' => 2, 'name' => 'Barang B', 'stock' => 5], // Stok terbatas
                ['id' => 3, 'name' => 'Barang C', 'stock' => 0], // Stok habis
            ], 200),

            $baseUrl . '/api/rooms' => Http::response([
                ['id' => 1, 'name' => 'Ruang 1', 'capacity' => 10],
                ['id' => 2, 'name' => 'Ruang 2', 'capacity' => 15],
            ], 200),

            $baseUrl . '/api/bookings' => Http::response([
                ['id' => 1, 'status' => 'approved'],
                ['id' => 2, 'status' => 'pending'],
                ['id' => 3, 'status' => 'rejected'],
            ], 200),
        ]);
    }

    public function test_laporan_page_is_accessible_and_shows_correct_data(): void
    {
        $this->withoutVite();
        $this->mockApiResponses();

        $response = $this->withSession(['api_token' => 'token_test'])
                         ->get(route('laporan.index'));

        $response->assertStatus(200);
        
        //Inventaris
        $response->assertSee('3 Item');
        $response->assertSee('Stok Terbatas: 1');
        $response->assertSee('Stok Habis: 1');

        //Ruangan
        $response->assertSee('2 Ruangan');
        $response->assertSee('Total Kapasitas: 25');

        //Booking
        $response->assertSee('3 Total Pengajuan');
        $response->assertSee('Disetujui: 1');
        $response->assertSee('Pending: 1');
        $response->assertSee('Ditolak: 1');
    }

    public function test_laporan_export_pdf_returns_successful_download(): void
    {
        $this->mockApiResponses();

        $response = $this->withSession(['api_token' => 'token_test'])
                         ->get(route('laporan.export'));

        $response->assertStatus(200);
        
        $response->assertHeader('Content-Type', 'application/pdf');
        
        $this->assertStringContainsString(
            'Laporan_SIMASU', 
            $response->headers->get('Content-Disposition')
        );
    }

    public function test_laporan_handles_api_failure_gracefully(): void
    {
        $this->withoutVite();
        $baseUrl = $this->getBaseUrl();

        // Simulasi API gagal
        Http::fake([
            $baseUrl . '/*' => Http::response([], 500),
        ]);

        $response = $this->withSession(['api_token' => 'token_test'])
                         ->get(route('laporan.index'));

        $response->assertStatus(200);
        
        $response->assertSee('0 Item');
        $response->assertSee('0 Ruangan');
    }

    public function test_laporan_filters_bookings_by_inclusive_start_date_range(): void
    {
        $this->withoutVite();
        $baseUrl = $this->getBaseUrl();

        Http::fake([
            $baseUrl . '/api/inventory' => Http::response([]),
            $baseUrl . '/api/rooms' => Http::response([]),
            $baseUrl . '/api/bookings' => Http::response([
                ['id' => 1, 'status' => 'approved', 'start_time' => '2026-11-01 08:00'],
                ['id' => 2, 'status' => 'pending', 'tanggal_mulai' => '2026-11-15 09:00'],
                ['id' => 3, 'status' => 'rejected', 'start_date' => '2026-11-30'],
                ['id' => 4, 'status' => 'pending', 'start_time' => '2026-12-01 08:00'],
            ]),
        ]);

        $response = $this->withSession(['api_token' => 'token_test'])
            ->get(route('laporan.index', ['start_date' => '2026-11-01', 'end_date' => '2026-11-30']));

        $response->assertOk();
        $response->assertSee('3 Total Pengajuan');
        $response->assertSee('Disetujui: 1');
        $response->assertSee('Pending: 1');
        $response->assertSee('Ditolak: 1');
    }

    public function test_laporan_date_range_requires_valid_ordered_dates(): void
    {
        $this->withSession(['api_token' => 'token_test'])
            ->get(route('laporan.index', ['start_date' => '2026-11-30', 'end_date' => '2026-11-01']))
            ->assertRedirect()
            ->assertSessionHasErrors('end_date');
    }

    public function test_laporan_csv_export_contains_summary_and_download_headers(): void
    {
        $this->mockApiResponses();

        $response = $this->withSession(['api_token' => 'token_test'])->get(route('laporan.export.csv'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Laporan_SIMASU', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Permintaan,Ditolak,1', $response->streamedContent());
    }
}