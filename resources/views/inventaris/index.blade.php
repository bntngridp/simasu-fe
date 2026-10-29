@extends('layouts.app')

@section('title', 'Inventaris - SIMASU')

@section('content')

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold">Inventaris</h2>
        <p class="text-muted">Kelola semua aset dan perlengkapan masjid</p>
    </div>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalBarang">
        <i class="bi bi-plus-lg"></i> Tambah Barang
    </button>
</div>

{{-- CARD TABLE --}}
<div class="card shadow-sm">
    <div class="card-body">
        {{-- Search --}}
        <div class="row g-2 mb-3">
            <div class="col-md-8">
                <input
                type="text"
                class="form-control"
                id="searchInput"
                placeholder="Cari barang...">
            </div>
            <div class="col-md-4">
                <select class="form-select" id="statusFilter">
                    <option value="all">Semua Status</option>
                    <option value="tersedia">Tersedia</option>
                    <option value="terbatas">Terbatas</option>
                    <option value="habis">Habis</option>
                </select>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Deskripsi</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                        <th>Diperbarui</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @if(isset($inventaris) && count($inventaris) > 0)
                    @foreach($inventaris as $item)
                    @php
                    $jumlah = $item->jumlah ?? 0;
                    @endphp
                    <tr data-status="{{ $item->status_stok ?? 'Habis' }}">
                        <td class="fw-semibold">
                            {{ $item->nama_barang ?? '-' }}
                        </td>
                        <td>{{ $item->kategori ?? '-' }}</td>
                        <td>
                            <small class="text-muted">
                                {{ $item->deskripsi ? Str::limit($item->deskripsi, 50) : '-' }}
                            </small>
                        </td>
                        <td>{{ $item->jumlah ?? 0 }}</td>
                        <td>
                            @if($item->status_stok === 'Tersedia')
                                <span class="badge bg-success">Tersedia</span>
                            @elseif($item->status_stok === 'Terbatas')
                                <span class="badge bg-warning text-dark">Terbatas</span>
                            @else
                                <span class="badge bg-danger">Habis</span>
                            @endif
                        </td>
                        <td>
                            @if($item->updated_at)
                            {{ $item->updated_at->format('d/m/Y') }}
                            @else
                            -
                            @endif
                        </td>
                        <td class="text-center">
                            {{-- Tombol Edit dengan Data Attribute Lengkap --}}
                            <button
                                class="btn btn-sm btn-primary btn-edit"
                                data-id="{{ $item->id }}"
                                data-nama="{{ $item->nama_barang }}"
                                data-kategori="{{ $item->kategori }}"
                                data-jumlah="{{ $item->jumlah }}"
                                data-deskripsi="{{ $item->deskripsi ?? '' }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            
                            <form
                                action="{{ route('inventaris.destroy', $item->id) }}"
                                method="POST"
                                class="d-inline"
                                onsubmit="return confirm('Yakin ingin menghapus barang ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Belum ada data inventaris
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        {{-- Footer Count --}}
        <div class="text-center mt-3 text-muted small">
            Menampilkan
            <span id="itemCount">{{ isset($inventaris) ? count($inventaris) : 0 }}</span> dari
            <span id="totalCount">{{ isset($inventaris) ? count($inventaris) : 0 }}</span> barang
        </div>
    </div>
</div>

{{-- MODAL Tambah/Edit Barang --}}
<div class="modal fade" id="modalBarang" tabindex="-1" aria-labelledby="modalBarangLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBarangLabel">Tambah Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formBarang" method="POST" action="{{ route('inventaris.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST" id="formMethod">
                <input type="hidden" name="id" id="itemId">

                <div class="modal-body">
                    {{-- Nama Barang --}}
                    <div class="mb-3">
                        <label for="namaBarang" class="form-label">Nama Barang <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control"
                            id="namaBarang"
                            name="nama_barang"
                            placeholder="Contoh: Sajadah"
                            required>
                    </div>

                    {{-- Kategori --}}
                    <div class="mb-3">
                        <label for="kategori" class="form-label">Kategori <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control"
                            id="kategori"
                            name="kategori"
                            placeholder="Contoh: Perlengkapan Ibadah"
                            required>
                    </div>

                    {{-- Jumlah --}}
                    <div class="mb-3">
                        <label for="jumlah" class="form-label">Jumlah <span class="text-danger">*</span></label>
                        <input
                            type="number"
                            class="form-control"
                            id="jumlah"
                            name="jumlah"
                            min="0"
                            placeholder="0"
                            required>
                    </div>

                    {{-- Deskripsi --}}
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea
                            class="form-control"
                            id="deskripsi"
                            name="deskripsi"
                            rows="3"
                            placeholder="Catatan kondisi barang"></textarea>
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

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('tableBody');

        function applyInventoryFilter() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedStatus = document.getElementById('statusFilter').value;
            const rows = tableBody.getElementsByTagName('tr');
            let visibleCount = 0;

            Array.from(rows).forEach(row => {
                if (row.querySelector('td[colspan]')) return;
                const text = row.textContent.toLowerCase();
                const status = row.dataset.status || '';
                const matchesSearch = text.includes(searchTerm);
                const matchesStatus = selectedStatus === 'all' || status === selectedStatus;
                const isVisible = matchesSearch && matchesStatus;
                row.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });

            document.getElementById('itemCount').textContent = visibleCount;
        }

        searchInput.addEventListener('input', applyInventoryFilter);
        document.getElementById('statusFilter').addEventListener('change', applyInventoryFilter);

        // if (searchInput && tableBody) {
        //     searchInput.addEventListener('input', function(e) {
        //         const searchTerm = e.target.value.toLowerCase();
        //         const rows = tableBody.getElementsByTagName('tr');
        //         let visibleCount = 0;

        //         Array.from(rows).forEach(row => {
        //             const text = row.textContent.toLowerCase();
        //             const isVisible = text.includes(searchTerm);
        //             row.style.display = isVisible ? '' : 'none';
        //             if (isVisible && !row.querySelector('td[colspan]')) {
        //                 visibleCount++;
        //             }
        //         });

        //         document.getElementById('itemCount').textContent = visibleCount;
        //     });
        // }

        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nama = this.getAttribute('data-nama');
                const kategori = this.getAttribute('data-kategori');
                const jumlah = this.getAttribute('data-jumlah');
                const deskripsi = this.getAttribute('data-deskripsi');

                document.getElementById('modalBarangLabel').textContent = 'Edit Barang';

                const form = document.getElementById('formBarang');
                form.action = `/inventaris/${id}`;
                document.getElementById('formMethod').value = 'PUT';

                document.getElementById('namaBarang').value = nama;
                document.getElementById('kategori').value = kategori;
                document.getElementById('jumlah').value = jumlah;
                document.getElementById('deskripsi').value = deskripsi;
                document.getElementById('itemId').value = id;

                const modal = new bootstrap.Modal(document.getElementById('modalBarang'));
                modal.show();
            });
        });

        const modalElement = document.getElementById('modalBarang');
        if (modalElement) {
            modalElement.addEventListener('hidden.bs.modal', function() {
                document.getElementById('formBarang').reset();
                document.getElementById('modalBarangLabel').textContent = 'Tambah Barang';
                document.getElementById('formBarang').action = '{{ route("inventaris.store") }}';
                document.getElementById('formMethod').value = 'POST';
                document.getElementById('itemId').value = '';
            });
        }
    });
</script>
@endpush