@extends('layouts.app')

@section('title', 'Kalender Peminjaman & Sewa')

@section('content')
<div class="container-fluid px-4 py-3">

    <div class="row mb-4">
        <div class="col-md-8">
            <div class="mb-4">
                <p class="text-muted small mb-1">Pantau ketersediaan inventaris dan ruangan</p>
                <h4 class="fw-bold mb-0">Kalender Peminjaman & Sewa</h4>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0 fw-semibold">
                            {{ $currentDate->locale('id')->translatedFormat('F Y') }}
                        </h5>
                        <div class="btn-group">
                            <a href="?month={{ $currentDate->copy()->subMonth()->month }}&year={{ $currentDate->copy()->subMonth()->year }}"
                                class="btn btn-sm btn-outline-secondary">
                                ← Sebelumnya
                            </a>
                            <a href="{{ route('kalender') }}" class="btn btn-sm btn-outline-success">
                                Hari Ini
                            </a>
                            <a href="?month={{ $currentDate->copy()->addMonth()->month }}&year={{ $currentDate->copy()->addMonth()->year }}"
                                class="btn btn-sm btn-outline-secondary">
                                Berikutnya →
                            </a>
                        </div>
                    </div>

                    <div class="calendar-container">
                        <div class="row g-0 border-bottom bg-light">
                            @foreach(['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'] as $day)
                            <div class="col text-center py-2 small fw-semibold text-muted">{{ $day }}</div>
                            @endforeach
                        </div>

                        @php
                        $firstDay = $currentDate->copy()->startOfMonth();
                        $lastDay = $currentDate->copy()->endOfMonth();
                        $startDayOfWeek = $firstDay->dayOfWeek; // 0=Sunday, 6=Saturday
                        $daysInMonth = $lastDay->day;
                        $currentDay = 1;
                        $totalCells = ceil(($daysInMonth + $startDayOfWeek) / 7) * 7;
                        @endphp

                        @for($i = 0; $i < $totalCells; $i++)
                            @if($i % 7==0)
                            <div class="row g-0 border-bottom">
                            @endif

                            <div class="col calendar-day border-end position-relative" style="min-height: 80px;">
                                @if($i >= $startDayOfWeek && $currentDay <= $daysInMonth)
                                    @php
                                    $dateKey=$currentDate->copy()->day($currentDay)->format('Y-m-d');
                                    $hasBooking = isset($bookingsByDate[$dateKey]);
                                    $hasRuangan = $hasBooking && ($bookingsByDate[$dateKey]['ruangan'] ?? false);
                                    $hasBarang = $hasBooking && ($bookingsByDate[$dateKey]['barang'] ?? false);
                                    @endphp

                                    <div class="p-2 h-100 d-flex flex-column position-relative calendar-day-cell"
                                        style="cursor: pointer;"
                                        data-day="{{ $currentDay }}"
                                        data-date="{{ $dateKey }}">
                                        <div class="d-flex justify-content-between align-items-start mb-auto">
                                            <span class="fw-semibold">{{ $currentDay }}</span>
                                        </div>
                                        @if($hasBooking)
                                        <div class="d-flex gap-1 justify-content-center mt-auto">
                                            @if($hasBarang)
                                            <span class="d-inline-block rounded-circle"
                                                style="width: 6px; height: 6px; background-color: #EF5350;"
                                                title="Ada peminjaman barang"></span>
                                            @endif
                                            @if($hasRuangan)
                                            <span class="d-inline-block rounded-circle"
                                                style="width: 6px; height: 6px; background-color: #2196F3;"
                                                title="Ada sewa ruangan"></span>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                    @php $currentDay++; @endphp
                                    @endif
                            </div>

                            @if(($i + 1) % 7 == 0)
                    </div>
                    @endif
                    @endfor
                </div>

                <div class="mt-4 pt-3 border-top">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background-color: #EF5350;"></span>
                                <small class="text-muted">Ada Peminjaman Barang</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background-color: #2196F3;"></span>
                                <small class="text-muted">Ada Sewa Ruangan</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Ringkasan Bulan Ini</h6>
                <div class="row text-center g-2">
                    <div class="col-6">
                        <div class="fw-bold">{{ $bookingSummary['total'] ?? 0 }}</div>
                        <small class="text-muted">Total</small>
                    </div>
                    <div class="col-6">
                        <div class="fw-bold text-warning">{{ $bookingSummary['pending'] ?? 0 }}</div>
                        <small class="text-muted">Pending</small>
                    </div>
                    <div class="col-6">
                        <div class="fw-bold text-primary">{{ $bookingSummary['room'] ?? 0 }}</div>
                        <small class="text-muted">Ruangan</small>
                    </div>
                    <div class="col-6">
                        <div class="fw-bold text-danger">{{ $bookingSummary['inventory'] ?? 0 }}</div>
                        <small class="text-muted">Barang</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-semibold">Daftar Peminjaman & Sewa</h6>
            </div>
            <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                @if(empty($monthlyBookings))
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-calendar-x fs-1 mb-2 d-block"></i>
                    <p class="mb-0">Belum ada peminjaman bulan ini</p>
                    @if(config('app.debug'))
                    <div class="alert alert-info mt-3 text-start" style="font-size: 0.85rem;">
                        <strong>🔍 Troubleshooting:</strong>
                        <ol class="mb-0 mt-2">
                            <li>Cek endpoint: <code>{{ config('api.base_url') }}/api/bookings</code></li>
                            <li>Format tanggal API: <code>YYYY-MM-DD HH:mm</code></li>
                            <li>Field yang dicari: <code>start_time</code>, <code>tanggal_mulai</code>, atau <code>start_date</code></li>
                            <li>Field type: <code>room</code> atau <code>inventory</code></li>
                            <li>Lihat log: <code>tail -f storage/logs/laravel.log</code></li>
                        </ol>
                    </div>
                    @endif
                </div>
                @else
                @foreach($monthlyBookings as $booking)
                <div class="p-3 border-bottom booking-item">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h6 class="mb-0 fw-semibold">{{ $booking['nama'] }}</h6>
                                @if($booking['type'] === 'room')
                                <span class="d-inline-block rounded-circle"
                                    style="width: 8px; height: 8px; background-color: #2196F3;"></span>
                                @else
                                <span class="d-inline-block rounded-circle"
                                    style="width: 8px; height: 8px; background-color: #EF5350;"></span>
                                @endif
                            </div>
                            <small class="text-muted d-block mb-1">
                                <i class="bi bi-person"></i> {{ $booking['peminjam'] }}
                            </small>
                            <small class="text-muted d-block">
                                <i class="bi bi-clock"></i> {{ $booking['tanggal_mulai'] }}<br>
                                <i class="bi bi-clock-fill"></i> {{ $booking['tanggal_selesai'] }}
                            </small>
                            @if($booking['type'] === 'inventory' && $booking['quantity'] > 1)
                            <small class="text-muted d-block mt-1">
                                <i class="bi bi-box"></i> Jumlah: {{ $booking['quantity'] }}
                            </small>
                            @endif
                            @php
                            try {
                            $start = \Carbon\Carbon::parse($booking['tanggal_mulai']);
                            $end = \Carbon\Carbon::parse($booking['tanggal_selesai']);
                            $days = $start->diffInDays($end);
                            if ($days > 0) {
                            echo '<small class="text-muted d-block mt-1"><i class="bi bi-calendar-range"></i> Durasi: ' . ($days + 1) . ' hari</small>';
                            }
                            } catch (\Exception $e) {
                            }
                            @endphp
                        </div>
                        <div class="text-center ms-2">
                            <div class="bg-light rounded px-3 py-2 mb-2">
                                <div class="fw-bold text-primary" style="font-size: 1.2rem;">
                                    {{ $booking['tanggal'] }}
                                </div>
                            </div>
                            <span class="badge 
                                            @if($booking['status'] == 'approved') bg-success
                                            @elseif($booking['status'] == 'pending') bg-warning text-dark
                                            @elseif($booking['status'] == 'rejected') bg-danger
                                            @elseif($booking['status'] == 'completed') bg-info
                                            @else bg-secondary
                                            @endif">
                                @if($booking['status'] == 'approved') Disetujui
                                @elseif($booking['status'] == 'pending') Menunggu
                                @elseif($booking['status'] == 'rejected') Ditolak
                                @elseif($booking['status'] == 'completed') Selesai
                                @else {{ ucfirst($booking['status']) }}
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
</div>

<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('kalender.store') }}" method="POST" id="bookingForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Peminjaman/Sewa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipe <span class="text-danger">*</span></label>
                        <select class="form-select" name="type" id="bookingType" required onchange="loadItems()">
                            <option value="inventory">Pinjam Barang</option>
                            <option value="room">Sewa Ruangan</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Item/Ruangan <span class="text-danger">*</span></label>
                        <select class="form-select" name="item_id" id="itemSelect" required onchange="updateItemName()">
                            <option value="">-- Pilih --</option>
                        </select>
                        <input type="hidden" name="item_name" id="itemName">
                    </div>

                    <div class="mb-3" id="quantityField" style="display: none;">
                        <label class="form-label fw-semibold">Jumlah</label>
                        <input type="number" class="form-control" name="quantity" id="quantityInput" min="1" value="1">
                        <small class="text-muted" id="stockInfo"></small>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="start_date" id="startDate" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jam Mulai <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="start_time" value="08:00" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="end_date" id="endDate" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jam Selesai <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="end_time" value="17:00" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan (Opsional)</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Tambahkan catatan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" id="submitBookingBtn">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .calendar-day {
        transition: background-color 0.2s ease;
    }

    .calendar-day:hover {
        background-color: #f8f9fa;
    }

    .booking-item {
        transition: background-color 0.2s ease;
    }

    .booking-item:hover {
        background-color: #f8f9fa;
    }

    .badge.rounded-circle {
        display: inline-block;
        border: none;
    }
</style>
@endpush

@php
$inventoryJson = json_encode($inventory);
$roomsJson = json_encode($rooms);
$bookingsByDateJson = json_encode($bookingsByDate);
$monthlyBookingsJson = json_encode($monthlyBookings);
$successMsg = session('success');
$errorMsg = session('error');
@endphp

@push('scripts')
<script>
    var __inventory = <?php echo $inventoryJson; ?>;
    var __rooms = <?php echo $roomsJson; ?>;
    var __bookingsByDate = <?php echo $bookingsByDateJson; ?>;
    var __monthlyBookings = <?php echo $monthlyBookingsJson; ?>;

    const inventory = __inventory;
    const rooms = __rooms;

    console.log('📦 Inventory data:', inventory);
    console.log('🏢 Rooms data:', rooms);
    console.log('📅 Bookings by date:', __bookingsByDate);
    console.log('📋 Monthly bookings:', __monthlyBookings);

    function openBookingModal(day, dateKey) {
        const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
        document.getElementById('startDate').value = dateKey;
        document.getElementById('endDate').value = dateKey;
        loadItems();
        modal.show();
    }

    function loadItems() {
        const type = document.getElementById('bookingType').value;
        const itemSelect = document.getElementById('itemSelect');
        const quantityField = document.getElementById('quantityField');
        const stockInfo = document.getElementById('stockInfo');

        itemSelect.innerHTML = '<option value="">-- Pilih --</option>';

        if (type === 'inventory') {
            quantityField.style.display = 'block';

            if (inventory && inventory.length > 0) {
                let hasAvailable = false;
                inventory.forEach(item => {
                    const stock = parseInt(item.stock) || 0;
                    if (stock > 0) {
                        hasAvailable = true;
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = `${item.name} (Stok: ${stock})`;
                        option.dataset.name = item.name;
                        option.dataset.stock = stock;
                        itemSelect.appendChild(option);
                    }
                });

                if (!hasAvailable) {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'Tidak ada barang dengan stok tersedia';
                    option.disabled = true;
                    itemSelect.appendChild(option);
                }
            } else {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'Tidak ada barang tersedia';
                option.disabled = true;
                itemSelect.appendChild(option);
            }
        } else {
            quantityField.style.display = 'none';
            stockInfo.textContent = '';

            if (rooms && rooms.length > 0) {
                let hasAvailable = false;
                rooms.forEach(room => {
                    const isAvailable = room.is_available === true ||
                        room.is_available === 1 ||
                        room.is_available === '1' ||
                        room.status === 'tersedia' ||
                        room.status === 'available';

                    if (isAvailable) {
                        hasAvailable = true;
                        const option = document.createElement('option');
                        option.value = room.id;
                        const capacityText = room.capacity ? ` (Kapasitas: ${room.capacity} orang)` : '';
                        option.textContent = `${room.name}${capacityText}`;
                        option.dataset.name = room.name;
                        itemSelect.appendChild(option);
                    }
                });

                if (!hasAvailable) {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'Tidak ada ruangan tersedia';
                    option.disabled = true;
                    itemSelect.appendChild(option);
                }
            } else {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'Tidak ada ruangan tersedia';
                option.disabled = true;
                itemSelect.appendChild(option);
            }
        }
    }

    function updateItemName() {
        const itemSelect = document.getElementById('itemSelect');
        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        const itemName = selectedOption.dataset.name || '';
        const stock = selectedOption.dataset.stock;

        document.getElementById('itemName').value = itemName;

        if (stock) {
            const stockInfo = document.getElementById('stockInfo');
            const quantityInput = document.getElementById('quantityInput');
            stockInfo.textContent = `Stok tersedia: ${stock}`;
            quantityInput.max = stock;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadItems();

        // Handle calendar day clicks
        const calendarDays = document.querySelectorAll('.calendar-day-cell');
        calendarDays.forEach(dayElement => {
            dayElement.addEventListener('click', function() {
                const day = this.dataset.day;
                const dateKey = this.dataset.date;
                openBookingModal(parseInt(day), dateKey);
            });
        });

        const bookingForm = document.getElementById('bookingForm');
        if (bookingForm) {
            bookingForm.addEventListener('submit', function(e) {
                const submitBtn = document.getElementById('submitBookingBtn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
            });
        }
    });
    $successMsg ? 'Swal_shown' : '';

    document.addEventListener('DOMContentLoaded', function() {
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');

        if (startDateInput && endDateInput) {
            const today = new Date().toISOString().slice(0, 10);
            startDateInput.min = today;
            endDateInput.min = today;

            startDateInput.addEventListener('change', () => {
                endDateInput.min = startDateInput.value;
                if (endDateInput.value && endDateInput.value < startDateInput.value) {
                    endDateInput.value = startDateInput.value;
                }
            });
        }
    });
</script>
@if($successMsg)
@if(class_exists('SweetAlert'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: <?php echo json_encode($successMsg); ?>,
        timer: 2000,
        showConfirmButton: false
    }).then(() => {
        window.location.reload();
    });
</script>
@else
<script>
    alert(<?php echo json_encode($successMsg); ?>);
    window.location.reload();
</script>
@endif
@endif

@if($errorMsg)
@if(class_exists('SweetAlert'))
<script>
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: <?php echo json_encode($errorMsg); ?>,
        showConfirmButton: true
    });
</script>
@else
<script>
    alert(<?php echo json_encode($errorMsg); ?>);
</script>
@endif
@endif
@endpush