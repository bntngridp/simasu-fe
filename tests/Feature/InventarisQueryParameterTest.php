<?php

namespace Tests\Feature;

use App\Http\Controllers\InventarisController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Parameter query berbentuk array (?sort[]=nama, ?kategori[]=Audio) sebelumnya
 * memicu TypeError / "Array to string conversion" (HTTP 500).
 */
class InventarisQueryParameterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        Http::fake([
            config('api.base_url') . '/api/inventory' => Http::response([
                ['id' => 1, 'name' => 'Sajadah', 'category' => 'Ibadah', 'stock' => 30, 'updatedAt' => '2026-11-01'],
                ['id' => 2, 'name' => 'Mikrofon', 'category' => 'Audio', 'stock' => 5, 'updatedAt' => '2026-11-05'],
                ['id' => 3, 'name' => 'Speaker', 'category' => 'Audio', 'stock' => 0, 'updatedAt' => '2026-11-02'],
            ], 200),
        ]);
    }

    private function getInventaris(string $query)
    {
        return $this->withSession(['api_token' => 'token'])->get('/inventaris?' . $query);
    }

    private function exportCsv(string $query): string
    {
        $response = $this->withSession(['api_token' => 'token'])->get('/inventaris/export?' . $query);
        $response->assertOk();

        return $response->streamedContent();
    }

    public function test_string_parameters_still_filter_and_sort(): void
    {
        $this->getInventaris('kategori=Audio&sort=nama')
            ->assertOk()
            ->assertSeeInOrder(['Mikrofon', 'Speaker'])
            ->assertDontSee('Sajadah</td>', false);
    }

    public function test_array_sort_parameter_falls_back_to_default_order(): void
    {
        // Urutan default "terbaru": Mikrofon (05) -> Speaker (02) -> Sajadah (01)
        $this->getInventaris('sort[]=nama')
            ->assertOk()
            ->assertSeeInOrder(['Mikrofon', 'Speaker', 'Sajadah']);
    }

    public function test_array_category_parameter_shows_all_items(): void
    {
        $this->getInventaris('kategori[]=Audio')
            ->assertOk()
            ->assertSee('Sajadah')
            ->assertSee('Mikrofon')
            ->assertSee('Speaker');
    }

    public function test_combined_array_parameters_use_defaults(): void
    {
        $this->getInventaris('kategori[]=Audio&sort[]=nama')
            ->assertOk()
            ->assertSeeInOrder(['Mikrofon', 'Speaker', 'Sajadah']);
    }

    public function test_export_with_array_sort_returns_valid_csv(): void
    {
        $lines = array_values(array_filter(explode("\n", $this->exportCsv('sort[]=nama'))));

        $this->assertStringStartsWith('"Nama Barang",Kategori,Jumlah', $lines[0]);
        $this->assertCount(4, $lines);
        $this->assertStringStartsWith('Mikrofon,', $lines[1]);
    }

    public function test_export_with_array_category_exports_all_items(): void
    {
        $csv = $this->exportCsv('kategori[]=Audio&sort[]=nama');

        $this->assertStringContainsString('Sajadah', $csv);
        $this->assertStringContainsString('Mikrofon', $csv);
        $this->assertStringContainsString('Speaker', $csv);
    }

    public function test_query_string_helper_ignores_non_scalar_values(): void
    {
        $controller = new InventarisController();

        $this->assertSame('Audio', $controller->queryString(Request::create('/', 'GET', ['k' => 'Audio']), 'k'));
        $this->assertSame('', $controller->queryString(Request::create('/', 'GET', ['k' => ['Audio']]), 'k'));
        $this->assertSame('', $controller->queryString(Request::create('/', 'GET'), 'k'));
    }
}
