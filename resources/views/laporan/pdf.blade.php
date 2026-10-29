<!DOCTYPE html>
<html>
<head>
    <title>Laporan SIMASU</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .section-title { font-size: 16px; font-weight: bold; margin-top: 20px; color: #333; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN SISTEM MANAJEMEN SARPRAS (SIMASU)</h1>
        <p>Dicetak pada: {{ $report['date'] }}</p>
    </div>

    <div class="section-title">1. Ringkasan Inventaris</div>
    <table class="table">
        <thead>
            <tr>
                <th>Kategori</th>
                <th>Nilai</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Total Item Barang</td><td>{{ $report['inventory']['total_items'] }}</td></tr>
            <tr><td>Total Stok Fisik</td><td>{{ $report['inventory']['total_stock'] }}</td></tr>
            <tr><td>Barang Stok Terbatas</td><td>{{ $report['inventory']['low_stock'] }}</td></tr>
            <tr><td>Barang Stok Habis</td><td>{{ $report['inventory']['out_of_stock'] }}</td></tr>
        </tbody>
    </table>

    <div class="section-title">2. Ringkasan Fasilitas & Ruangan</div>
    <table class="table">
        <thead>
            <tr>
                <th>Kategori</th>
                <th>Nilai</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Total Ruangan</td><td>{{ $report['rooms']['total_rooms'] }}</td></tr>
            <tr><td>Total Kapasitas (Orang)</td><td>{{ $report['rooms']['capacity_sum'] }}</td></tr>
        </tbody>
    </table>

    <div class="section-title">3. Statistik Permintaan (Booking)</div>
    <table class="table">
        <thead>
            <tr>
                <th>Status</th>
                <th>Jumlah Pengajuan</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Disetujui</td><td>{{ $report['bookings']['approved'] }}</td></tr>
            <tr><td>Menunggu (Pending)</td><td>{{ $report['bookings']['pending'] }}</td></tr>
            <tr><td>Ditolak</td><td>{{ $report['bookings']['rejected'] }}</td></tr>
            <tr style="font-weight: bold;"><td>Total Seluruhnya</td><td>{{ $report['bookings']['total'] }}</td></tr>
        </tbody>
    </table>

    <div class="footer">
        Sistem Informasi Manajemen Sarana dan Prasarana - Proyek DevOps
    </div>
</body>
</html>