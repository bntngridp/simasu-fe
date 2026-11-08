<?php

namespace App\Http\Controllers;

use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SecurityEventController extends Controller
{
    private const EXPORT_LIMIT = 5000;

    public function index(Request $request)
    {
        $filters = $this->authorizedFilters($request);

        $events = $this->filteredQuery($filters)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('keamanan.index', [
            'events' => $events,
            'labels' => SecurityEvent::LABELS,
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->authorizedFilters($request);
        $filename = 'riwayat-keamanan-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, ['Waktu (WIB)', 'Aktivitas', 'Akun', 'Alamat IP'], ',', '"', '');

            $rows = $this->filteredQuery($filters)
                ->latest('id')
                ->limit(self::EXPORT_LIMIT)
                ->cursor();

            foreach ($rows as $event) {
                fputcsv($out, [
                    $event->created_at->timezone('Asia/Jakarta')->format('d-m-Y H:i:s'),
                    SecurityEvent::LABELS[$event->event] ?? $event->event,
                    $this->csvSafe($event->email),
                    $this->csvSafe($event->ip_address),
                ], ',', '"', '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function authorizedFilters(Request $request): array
    {
        abort_unless($request->session()->get('user.role') === 'admin', 403);

        return $request->validate([
            'event' => ['nullable', Rule::in(array_keys(SecurityEvent::LABELS))],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'email' => ['nullable', 'email', 'max:254'],
        ]);
    }

    private function filteredQuery(array $filters)
    {
        return SecurityEvent::query()
            ->when($filters['event'] ?? null, fn ($q, $event) => $q->where('event', $event))
            ->when($filters['date'] ?? null, fn ($q, $date) => $q->whereDate('created_at', $date))
            ->when($filters['email'] ?? null, fn ($q, $email) => $q->where('email', mb_strtolower(trim($email))));
    }

    /** Cegah CSV/formula injection: nilai berawalan = + - @ diberi tanda petik. */
    private function csvSafe(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}