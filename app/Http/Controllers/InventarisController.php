<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InventarisController extends Controller
{
    /** Stok di bawah atau sama dengan nilai ini dianggap "Terbatas". */
    public const LOW_STOCK_THRESHOLD = 10;

    private const ENDPOINT = '/api/inventory';

    /**
     * HTTP client terpusat untuk API inventaris.
     * Verifikasi SSL dikontrol lewat config (API_VERIFY_SSL), tidak dimatikan permanen.
     */
    private function api(): PendingRequest
    {
        $client = Http::withOptions(['verify' => (bool) config('api.verify_ssl', true)])
            ->timeout(5);

        $token = session('api_token');

        return $token ? $client->withToken($token) : $client;
    }

    private function url(string $path = ''): string
    {
        return config('api.base_url') . self::ENDPOINT . $path;
    }

    /**
     * Ambil pesan error dari response API tanpa crash bila body bukan JSON.
     */
    private function apiMessage(ApiResponse $response, string $default): string
    {
        $message = $response->json('message');

        return is_string($message) && $message !== '' ? $message : $default;
    }

    public function resolveStockStatus(int $stock): string
    {
        if ($stock <= 0) {
            return 'Habis';
        }

        return $stock <= self::LOW_STOCK_THRESHOLD ? 'Terbatas' : 'Tersedia';
    }

    public function index()
    {
        $response = $this->api()->get($this->url());
        $data = $response->successful() ? $response->json() : [];

        $inventaris = collect(is_array($data) ? $data : [])
            ->filter(fn ($item) => is_array($item) && isset($item['id']))
            ->map(function ($item) {
                $stock = (int) ($item['stock'] ?? 0);

                return (object) [
                    'id'           => $item['id'],
                    'nama_barang'  => $item['name'] ?? '-',
                    'kategori'     => $item['category'] ?? '-',
                    'jumlah'       => $stock,
                    'status'       => $item['status'] ?? 'Tersedia',
                    'status_stok'  => $this->resolveStockStatus($stock),
                    'deskripsi'    => $item['description'] ?? '-',
                    'updated_at'   => isset($item['updatedAt']) ? Carbon::parse($item['updatedAt']) : now(),
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

        $response = $this->api()->post($this->url(), [
            'name'        => $validated['nama_barang'],
            'category'    => $validated['kategori'],
            'stock'       => (int) $validated['jumlah'],
            'description' => $request->deskripsi ?? '-'
        ]);

        if ($response->successful()) {
            return redirect()->route('inventaris')->with('success', 'Barang berhasil ditambahkan!');
        }

        return redirect()->route('inventaris')
            ->with('error', 'Gagal menambahkan barang: ' . $this->apiMessage($response, 'Server Error'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'kategori'    => 'required|string|max:100',
            'jumlah'      => 'required|integer|min:0',
            'deskripsi'   => 'nullable|string|max:1000',
        ]);

        $response = $this->api()->put($this->url('/' . $id), [
            'name'        => $validated['nama_barang'],
            'category'    => $validated['kategori'],
            'stock'       => (int) $validated['jumlah'],
            'description' => $validated['deskripsi'] ?? '-',
        ]);

        if ($response->successful()) {
            return redirect()->route('inventaris')->with('success', 'Barang berhasil diperbarui!');
        }

        return redirect()->route('inventaris')
            ->with('error', 'Gagal update: ' . $this->apiMessage($response, 'Error'));
    }

    public function destroy($id)
    {
        $response = $this->api()->delete($this->url('/' . $id));

        if ($response->successful()) {
            return redirect()->route('inventaris')->with('success', 'Barang berhasil dihapus!');
        }

        return redirect()->route('inventaris')->with('error', 'Gagal menghapus barang.');
    }

    public function show($id)
    {
        $response = Http::withoutVerifying()->get(config('api.base_url') . '/api/inventory/' . $id);

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
