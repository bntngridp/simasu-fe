@extends('layouts.app')

@section('title', 'Notifikasi Admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="fw-bold mb-1">Notifikasi Admin</h2>
            <p class="text-muted mb-0">Pantau stok kritis, booking pending, dan event terdekat dalam satu halaman.</p>
        </div>
        <span class="badge bg-danger-subtle text-danger fs-6 px-3 py-2">
            {{ $summary['total'] }} prioritas
        </span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-warning shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Stok Kritis</small>
                        <h3 class="fw-bold text-warning mb-0">{{ $summary['critical_inventory'] }}</h3>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-2 text-warning"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Booking Pending</small>
                        <h3 class="fw-bold text-danger mb-0">{{ $summary['pending_bookings'] }}</h3>
                    </div>
                    <i class="bi bi-hourglass-split fs-2 text-danger"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-primary shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Event Terdekat</small>
                        <h3 class="fw-bold text-primary mb-0">{{ $summary['upcoming_events'] }}</h3>
                    </div>
                    <i class="bi bi-calendar-event fs-2 text-primary"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Stok Kritis</div>
                <div class="list-group list-group-flush">
                    @forelse($criticalInventory as $item)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">{{ $item['name'] }}</div>
                                <small class="text-muted">Kategori: {{ $item['category'] }}</small>
                            </div>
                            <span class="badge bg-warning text-dark">Stok {{ $item['stock'] }}</span>
                        </div>
                    @empty
                        <div class="list-group-item text-muted">Tidak ada stok kritis.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Booking Pending</div>
                <div class="list-group list-group-flush">
                    @forelse($pendingBookings as $booking)
                        <div class="list-group-item">
                            <div class="fw-semibold">{{ $booking['item_name'] }}</div>
                            <small class="text-muted">Diajukan oleh {{ $booking['user_name'] }}</small>
                        </div>
                    @empty
                        <div class="list-group-item text-muted">Tidak ada booking pending.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Event Terdekat</div>
                <div class="list-group list-group-flush">
                    @forelse($upcomingEvents as $event)
                        <div class="list-group-item">
                            <div class="fw-semibold">{{ $event['title'] }}</div>
                            <small class="text-muted">
                                {{ $event['location'] }} • {{ $event['date_object']->format('d M Y H:i') }}
                            </small>
                        </div>
                    @empty
                        <div class="list-group-item text-muted">Tidak ada event terdekat.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
