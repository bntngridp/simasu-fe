<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InventarisController extends Controller
{
    private function resolveStockStatus(int $stock): string
    {
        if ($stock <= 0) {
            return 'Habis';
        } elseif ($stock <= 10) {
            return 'Terbatas';
        } else {
            return 'Tersedia';
        }
    }

    public function index()
    {
        $baseUrl = config('api.base_url');

        $http = Http::withoutVerifying()->timeout(5);
        $response = $http->get($baseUrl . '/api/inventory');

        $inventaris = collect(
            $response->successful() ? $response->json() : []
        )->map(function ($item) {
            $stock = (int) ($item['stock'] ?? 0);
            
            return (object) [
                'id'           => $item['id'],
                'nama_barang'  => $item['name'] ?? '-',
                'kategori'     => $item['category'] ?? '-',
                'jumlah'       => $stock,
                'status'       => $item['status'] ?? 'Tersedia',
                'status_stok'  => $this->resolveStockStatus($stock),
                'deskripsi'    => $item['description'] ?? '-',
                'updated_at'   => isset($item['updatedAt']) ? \Carbon\Carbon::parse($item['updatedAt']) : now(),
            ];
        })->sortByDesc('updated_at')->values();
        
        return view('inventaris.index', compact('inventaris'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'kategori'    => 'required|string',
            'jumlah'      => 'required|integer|min:0',
            'deskripsi'   => 'nullable|string'
        ]);
        
        $baseUrl = config('api.base_url');
        $token = session('api_token');

        $response = Http::withoutVerifying()->withToken($token)->post($baseUrl . '/api/inventory', [
            'name'        => $validated['nama_barang'],
            'category'    => $validated['kategori'],
            'stock'       => (int) $validated['jumlah'],
            'description' => $request->deskripsi ?? '-'
        ]);
        
        if ($response->successful()) {
            return redirect()->route('inventaris')->with('success', 'Barang berhasil ditambahkan!');
        }
        
        return redirect()->route('inventaris')
            ->with('error', 'Gagal menambahkan barang: ' . ($response->json()['message'] ?? 'Server Error'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'kategori'    => 'required|string|max:100',
            'jumlah'      => 'required|integer|min:0',
            'deskripsi'   => 'nullable|string|max:1000',
        ]);
        
        $baseUrl = config('api.base_url');
        $token = session('api_token');

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->put($baseUrl . '/api/inventory/' . $id, [
                'name'  => $validated['nama_barang'],
                'category' => $validated['kategori'],
                'stock' => (int) $validated['jumlah'],
                'description' => $validated['deskripsi'] ?? '-',
            ]);
        
        if ($response->successful()) {
            return redirect()->route('inventaris')->with('success', 'Barang berhasil diperbarui!');
        }
        
        return redirect()->route('inventaris')
            ->with('error', 'Gagal update: ' . ($response->json()['message'] ?? 'Error'));
    }

    public function destroy($id)
    {
        $baseUrl = config('api.base_url');
        $token = session('api_token');

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->delete($baseUrl . '/api/inventory/' . $id);
        
        if ($response->successful()) {
            return redirect()->route('inventaris')->with('success', 'Barang berhasil dihapus!');
        }
        
        return redirect()->route('inventaris')->with('error', 'Gagal menghapus barang.');
    }

    public function show($id)
    {
        $baseUrl = config('api.base_url');
        $response = Http::withoutVerifying()->get($baseUrl . '/api/inventory/' . $id);
        
        if ($response->successful()) {
            $data = $response->json();
            $mapped = [
                'id' => $data['id'],
                'nama_barang' => $data['name'],
                'kategori' => $data['category'],
                'jumlah' => $data['stock'],
                'deskripsi' => $data['description']
            ];
            return response()->json($mapped);
        }
        return response()->json(['error' => 'Data tidak ditemukan'], 404);
    }
}