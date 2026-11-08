<?php

namespace Tests\Feature;

use App\Http\Controllers\InventarisController;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventarisRefactorTest extends TestCase
{
    private string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->baseUrl = config('api.base_url');
    }

    public function test_status_filter_option_values_match_row_data_status(): void
    {
        Http::fake([
            $this->baseUrl . '/api/inventory' => Http::response([
                ['id' => 1, 'name' => 'Sajadah', 'stock' => 0],
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('inventaris'));

        $response->assertOk();
        $response->assertSee('<option value="Tersedia">', false);
        $response->assertSee('<option value="Terbatas">', false);
        $response->assertSee('<option value="Habis">', false);
        $response->assertDontSee('<option value="habis">', false);
    }

    public function test_empty_state_spans_all_seven_columns_when_api_fails(): void
    {
        Http::fake([
            $this->baseUrl . '/api/inventory' => Http::response('Internal Server Error', 500),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('inventaris'));

        $response->assertOk();
        $response->assertSee('colspan="7"', false);
        $response->assertSee('Belum ada data inventaris');
    }

    public function test_index_skips_malformed_items_without_id(): void
    {
        Http::fake([
            $this->baseUrl . '/api/inventory' => Http::response([
                ['name' => 'Tanpa ID', 'stock' => 3],
                'bukan-array',
                ['id' => 2, 'name' => 'Mikrofon', 'stock' => 5],
            ], 200),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('inventaris'));

        $response->assertOk();
        $response->assertSee('Mikrofon');
        $response->assertDontSee('Tanpa ID');
    }

    public function test_index_sends_bearer_token_from_session(): void
    {
        Http::fake([
            $this->baseUrl . '/api/inventory' => Http::response([], 200),
        ]);

        $this->withSession(['api_token' => 'rahasia-123'])->get(route('inventaris'))->assertOk();

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer rahasia-123'));
    }

    public function test_store_failure_with_non_json_body_uses_default_message(): void
    {
        Http::fake([
            $this->baseUrl . '/api/inventory' => Http::response('<html>502 Bad Gateway</html>', 502),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->post(route('inventaris.store'), [
            'nama_barang' => 'Karpet',
            'kategori' => 'Ibadah',
            'jumlah' => 4,
        ]);

        $response->assertRedirect(route('inventaris'));
        $response->assertSessionHas('error', 'Gagal menambahkan barang: Server Error');
    }

    public function test_store_failure_shows_message_from_api(): void
    {
        Http::fake([
            $this->baseUrl . '/api/inventory' => Http::response(['message' => 'Nama sudah dipakai'], 422),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->post(route('inventaris.store'), [
            'nama_barang' => 'Karpet',
            'kategori' => 'Ibadah',
            'jumlah' => 4,
        ]);

        $response->assertSessionHas('error', 'Gagal menambahkan barang: Nama sudah dipakai');
    }

    public function test_update_failure_with_non_json_body_does_not_crash(): void
    {
        Http::fake([
            $this->baseUrl . '/api/inventory/9' => Http::response('', 500),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->put(route('inventaris.update', 9), [
            'nama_barang' => 'Speaker',
            'kategori' => 'Audio',
            'jumlah' => 2,
        ]);

        $response->assertRedirect(route('inventaris'));
        $response->assertSessionHas('error', 'Gagal update: Error');
    }

    public function test_destroy_failure_flashes_error(): void
    {
        Http::fake([
            $this->baseUrl . '/api/inventory/5' => Http::response(['message' => 'Not found'], 404),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->delete(route('inventaris.destroy', 5));

        $response->assertRedirect(route('inventaris'));
        $response->assertSessionHas('error', 'Gagal menghapus barang.');
    }

    #[DataProvider('stockBoundaryProvider')]
    public function test_resolve_stock_status_boundaries(int $stock, string $expected): void
    {
        $controller = new InventarisController();

        $this->assertSame($expected, $controller->resolveStockStatus($stock));
    }

    public static function stockBoundaryProvider(): array
    {
        return [
            'stok negatif'          => [-3, 'Habis'],
            'stok nol'              => [0, 'Habis'],
            'stok satu'             => [1, 'Terbatas'],
            'tepat di ambang batas' => [InventarisController::LOW_STOCK_THRESHOLD, 'Terbatas'],
            'satu di atas ambang'   => [InventarisController::LOW_STOCK_THRESHOLD + 1, 'Tersedia'],
        ];
    }
}
