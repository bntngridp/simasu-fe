<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InventarisStatusTest extends TestCase
{
    public function test_inventory_page_shows_stock_filter_and_statuses(): void
    {
        $this->withoutVite();
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/inventory' => Http::response([
                ['id' => 1, 'name' => 'Sajadah', 'category' => 'Ibadah', 'stock' => 0, 'description' => 'Perlu stok baru'],
                ['id' => 2, 'name' => 'Mikrofon', 'category' => 'Audio', 'stock' => 5, 'description' => 'Wireless'],
                ['id' => 3, 'name' => 'Kursi', 'category' => 'Fasilitas', 'stock' => 20, 'description' => 'Plastik'],
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('inventaris'));

        $response->assertOk();
        $response->assertSee('statusFilter');
        $response->assertSee('Terbatas');
        $response->assertSee('Habis');
        $response->assertSee('data-status="Tersedia"', false);
        $response->assertSee('data-status="Terbatas"', false);
        $response->assertSee('data-status="Habis"', false);
    }
}