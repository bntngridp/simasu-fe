<?php

namespace Tests\Feature;

use App\Http\Controllers\InventarisController;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InventarisSummaryTest extends TestCase
{
    private string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->baseUrl = config('api.base_url');
    }

    private function fakeInventory($body, int $status = 200): void
    {
        Http::fake([$this->baseUrl . '/api/inventory' => Http::response($body, $status)]);
    }

    private function summaryCard(string $key, int $value): string
    {
        return 'data-summary="' . $key . '">' . $value . '</h3>';
    }

    public function test_summary_cards_show_correct_counts(): void
    {
        $this->fakeInventory([
            ['id' => 1, 'name' => 'Sajadah', 'stock' => 0],
            ['id' => 2, 'name' => 'Mikrofon', 'stock' => 5],
            ['id' => 3, 'name' => 'Kabel', 'stock' => 10],
            ['id' => 4, 'name' => 'Kursi', 'stock' => 20],
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('inventaris'));

        $response->assertOk();
        $response->assertSee($this->summaryCard('total_barang', 4), false);
        $response->assertSee($this->summaryCard('total_stok', 35), false);
        $response->assertSee($this->summaryCard('terbatas', 2), false);
        $response->assertSee($this->summaryCard('habis', 1), false);
    }

    public function test_summary_is_zero_when_inventory_empty(): void
    {
        $this->fakeInventory([]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('inventaris'));

        $response->assertOk();
        $response->assertSee($this->summaryCard('total_barang', 0), false);
        $response->assertSee($this->summaryCard('total_stok', 0), false);
    }

    public function test_summary_is_zero_and_page_loads_when_api_down(): void
    {
        $this->fakeInventory(['message' => 'down'], 503);

        $response = $this->withSession(['api_token' => 'token'])->get(route('inventaris'));

        $response->assertOk();
        $response->assertSee($this->summaryCard('habis', 0), false);
        $response->assertSee('Belum ada data inventaris');
    }

    public function test_summary_counts_all_items_even_when_category_filtered(): void
    {
        $this->fakeInventory([
            ['id' => 1, 'name' => 'Sajadah', 'category' => 'Ibadah', 'stock' => 0],
            ['id' => 2, 'name' => 'Mikrofon', 'category' => 'Audio', 'stock' => 5],
        ]);

        $response = $this->withSession(['api_token' => 'token'])
            ->get(route('inventaris', ['kategori' => 'Audio']));

        $response->assertSee($this->summaryCard('total_barang', 2), false);
        $response->assertDontSee('Sajadah</td>', false);
    }

    public function test_build_summary_handles_all_items_out_of_stock(): void
    {
        $items = collect([
            (object) ['jumlah' => 0, 'status_stok' => 'Habis'],
            (object) ['jumlah' => 0, 'status_stok' => 'Habis'],
        ]);

        $summary = (new InventarisController())->buildSummary($items);

        $this->assertSame([
            'total_barang' => 2,
            'total_stok'   => 0,
            'tersedia'     => 0,
            'terbatas'     => 0,
            'habis'        => 2,
        ], $summary);
    }
}
