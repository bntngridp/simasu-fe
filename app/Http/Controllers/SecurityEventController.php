<?php

namespace App\Http\Controllers;

use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Kontribusi Bintang (BNT-03): halaman riwayat keamanan khusus admin
 * dengan validasi filter aktivitas, email, tanggal, dan pagination.
 */
class SecurityEventController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->session()->get('user.role') === 'admin', 403);

        $filters = $request->validate([
            'event' => ['nullable', Rule::in(array_keys(SecurityEvent::LABELS))],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'email' => ['nullable', 'email', 'max:254'],
        ]);

        $events = SecurityEvent::query()
            ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', $event))
            ->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', $date))
            ->when($filters['email'] ?? null, fn ($query, $email) => $query->where('email', mb_strtolower(trim($email))))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('keamanan.index', [
            'events' => $events,
            'labels' => SecurityEvent::LABELS,
        ]);
    }
}
