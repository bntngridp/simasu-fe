<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventarisController extends Controller
{
    /** Stok di bawah atau sama dengan nilai ini dianggap "Terbatas". */
    public const LOW_STOCK_THRESHOLD = 10;

    /** Batas atas stok yang boleh diinput untuk mencegah nilai tidak wajar. */
    public const MAX_STOCK = 100000;

    /** Nilai sort yang diizinkan (whitelist). */
    public const SORT_OPTIONS = ['terbaru', 'nama', 'stok_terendah', 'stok_tertinggi'];

    private const DEFAULT_SORT = 'terbaru';

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

    /**
     * Aturan validasi bersama untuk tambah & ubah barang.
     */
    private function rules(): array
    {
        return [
            'nama_barang' => 'required|string|max:255',
            'kategori'    => 'required|string|max:100',
            'jumlah'      => 'required|integer|min:0|max:' . self::MAX_STOCK,
            'deskripsi'   => 'nullable|string|max:1000',
        ];
    }

    private function toApiPayload(array $validated): array
    {
        return [
            'name'        => trim($validated['nama_barang']),
            'category'    => trim($validated['kategori']),
            'stock'       => (int) $validated['jumlah'],
            'description' => $validated['deskripsi'] ?? '-',
        ];
    }

    /**
     * Catat aksi perubahan data inventaris (audit trail / non-repudiation).
     */
    private function audit(string $action, $id, bool $success): void
    {
        Log::info('inventaris.' . $action, [
            'id'      => $id,
            'success' => $success,
            'user'    => session('user.email') ?? session('user.name'),
            'ip'      => request()->ip(),
        ]);
    }

    public function resolveStockStatus(int $stock): string
    {
        if ($stock <= 0) {
            return 'Habis';
        }

        return $stock <= self::LOW_STOCK_THRESHOLD ? 'Terbatas' : 'Tersedia';
    }

    /**
     * Ambil data inventaris dari API dan petakan ke format view.
     */
    private function fetchInventaris(): Collection
    {
        $response = $this->api()->get($this->url());
        $data = $response->successful() ? $response->json() : [];

        return collect(is_array($data) ? $data : [])
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
            })
            ->values();
    }

    /**
     * Ringkasan stok untuk kartu statistik di atas tabel.
     */
    public function buildSummary(Collection $items): array
    {
        return [
            'total_barang' => $items->count(),
            'total_stok'   => (int) $items->sum('jumlah'),
            'tersedia'     => $items->where('status_stok', 'Tersedia')->count(),
            'terbatas'     => $items->where('status_stok', 'Terbatas')->count(),
            'habis'        => $items->where('status_stok', 'Habis')->count(),
        ];
    }

    /**
     * Daftar kategori unik (urut abjad) untuk dropdown filter.
     */
    public function extractCategories(Collection $items): array
    {
        return $items->pluck('kategori')
            ->filter(fn ($kategori) => is_string($kategori) && $kategori !== '' && $kategori !== '-')
            ->unique(fn ($kategori) => mb_strtolower($kategori))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Ambil parameter query sebagai string. Nilai non-skalar (mis. ?sort[]=nama)
     * diabaikan agar jatuh ke nilai default, bukan memicu error 500.
     */
    public function queryString(Request $request, string $key): string
    {
        $value = $request->query($key);

        return is_scalar($value) ? (string) $value : '';
    }

    public function normalizeSort(?string $sort): string
    {
        return in_array($sort, self::SORT_OPTIONS, true) ? $sort : self::DEFAULT_SORT;
    }

    /**
     * Filter berdasarkan kategori (case-insensitive) lalu urutkan sesuai pilihan.
     */
    public function applyFilters(Collection $items, ?string $kategori, ?string $sort): Collection
    {
        $kategori = $kategori !== null ? mb_strtolower(trim($kategori)) : '';

        if ($kategori !== '') {
            $items = $items->filter(fn ($item) => mb_strtolower((string) $item->kategori) === $kategori);
        }

        $sorted = match ($this->normalizeSort($sort)) {
            'nama'           => $items->sortBy(fn ($item) => mb_strtolower($item->nama_barang)),
            'stok_terendah'  => $items->sortBy('jumlah'),
            'stok_tertinggi' => $items->sortByDesc('jumlah'),
            default          => $items->sortByDesc('updated_at'),
        };

        return $sorted->values();
    }

    public function index(Request $request)
    {
        $semua = $this->fetchInventaris();

        $summary = $this->buildSummary($semua);
        $kategoriList = $this->extractCategories($semua);
        $kategoriAktif = $this->queryString($request, 'kategori');
        $sortAktif = $this->normalizeSort($this->queryString($request, 'sort'));

        $inventaris = $this->applyFilters($semua, $kategoriAktif, $sortAktif);

        return view('inventaris.index', compact(
            'inventaris',
            'summary',
            'kategoriList',
            'kategoriAktif',
            'sortAktif'
        ));
    }

    /**
     * Cegah CSV/formula injection saat file dibuka di Excel.
     */
    public function sanitizeCsvCell($value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'" . $value
            : $value;
    }

    public function export(Request $request): StreamedResponse
    {
        $items = $this->applyFilters(
            $this->fetchInventaris(),
            $this->queryString($request, 'kategori'),
            $this->queryString($request, 'sort')
        );

        $filename = 'inventaris-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($items) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Nama Barang', 'Kategori', 'Jumlah', 'Status', 'Deskripsi', 'Diperbarui']);

            foreach ($items as $item) {
                fputcsv($out, [
                    $this->sanitizeCsvCell($item->nama_barang),
                    $this->sanitizeCsvCell($item->kategori),
                    $item->jumlah,
                    $item->status_stok,
                    $this->sanitizeCsvCell($item->deskripsi),
                    $item->updated_at->format('Y-m-d'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $response = $this->api()->post($this->url(), $this->toApiPayload($validated));
        $this->audit('create', null, $response->successful());

        if ($response->successful()) {
            return redirect()->route('inventaris')->with('success', 'Barang berhasil ditambahkan!');
        }

        return redirect()->route('inventaris')
            ->with('error', 'Gagal menambahkan barang: ' . $this->apiMessage($response, 'Server Error'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate($this->rules());

        $response = $this->api()->put($this->url('/' . $id), $this->toApiPayload($validated));
        $this->audit('update', $id, $response->successful());

        if ($response->successful()) {
            return redirect()->route('inventaris')->with('success', 'Barang berhasil diperbarui!');
        }

        return redirect()->route('inventaris')
            ->with('error', 'Gagal update: ' . $this->apiMessage($response, 'Error'));
    }

    public function destroy($id)
    {
        $response = $this->api()->delete($this->url('/' . $id));
        $this->audit('delete', $id, $response->successful());

        if ($response->successful()) {
            return redirect()->route('inventaris')->with('success', 'Barang berhasil dihapus!');
        }

        return redirect()->route('inventaris')->with('error', 'Gagal menghapus barang.');
    }
}
