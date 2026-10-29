<aside class="d-flex flex-column flex-shrink-0 p-3 text-white" style="width:260px; min-height:100vh; background:#15803d;">
    <div class="d-flex align-items-center gap-2 mb-4">
        <img src="{{ asset('logo.png') }}" width="36">
        <span class="fs-5 fw-bold">SIMASU</span>
    </div>

    <ul class="nav nav-pills flex-column mb-auto gap-1">
        <li class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link text-white {{ request()->routeIs('dashboard*') ? 'active bg-success' : '' }}">
                <img src="{{ asset('icons/home.png') }}" width="18" class="me-2">
                Dashboard
            </a>
        </li>

        <li>
            <a href="{{ route('inventaris') }}" class="nav-link text-white {{ request()->routeIs('inventaris*') ? 'active bg-success' : '' }}">
                <img src="{{ asset('icons/cube.png') }}" width="18" class="me-2">
                Inventaris
            </a>
        </li>

        <li>
            <a href="{{ route('ruangan') }}" class="nav-link text-white {{ request()->routeIs('ruangan*') ? 'active bg-success' : '' }}">
                <img src="{{ asset('icons/door.png') }}" width="18" class="me-2">
                Sewa Ruangan
            </a>
        </li>

        <li>
            <a href="{{ route('kalender') }}" class="nav-link text-white {{ request()->routeIs('kalender*') ? 'active bg-success' : '' }}">
                <img src="{{ asset('icons/calendar.png') }}" width="18" class="me-2">
                Kalender
            </a>
        </li>


        <li>
            <a href="{{ route('notifikasi.index') }}" class="nav-link text-white {{ request()->routeIs('notifikasi*') ? 'active bg-success' : '' }}">
                <i class="bi bi-bell me-2"></i>
                Notifikasi
            </a>
        </li>

        <li>
            <a href="{{ route('laporan.index') }}" class="nav-link text-white {{ request()->routeIs('laporan.*') ? 'active bg-success' : '' }}" >
                <i class="fas fa-file-alt"></i>
                <img src="{{ asset('icons/document.png') }}" width="18" class="me-2">
                <span>Laporan</span>
            </a>
        </li>

        <li>
            <a href="{{ route('profil') }}" class="nav-link text-white {{ request()->routeIs('profil') ? 'active bg-success' : '' }}">
                <img src="{{ asset('icons/user.png') }}" width="18" class="me-2">
                Profil
            </a>
        </li>

        <li class="nav-item mt-3">
            <a href="{{ route('permintaan.index') }}"
                class="nav-link text-white text-decoration-underline hover-biru {{ request()->routeIs('permintaan*') ? 'active bg-success' : '' }}">

                <span class="ms-4">Permintaan</span>
            </a>
        </li>
    </ul>
    <div class="mt-auto small text-white-50">
        <div class="fw-semibold text-white">
            {{ session('user.name', 'Admin SIMASU') }}
        </div>
        <div>
            {{ ucfirst(session('user.role', 'admin')) }}
        </div>
        <div class="mt-auto small text-white-50 border-top border-light pt-3">
    </div>
    <hr class="text-white">

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-outline-light w-100">
            Logout
        </button>
    </form>
</aside>

<style>
    .hover-biru {
        transition: all 0.5s ease;
    }
    .hover-biru:hover {
        background-color: #0d6efd !important;
        color: white !important;
    }
</style>