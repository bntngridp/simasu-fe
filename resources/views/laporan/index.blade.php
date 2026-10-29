@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Laporan Sistem (SIMASU)</h1>
        <a href="{{ route('laporan.export') }}" class="btn btn-primary">
            <i class="fas fa-download mr-1"></i> Ekspor PDF
        </a>
    </div>

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
                    <small class="text-warning">Pending: {{ $report['bookings']['pending'] }}</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection