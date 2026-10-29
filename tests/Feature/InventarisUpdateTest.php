<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InventarisUpdateTest extends TestCase
{
    public function test_inventory_update_sends_category_and_description(): void
    {
        $baseUrl = config('api.base_url');

        Http::fake([
            $baseUrl . '/api/inventory/7' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->withSession(['api_token' => 'token'])->put(route('inventaris.update', 7), [
            'nama_barang' => 'Sound System',
            'kategori' => 'Audio',
            'jumlah' => 12,
            'deskripsi' => 'Speaker utama masjid',
        ]);

        $response->assertRedirect(route('inventaris'));

        Http::assertSent(function ($request) use ($baseUrl) {
            return $request->method() === 'PUT'
                && $request->url() === $baseUrl . '/api/inventory/7'
                && $request['name'] === 'Sound System'
                && $request['category'] === 'Audio'
                && $request['stock'] === 12
                && $request['description'] === 'Speaker utama masjid';
        });
    }
}