<?php

namespace Tests\Feature;

use App\Http\Controllers\InventarisController;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InventarisFilterTest extends TestCase
{
    private string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->baseUrl = config('api.base_url');

        Http::fake([
            $this->baseUrl . '/api/inventory' => Http::response([
                ['id' => 1, 'name' => 'Sajadah', 'category' => 'Ibadah', 'stock' => 30, 'updatedAt' => '2026-11-01'],
                ['id' => 2, 'name' => 'Mikrofon', 'category' => 'Audio', 'stock' => 5, 'updatedAt' => '2026-11-05'],
                ['id' => 3, 'name' => 'Al-Quran', 'category' => 'ibadah', 'stock' => 12, 'updatedAt' => '2026-11-03'],
                ['id' => 4, 'name' => 'Speaker', 'category' => 'Audio', 'stock' => 0, 'updatedAt' => '2026-11-02'],
            ], 200),
        ]);
    }

    private function getInventaris(array $query = [])
    {
        return $this->withSession(['api_token' => 'token'])->get(route('inventaris', $query));
    }

    public function test_filter_by_category_shows_only_matching_items(): void
    {
        $response = $this->getInventaris(['kategori' => 'Audio']);

        $response->assertOk();
        $response->assertSee('Mikrofon');
        $response->assertSee('Speaker');
        $response->assertDontSee('Sajadah</td>', false);
    }

    public function test_filter_by_category_is_case_insensitive(): void
    {
        $response = $this->getInventaris(['kategori' => 'IBADAH']);

        $response->assertSee('Sajadah');
        $response->assertSee('Al-Quran');
        $response->assertDontSee('Mikrofon</td>', false);
    }

    public function test_unknown_category_shows_empty_state(): void
    {
        $response = $this->getInventaris(['kategori' => 'Elektronik']);

        $response->assertOk();
        $response->assertSee('Belum ada data inventaris');
    }

    public function test_sort_by_lowest_stock(): void
    {
        $response = $this->getInventaris(['sort' => 'stok_terendah']);

        $response->assertSeeInOrder(['Speaker', 'Mikrofon', 'Al-Quran', 'Sajadah']);
    }

    public function test_sort_by_name_ascending(): void
    {
        $response = $this->getInventaris(['sort' => 'nama']);

        $response->assertSeeInOrder(['Al-Quran', 'Mikrofon', 'Sajadah', 'Speaker']);
    }

    public function test_invalid_sort_falls_back_to_latest(): void
    {
        $response = $this->getInventaris(['sort' => 'DROP TABLE']);

        $response->assertOk();
        $response->assertSeeInOrder(['Mikrofon', 'Al-Quran', 'Speaker', 'Sajadah']);
        $response->assertSee('<option value="terbaru" selected', false);
    }

    public function test_category_dropdown_lists_unique_categories(): void
    {
        $response = $this->getInventaris();

        $response->assertSee('<option value="Audio"', false);
        $response->assertSee('<option value="Ibadah"', false);
        $response->assertDontSee('<option value="ibadah"', false);
    }

    public function test_extract_categories_ignores_empty_and_placeholder(): void
    {
        $items = collect([
            (object) ['kategori' => '-'],
            (object) ['kategori' => ''],
            (object) ['kategori' => 'Zakat'],
            (object) ['kategori' => 'audio'],
        ]);

        $this->assertSame(['audio', 'Zakat'], (new InventarisController())->extractCategories($items));
    }
}
