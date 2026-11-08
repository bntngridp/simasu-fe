@extends('layouts.app')

@section('title', 'Riwayat Keamanan - SIMASU')

@section('content')
    <div class="mb-4">
        <h1 class="h3 fw-bold">Riwayat Keamanan</h1>
        <p class="text-muted mb-0">Pantau aktivitas autentikasi pada panel admin SIMASU.</p>
    </div>

    <form method="GET" action="{{ route('keamanan.index') }}" class="card card-body border-0 shadow-sm mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="event" class="form-label">Jenis aktivitas</label>
                <select id="event" name="event" class="form-select">
                    <option value="">Semua aktivitas</option>
                    @foreach($labels as $value => $label)
                        <option value="{{ $value }}" @selected(request('event') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="date" class="form-label">Tanggal</label>
                <input id="date" name="date" type="date" value="{{ request('date') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="audit-email" class="form-label">Alamat email</label>
                <input id="audit-email" name="email" type="email" maxlength="254" value="{{ request('email') }}" class="form-control" placeholder="Semua akun">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-success">Terapkan</button>
                <a href="{{ route('keamanan.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">{{ $events->total() }} aktivitas tercatat</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th scope="col">Waktu</th><th scope="col">Aktivitas</th><th scope="col">Akun</th><th scope="col">Alamat IP</th></tr></thead>
                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td class="text-nowrap">{{ $event->created_at->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }} WIB</td>
                            <td>{{ $labels[$event->event] ?? $event->event }}</td>
                            <td>{{ $event->email ?? '-' }}</td>
                            <td>{{ $event->ip_address ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-5">Belum ada aktivitas keamanan yang sesuai filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($events->hasPages())
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <span class="small text-muted">Halaman {{ $events->currentPage() }} dari {{ $events->lastPage() }}</span>
                <div class="d-flex gap-2">
                    @if($events->previousPageUrl())<a class="btn btn-outline-secondary btn-sm" href="{{ $events->previousPageUrl() }}">Sebelumnya</a>@endif
                    @if($events->nextPageUrl())<a class="btn btn-outline-secondary btn-sm" href="{{ $events->nextPageUrl() }}">Berikutnya</a>@endif
                </div>
            </div>
        @endif
    </div>
@endsection
