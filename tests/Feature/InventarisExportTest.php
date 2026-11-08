<?php

namespace Tests\Feature;

use App\Http\Controllers\InventarisController;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InventarisExportTest extends TestCase
{
    private string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = config('api.base_url');
    }

    private function fakeInventory(array $items): void
    {
        Http::fake([$this->baseUrl . '/api/inventory' => Http::response($items, 200)]);
    }

    private function exportCsv(array $query = []): string
    {
        $response = $this->withSession(['api_token' => 'token'])->get(route('inventaris.export', $query));
        $response->assertOk();

        return $response->streamedContent();
    }

    public function test_export_returns_csv_download(): void
    {
        $this->fakeInventory([]);

        $response = $this->withSession(['api_token' => 'token'])->get(route('inventaris.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename=inventaris-', $response->headers->get('Content-Disposition'));
    }

    public function test_export_contains_header_and_rows(): void
    {
        $this->fakeInventory([
            ['id' => 1, 'name' => 'Sajadah', 'category' => 'Ibadah', 'stock' => 3, 'description' => 'Hijau', 'updatedAt' => '2026-11-01'],
        ]);

        $lines = array_values(array_filter(explode("\n", $this->exportCsv())));

        $this->assertSame('"Nama Barang",Kategori,Jumlah,Status,Deskripsi,Diperbarui', $lines[0]);
        $this->assertSame('Sajadah,Ibadah,3,Terbatas,Hijau,2026-11-01', $lines[1]);
    }

    public function test_export_with_empty_data_only_has_header(): void
    {
        $this->fakeInventory([]);

        $lines = array_values(array_filter(explode("\n", $this->exportCsv())));

        $this->assertCount(1, $lines);
    }

    public function test_export_escapes_commas_and_quotes(): void
    {
        $this->fakeInventory([
            ['id' => 1, 'name' => 'Kabel, roll', 'category' => 'Audio', 'stock' => 20, 'description' => 'Merk "X"'],
        ]);

        $csv = $this->exportCsv();

        $this->assertStringContainsString('"Kabel, roll"', $csv);
        $this->assertStringContainsString('"Merk ""X"""', $csv);
    }

    public function test_export_respects_category_filter(): void
    {
        $this->fakeInventory([
            ['id' => 1, 'name' => 'Sajadah', 'category' => 'Ibadah', 'stock' => 3],
            ['id' => 2, 'name' => 'Mikrofon', 'category' => 'Audio', 'stock' => 3],
        ]);

        $csv = $this->exportCsv(['kategori' => 'audio']);

        $this->assertStringContainsString('Mikrofon', $csv);
        $this->assertStringNotContainsString('Sajadah', $csv);
    }

    public function test_export_neutralizes_formula_injection(): void
    {
        $this->fakeInventory([
            ['id' => 1, 'name' => '=HYPERLINK("http://evil")', 'category' => 'Audio', 'stock' => 1],
        ]);

        $csv = $this->exportCsv();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_sanitize_csv_cell_keeps_normal_text(): void
    {
        $controller = new InventarisController();

        $this->assertSame('Sajadah', $controller->sanitizeCsvCell('Sajadah'));
        $this->assertSame('', $controller->sanitizeCsvCell(''));
        $this->assertSame("'+62812", $controller->sanitizeCsvCell('+62812'));
        $this->assertSame("'@SUM(A1)", $controller->sanitizeCsvCell('@SUM(A1)'));
    }

    public function test_export_requires_login(): void
    {
        Http::fake();

        $this->get(route('inventaris.export'))->assertRedirect(route('login'));

        Http::assertNothingSent();
    }
}
