@extends('layouts.app')

@section('title', 'Profil Pengguna - SIMASU')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Profil Pengguna</h2>
            <p class="text-muted">Kelola informasi akun dan preferensi keamanan</p>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card shadow-sm border-0 text-center py-4">
                <div class="card-body">
                    <div class="position-relative d-inline-block mb-3">
                        @if(!empty($user->profile_photo))
                            {{-- Jika ada foto, ambil dari URL API --}}
                            <img src="{{ config('api.base_url') . '/' . $user->profile_photo }}" 
                                alt="Profile Photo" 
                                class="rounded-circle border"
                                style="width: 120px; height: 120px; object-fit: cover;">
                        @else
                            {{-- Jika tidak ada, pakai inisial --}}
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white mx-auto" 
                                style="width: 120px; height: 120px; font-size: 3rem; font-weight: bold;">
                                {{ substr($user->name, 0, 2) }}
                            </div>
                        @endif
                        
                        <form id="formPhoto" action="{{ route('profil.photo') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <label for="photoInput" class="position-absolute bottom-0 end-0 bg-white rounded-circle shadow p-2" 
                                style="cursor: pointer; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"
                                title="Ganti Foto">
                                <i class="bi bi-camera-fill text-primary"></i>
                            </label>
                            <input type="file" name="photo" id="photoInput" class="d-none" onchange="document.getElementById('formPhoto').submit();">
                        </form>
                    </div>

                    <h4 class="fw-bold mb-1">{{ $user->name }}</h4>
                    <p class="text-muted mb-3">{{ $user->role }}</p>
                    <div class="badge bg-light text-dark p-2 w-100">
                        <i class="bi bi-calendar-check me-1"></i> Terdaftar sejak {{ \Carbon\Carbon::parse($user->created_at)->translatedFormat('d F Y') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-person-lines-fill me-2"></i>Informasi Pribadi</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profil.update') }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row gx-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Nama Lengkap</label>
                                <input type="text" class="form-control" name="name" value="{{ old('name', $user->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Jabatan</label>
                                {{-- UPDATE: Readonly agar tidak bisa diedit dan tidak dikirim ke server --}}
                                <input type="text" class="form-control bg-light" value="{{ $user->role }}" readonly>
                            </div>
                        </div>

                        <div class="row gx-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Email</label>
                                <input type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Nomor Telepon</label>
                                <input type="tel" class="form-control" name="phone" value="{{ old('phone', $user->phone) }}">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small text-muted">Alamat</label>
                            <textarea class="form-control" name="address" rows="3" placeholder="Masukkan alamat lengkap">{{ old('address', $user->address) }}</textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-danger"><i class="bi bi-shield-lock me-2"></i>Keamanan Akun</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profil.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label small text-muted">Password Saat Ini</label>
                            <input type="password" class="form-control" name="current_password" required placeholder="••••••••">
                        </div>

                        <div class="row gx-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Password Baru</label>
                                <input type="password" class="form-control" name="new_password" required placeholder="Masukkan password baru">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Konfirmasi Password Baru</label>
                                <input type="password" class="form-control" name="new_password_confirmation" required placeholder="Ulangi password baru">
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-outline-danger px-4">
                                <i class="bi bi-key me-1"></i> Ubah Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection