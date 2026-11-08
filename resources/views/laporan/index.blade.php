@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Laporan Sistem (SIMASU)</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('laporan.export', request()->only(['start_date', 'end_date'])) }}" class="btn btn-primary">
                <i class="fas fa-file-pdf mr-1"></i> Ekspor PDF
            </a>
            <a href="{{ route('laporan.export.csv', request()->only(['start_date', 'end_date'])) }}" class="btn btn-outline-secondary">
                <i class="fas fa-file-csv mr-1"></i> Ekspor CSV
            </a>
        </div>
    </div>

    <form action="{{ route('laporan.index') }}" method="GET" class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Tanggal mulai booking</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $report['filters']['start_date'] }}">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">Tanggal akhir booking</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $report['filters']['end_date'] }}">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary">Terapkan</button>
                    <a href="{{ route('laporan.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>
            @if ($errors->any())
                <div class="alert alert-danger mt-3 mb-0" role="alert">{{ $errors->first() }}</div>
            @endif
            @if ($report['filters']['start_date'])
                <small class="text-muted d-block mt-3">Ringkasan permintaan dibatasi pada tanggal mulai booking yang dipilih.</small>
            @endif
        </div>
    </form>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Barang Inventaris</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $report['inventory']['total_items'] }} Item</div>
                    <hr>
                    <small class="text-warning">Stok Terbatas: {{ $report['inventory']['low_stock'] }}</small><br>
                    <small class="text-danger">Stok Habis: {{ $report['inventory']['out_of_stock'] }}</small>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Kapasitas Fasilitas</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $report['rooms']['total_rooms'] }} Ruangan</div>
                    <hr>
                    <small>Total Kapasitas: {{ $report['rooms']['capacity_sum'] }} Orang</small>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Status Permintaan</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $report['bookings']['total'] }} Total Pengajuan</div>
                    <hr>
                    <small class="text-primary">Disetujui: {{ $report['bookings']['approved'] }}</small> | 
                    <small class="text-warning">Pending: {{ $report['bookings']['pending'] }}</small> |
                    <small class="text-danger">Ditolak: {{ $report['bookings']['rejected'] }}</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection