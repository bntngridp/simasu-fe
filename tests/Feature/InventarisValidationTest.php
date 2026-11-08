<?php

namespace Tests\Feature;

use App\Http\Controllers\InventarisController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventarisValidationTest extends TestCase
{
    private string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = config('api.base_url');
    }

    private function validPayload(array $override = []): array
    {
        return array_merge([
            'nama_barang' => 'Karpet',
            'kategori'    => 'Ibadah',
            'jumlah'      => 4,
            'deskripsi'   => 'Karpet saf',
        ], $override);
    }

    private function postStore(array $payload)
    {
        return $this->withSession(['api_token' => 'token'])
            ->from(route('inventaris'))
            ->post(route('inventaris.store'), $payload);
    }

    public function test_store_rejects_negative_stock_and_does_not_call_api(): void
    {
        Http::fake();

        $this->postStore($this->validPayload(['jumlah' => -1]))
            ->assertSessionHasErrors('jumlah');

        Http::assertNothingSent();
    }

    public function test_store_rejects_stock_above_maximum(): void
    {
        Http::fake();

        $this->postStore($this->validPayload(['jumlah' => InventarisController::MAX_STOCK + 1]))
            ->assertSessionHasErrors('jumlah');

        Http::assertNothingSent();
    }

    public function test_store_rejects_too_long_name_category_and_description(): void
    {
        Http::fake();

        $this->postStore($this->validPayload([
            'nama_barang' => Str::repeat('a', 256),
            'kategori'    => Str::repeat('b', 101),
            'deskripsi'   => Str::repeat('c', 1001),
        ]))->assertSessionHasErrors(['nama_barang', 'kategori', 'deskripsi']);

        Http::assertNothingSent();
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        Http::fake();

        $this->postStore([])->assertSessionHasErrors(['nama_barang', 'kategori', 'jumlah']);

        Http::assertNothingSent();
    }

    public function test_store_accepts_values_on_maximum_boundary(): void
    {
        Http::fake([$this->baseUrl . '/api/inventory' => Http::response(['id' => 1], 201)]);

        $this->postStore($this->validPayload([
            'nama_barang' => Str::repeat('a', 255),
            'jumlah'      => InventarisController::MAX_STOCK,
        ]))->assertSessionHasNoErrors()->assertSessionHas('success');
    }

    public function test_store_sends_trimmed_and_validated_payload_only(): void
    {
        Http::fake([$this->baseUrl . '/api/inventory' => Http::response(['id' => 1], 201)]);

        $this->postStore($this->validPayload([
            'nama_barang' => '  Karpet  ',
            'deskripsi'   => null,
            'role'        => 'admin',
        ]));

        Http::assertSent(fn ($request) => $request['name'] === 'Karpet'
            && $request['description'] === '-'
            && ! isset($request['role']));
    }

    public function test_store_writes_audit_log(): void
    {
        Http::fake([$this->baseUrl . '/api/inventory' => Http::response(['id' => 1], 201)]);
        Log::spy();

        $this->postStore($this->validPayload());

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message, $context) => $message === 'inventaris.create' && $context['success'] === true)
            ->once();
    }

    public function test_destroy_writes_audit_log_with_id(): void
    {
        Http::fake([$this->baseUrl . '/api/inventory/8' => Http::response(null, 204)]);
        Log::spy();

        $this->withSession(['api_token' => 'token'])->delete(route('inventaris.destroy', 8));

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message, $context) => $message === 'inventaris.delete' && $context['id'] == 8)
            ->once();
    }

    public function test_inventory_routes_require_login(): void
    {
        Http::fake();

        $this->get(route('inventaris'))->assertRedirect(route('login'));
        $this->post(route('inventaris.store'), $this->validPayload())->assertRedirect(route('login'));
        $this->delete(route('inventaris.destroy', 1))->assertRedirect(route('login'));

        Http::assertNothingSent();
    }
}
