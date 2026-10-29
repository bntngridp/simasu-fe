@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

{{-- HEADER --}}
<div class="mb-4">
    <h2 class="fw-bold">Dashboard</h2>
    <p class="text-muted">
        Selamat datang di Sistem Manajemen Inventaris dan Ruangan Masjid
    </p>
</div>

{{-- STAT --}}
<div class="row g-3 mb-4">

    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Total Inventaris</small>
                    <h3 class="fw-bold">{{ $totalInventaris }}</h3>
                </div>
                <div class="bg-success-subtle p-3 rounded">
                    <img
                        src="{{ asset('icons/cube-green-1024.png') }}"
                        alt="Inventaris"
                        width="28"
                    >
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Ruangan Tersedia</small>
                    <h3 class="fw-bold">{{ $ruanganTersedia }}</h3>
                </div>
                <div class="bg-primary-subtle p-3 rounded">
                    <img
                        src="{{ asset('icons/door-green.png') }}"
                        alt="Ruangan"
                        width="28"
                    >
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Anggota Aktif</small>
                    <h3 class="fw-bold">{{ $anggotaAktif }}</h3>
                </div>
                <div class="bg-secondary-subtle p-3 rounded">
                    <img
                        src="{{ asset('icons/user-blue.png') }}"
                        alt="Anggota"
                        width="28"
                    >
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-warning">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Inventaris Kritis</small>
                    <h3 class="fw-bold text-warning">{{ $inventarisKritis }}</h3>
                </div>
                <div class="bg-warning-subtle p-3 rounded">
                    <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
                </div>
            </div>
        </div>
    </div>

</div>


{{-- Berita & Aktivitas --}}
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Berita & Pengumuman</span>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAnnouncement">
                    <i class="bi bi-plus-circle"></i> Tambah Pengumuman
                </button>
            </div>

            <ul class="list-group list-group-flush">
                @forelse ($berita as $item)
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="fw-semibold mb-1">{{ $item->title }}</h6>
                            <p class="text-muted mb-2">
                                {{ $item->subtitle }}
                            </p>
                            <span class="badge bg-primary">
                                {{ $item->tag }}
                            </span>
                        </div>
                        <div class="d-flex gap-2 ms-3">
                            <button type="button"
                                    class="btn btn-sm btn-outline-warning"
                                    onclick="editAnnouncement({{ json_encode($item) }})"
                                    title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('announcements.delete', $item->id) }}" 
                                    method="POST" 
                                    class="d-inline"
                                    onsubmit="return confirm('Yakin ingin menghapus pengumuman ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="btn btn-sm btn-outline-danger"
                                        title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </li>
                @empty
                <li class="list-group-item text-center text-muted py-4">
                    Belum ada pengumuman
                </li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">
                Aktivitas Terbaru
            </div>

            <div class="card-body">
                @forelse ($activities as $activity)
                    <div class="mb-3 d-flex gap-2">
                        <div>
                            @if ($activity['type'] === 'barang')
                                <i class="bi bi-box-seam text-success"></i>
                            @elseif ($activity['type'] === 'booking')
                                <i class="bi bi-calendar-check text-warning"></i>
                            @else
                                <i class="bi bi-door-open text-primary"></i>
                            @endif
                        </div>
                        <div>
                            <h6 class="fw-semibold mb-0">
                                {{ $activity['title'] }}
                            </h6>
                            <small class="text-muted">
                                {{ $activity['description'] }}
                            </small>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center">Belum ada aktivitas</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Agenda Mendatang --}}
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Agenda Mendatang</span>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalEvent">
            <i class="bi bi-plus-circle"></i> Tambah Event
        </button>
    </div>

    <div class="card-body">
        <div class="row row-cols-1 row-cols-md-3 g-3">
            @forelse ($agenda as $item)
                <div class="col">
                    <div class="card h-100 position-relative">
                        <div class="card-body">
                            <h6 class="fw-bold mb-2">
                                {{ $item->title }}
                            </h6>

                            <p class="text-muted small mb-2">
                                {{ $item->subtitle }}
                            </p>
                        </div>

                        <div class="card-footer bg-white border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-success d-block mb-1">
                                        {{ $item->location }}
                                    </span>

                                    @if ($item->datetime)
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($item->datetime, 'UTC')
                                                ->setTimezone('Asia/Jakarta')
                                                ->translatedFormat('d M Y, H:i') }} WIB
                                        </small>
                                    @endif
                                </div>

                                <div class="d-flex gap-1">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-warning"
                                            onclick='editEvent(@json($item))'
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('events.delete', $item->id) }}" 
                                            method="POST" 
                                            class="d-inline"
                                            onsubmit="return confirm('Yakin ingin menghapus event ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-sm btn-outline-danger"
                                                title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <p class="text-muted text-center py-4">Belum ada agenda mendatang.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Modal Announcement (Create/Edit) --}}
<div class="modal fade" id="modalAnnouncement" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAnnouncement" method="POST" action="{{ route('announcements.store') }}">
                @csrf
                <input type="hidden" name="_method" id="announcementMethod" value="POST">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAnnouncementTitle">Tambah Pengumuman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="announcement_title" class="form-label">Judul <span class="text-danger">*</span></label>
                        <input type="text" 
                                class="form-control" 
                                id="announcement_title" 
                                name="title" 
                                required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="announcement_subtitle" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                    id="announcement_subtitle" 
                                    name="subtitle" 
                                    rows="3" 
                                    required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="announcement_tag" class="form-label">Tag <span class="text-danger">*</span></label>
                        <input type="text" 
                                class="form-control" 
                                id="announcement_tag" 
                                name="tag" 
                                placeholder="Contoh: Informasi, Penting, Acara"
                                required>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Event (Create/Edit) --}}
<div class="modal fade" id="modalEvent" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEvent" method="POST" action="{{ route('events.store') }}">
                @csrf
                <input type="hidden" name="_method" id="eventMethod" value="POST">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEventTitle">Tambah Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="event_title" class="form-label">Judul Event <span class="text-danger">*</span></label>
                        <input type="text" 
                                class="form-control" 
                                id="event_title" 
                                name="title" 
                                required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="event_subtitle" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                    id="event_subtitle" 
                                    name="subtitle" 
                                    rows="3" 
                                    required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="event_date" class="form-label">Tanggal & Waktu <span class="text-danger">*</span></label>
                        <input type="datetime-local" 
                                class="form-control" 
                                id="event_date" 
                                name="datetime" 
                                required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="event_location" class="form-label">Lokasi <span class="text-danger">*</span></label>
                        <input type="text" 
                                class="form-control" 
                                id="event_location" 
                                name="location" 
                                placeholder="Contoh: Aula Utama"
                                required>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
window.editAnnouncement = function(data) {
    document.getElementById('modalAnnouncementTitle').textContent = "Edit Pengumuman";
    document.getElementById('formAnnouncement').action = "{{ url('dashboard/announcements') }}/" + data.id;
    document.getElementById('announcementMethod').value = "PUT";
    
    document.getElementById('announcement_title').value = data.title;
    document.getElementById('announcement_subtitle').value = data.subtitle;
    document.getElementById('announcement_tag').value = data.tag;
    
    var modal = new bootstrap.Modal(document.getElementById('modalAnnouncement'));
    modal.show();
}

window.editEvent = function(data) {
    document.getElementById('modalEventTitle').textContent = "Edit Event";
    document.getElementById('formEvent').action =
        "{{ url('dashboard/events') }}/" + data.id;
    document.getElementById('eventMethod').value = "PUT";

    document.getElementById('event_title').value = data.title ?? '';
    document.getElementById('event_subtitle').value = data.subtitle ?? '';
    document.getElementById('event_location').value = data.location ?? '';

    const eventDate = data.event_date ?? data.datetime;
    if (eventDate) {
        const date = new Date(eventDate);

        const formatted =
            date.getFullYear() + '-' +
            String(date.getMonth() + 1).padStart(2, '0') + '-' +
            String(date.getDate()).padStart(2, '0') + 'T' +
            String(date.getHours()).padStart(2, '0') + ':' +
            String(date.getMinutes()).padStart(2, '0');

        document.getElementById('event_date').value = formatted;
    }

    const modal = new bootstrap.Modal(
        document.getElementById('modalEvent')
    );
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const eventDateInput = document.getElementById('event_date');
    if (eventDateInput) {
        eventDateInput.min = new Date().toISOString().slice(0, 16);
    }

    document.getElementById('modalAnnouncement').addEventListener('hidden.bs.modal', function () {
        document.getElementById('formAnnouncement').reset();
        document.getElementById('formAnnouncement').action = "{{ route('announcements.store') }}";
        document.getElementById('announcementMethod').value = "POST";
        document.getElementById('modalAnnouncementTitle').textContent = "Tambah Pengumuman";
    });

    document.getElementById('modalEvent').addEventListener('hidden.bs.modal', function () {
        document.getElementById('formEvent').reset();
        document.getElementById('formEvent').action = "{{ route('events.store') }}";
        document.getElementById('eventMethod').value = "POST";
        document.getElementById('modalEventTitle').textContent = "Tambah Event";
    });
});
</script>
@endpush