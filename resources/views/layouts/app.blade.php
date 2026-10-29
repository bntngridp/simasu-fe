<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'SIMASU')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @vite(['resources/js/app.js'])

    <style>
        .command-palette-list {
            max-height: 360px;
            overflow-y: auto;
        }

        .command-palette-item.active,
        .command-palette-item:focus {
            background-color: #0d6efd;
            color: #fff;
        }

        .command-palette-item.active small,
        .command-palette-item:focus small,
        .command-palette-item.active i,
        .command-palette-item:focus i {
            color: #fff !important;
        }
    </style>
</head>
<body class="bg-light">

<div class="d-flex">

    {{-- Sidebar --}}
    @include('partials.sidebar')

    {{-- Main Content --}}
    <main class="flex-fill p-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        @endif
        <div class="d-flex justify-content-end mb-3">
            <button
                type="button"
                class="btn btn-outline-secondary btn-sm w-auto"
                id="openCommandPalette"
                aria-label="Buka navigasi cepat">
                <i class="bi bi-command me-1"></i>
                Navigasi Cepat
                <kbd class="ms-2">Ctrl</kbd> + <kbd>K</kbd>
            </button>
        </div>

        @yield('content')
    </main>

</div>

<div class="modal fade" id="commandPaletteModal" tabindex="-1" aria-labelledby="commandPaletteTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="commandPaletteTitle">Navigasi Cepat</h5>
                    <small class="text-muted">Cari halaman atau aksi yang ingin dibuka.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input
                        type="text"
                        class="form-control"
                        id="commandPaletteSearch"
                        placeholder="Ketik dashboard, inventaris, ruangan..."
                        autocomplete="off">
                </div>

                <div class="list-group command-palette-list" id="commandPaletteList">
                    <a href="{{ route('dashboard') }}" class="list-group-item list-group-item-action command-palette-item" data-command-keywords="dashboard beranda ringkasan statistik">
                        <div class="fw-semibold"><i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard</div>
                        <small class="text-muted">Lihat ringkasan aplikasi dan aktivitas terbaru.</small>
                    </a>
                    <a href="{{ route('inventaris') }}" class="list-group-item list-group-item-action command-palette-item" data-command-keywords="inventaris barang stok perlengkapan">
                        <div class="fw-semibold"><i class="bi bi-box-seam me-2 text-success"></i>Inventaris</div>
                        <small class="text-muted">Kelola data barang dan stok.</small>
                    </a>
                    <a href="{{ route('ruangan') }}" class="list-group-item list-group-item-action command-palette-item" data-command-keywords="ruangan sewa aula fasilitas">
                        <div class="fw-semibold"><i class="bi bi-door-open me-2 text-warning"></i>Sewa Ruangan</div>
                        <small class="text-muted">Kelola data ruangan dan kapasitas.</small>
                    </a>
                    <a href="{{ route('kalender') }}" class="list-group-item list-group-item-action command-palette-item" data-command-keywords="kalender jadwal booking peminjaman">
                        <div class="fw-semibold"><i class="bi bi-calendar-event me-2 text-danger"></i>Kalender</div>
                        <small class="text-muted">Lihat jadwal peminjaman dan sewa.</small>
                    </a>
                    <a href="{{ route('permintaan.index') }}" class="list-group-item list-group-item-action command-palette-item" data-command-keywords="permintaan approval pending setuju tolak">
                        <div class="fw-semibold"><i class="bi bi-inbox me-2 text-info"></i>Permintaan</div>
                        <small class="text-muted">Review permintaan peminjaman.</small>
                    </a>
                    <a href="{{ route('notifikasi.index') }}" class="list-group-item list-group-item-action command-palette-item" data-command-keywords="notifikasi admin stok kritis booking pending event">
                        <div class="fw-semibold"><i class="bi bi-bell me-2 text-warning"></i>Notifikasi</div>
                        <small class="text-muted">Pantau stok kritis, booking pending, dan event terdekat.</small>
                    </a>
                    <a href="{{ route('profil') }}" class="list-group-item list-group-item-action command-palette-item" data-command-keywords="profil akun admin password foto">
                        <div class="fw-semibold"><i class="bi bi-person-circle me-2 text-secondary"></i>Profil</div>
                        <small class="text-muted">Kelola data akun admin.</small>
                    </a>
                </div>

                <div class="text-center text-muted py-3 d-none" id="commandPaletteEmpty">
                    Tidak ada menu yang cocok.
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
