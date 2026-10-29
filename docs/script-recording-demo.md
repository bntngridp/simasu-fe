# Naskah Demonstrasi Video Fitur SIMASU-Web

Dokumen ini memuat panduan lengkap dan skrip naskah untuk perekaman video demonstrasi sistem SIMASU-Web (Sistem Informasi Manajemen Sarana dan Prasarana). Naskah disusun secara berurutan mencakup pembukaan, demonstrasi setiap modul berdasarkan kontribusi anggota tim, hingga penutup.

---

## 1. Informasi dan Metadata Perekaman

- Judul Video: Demonstrasi Terpadu Fitur SIMASU-Web
- Target Durasi: 5 sampai 7 menit
- Format Perekaman: Rekaman Layar (Screen Recording) dengan Narasi Suara
- Lingkungan Sistem:
  - Frontend Web Admin: http://localhost:7080
  - Frontend Vite Dev: http://localhost:7081
  - Backend REST API: http://localhost:7082
  - Dokumentasi API Swagger: http://localhost:7082/docs
- Kredensial Pengujian:
  - Akun Admin: email admin@gmail.com, sandi admin123
  - Akun User: email user@gmail.com, sandi password123

---

## 2. Pemetaan Fitur dan Anggota Tim

| No | Nama Anggota | Modul / Fitur | Berkas Pengendali Utama | Komponen Tampilan Utama |
|---|---|---|---|---|
| 1 | Bintang | Autentikasi dan Keamanan Sesi | app/Http/Controllers/AuthController.php | resources/views/auth/login.blade.php |
| 2 | Devon | Manajemen Inventaris dan Stok | app/Http/Controllers/InventarisController.php | resources/views/inventaris/index.blade.php |
| 3 | Adrian | Manajemen Sewa Ruangan | app/Http/Controllers/RuanganController.php | resources/views/ruangan/index.blade.php |
| 4 | Arzu | Kalender Jadwal Reservasi | app/Http/Controllers/KalenderController.php | resources/views/kalender/index.blade.php |
| 5 | Aryo | Notifikasi dan Ringkasan Permintaan | app/Http/Controllers/NotifikasiController.php | resources/views/notifikasi/index.blade.php |
| 6 | Dillan | Persetujuan Permintaan (Approval) | app/Http/Controllers/PermintaanController.php | resources/views/permintaan/index.blade.php |

---

## 3. Daftar Periksa Sebelum Perekaman (Pre-Recording Checklist)

Sebelum memulai tombol rekam, pastikan hal-hal berikut:
1. Seluruh layanan lokal berjalan normal:
   - Port 7080 aktif merespons web admin.
   - Port 7081 aktif untuk aset styling Vite.
   - Port 7082 aktif melayani backend Express dan database SQLite.
2. Buka peramban dan posisikan tab pada halaman login: http://localhost:7080/login.
3. Atur resolusi layar peramban pada rasio 16:9 (disarankan Full HD 1080p) dengan zoom level 100 persen agar seluruh tabel dan tombol terlihat jelas.
4. Pastikan mikrofon berada dalam kondisi hening tanpa gangguan suara bising di latar belakang.

---

## 4. Naskah Video Demonstrasi (Script)

### Bagian 1: Pembukaan (Opening)

- Panduan Visual Layar:
  Tampilkan halaman muka peramban di http://localhost:7080/login. Gerakkan kursor secara tenang ke arah logo dan teks SIMASU.
- Teks Narasi Suara:
  "Halo semuanya. Pada video demonstrasi kali ini, kami akan mempresentasikan sistem SIMASU-Web, yaitu Sistem Informasi Manajemen Sarana dan Prasarana. Aplikasi ini berfungsi sebagai panel administrasi terpadu untuk mengelola inventaris barang, persewaan ruangan, penjadwalan kalender, serta pemrosesan persetujuan peminjaman fasilitas secara efisien.

  Proyek ini dikembangkan bersama oleh tim kami dengan pembagian tanggung jawab modul:
  - Bintang pada modul Autentikasi dan Keamanan,
  - Devon pada modul Manajemen Inventaris,
  - Adrian pada modul Sewa Ruangan,
  - Arzu pada modul Kalender Jadwal,
  - Aryo pada modul Notifikasi dan Permintaan, serta
  - Dillan pada modul Persetujuan atau Approval.

  Mari kita masuk ke demonstrasi fitur yang pertama."

---

### Bagian 2: Bintang — Fitur Autentikasi dan Keamanan Sesi

- Panduan Visual Layar:
  1. Ketik email: user@gmail.com dan sandi: password123 pada kolom formulir login.
  2. Klik tombol "Masuk". Sorot kotak pesan kesalahan merah yang muncul: "Gagal! Akun ini bukan admin."
  3. Ganti email menjadi: admin@gmail.com dan sandi: admin123.
  4. Klik tombol "Masuk". Tampilkan proses transisi masuk ke halaman Dashboard, lalu sorot nama Super Admin pada pojok kiri bawah sidebar.
- Teks Narasi Suara:
  "Fitur pertama dikembangkan oleh Bintang, yaitu sistem Autentikasi dan Otorisasi Berbasis Peran.

  Pertama, kita uji ketahanan keamanannya. Sistem ini menerapkan prinsip Least Privilege. Jika akun pengguna biasa atau non-admin mencoba masuk ke panel admin ini, sistem secara otomatis menolak akses dan memunculkan notifikasi peringatan: Gagal! Akun ini bukan admin. Sesi pengguna langsung dibatalkan demi keamanan.

  Sekarang, kita masukkan akun resmi admin, yaitu admin@gmail.com dengan sandi admin123. Ketika tombol masuk ditekan, sistem melakukan verifikasi ke server backend lokal, menerima token JWT, dan langsung mengarahkan kita ke Dashboard utama dengan status Super Admin aktif."

---

### Bagian 3: Devon — Fitur Manajemen Inventaris

- Panduan Visual Layar:
  1. Klik menu "Inventaris" pada navigasi sidebar.
  2. Tampilkan tabel daftar aset barang inventaris.
  3. Sorot kolom "Status Stok" yang menampilkan badge Tersedia atau Terbatas.
  4. Buka modal tambah barang baru, masukkan nama barang uji (contoh: Lampu Sorot Panggung) dan jumlah stok, lalu klik simpan untuk memperlihatkan data langsung masuk ke tabel.
- Teks Narasi Suara:
  "Fitur kedua dikembangkan oleh Devon, yaitu modul Manajemen Inventaris Sarana dan Prasarana.

  Pada modul ini, administrator dapat memantau katalog seluruh barang yang tersedia. Setiap entri barang menyajikan informasi nama aset, kategori, jumlah unit fisik, dan deskripsi penggunaan.

  Modul ini dilengkapi logika penentuan status stok otomatis. Jika stok aman, status tercatat sebagai Tersedia. Apabila stok berada di rentang satu hingga sepuluh unit, status otomatis beralih menjadi Terbatas untuk memberi sinyal kewaspadaan bagi pengelola. Seluruh operasi penambahan barang baru, pengubahan jumlah, dan penghapusan data terintegrasi secara instan ke basis data."

---

### Bagian 4: Adrian — Fitur Sewa Ruangan

- Panduan Visual Layar:
  1. Klik menu "Sewa Ruangan" pada sidebar.
  2. Tampilkan katalog daftar ruangan (seperti Aula Utama Masjid dan Ruang Rapat DKM).
  3. Tunjukkan rincian kapasitas orang, lokasi lantai, fasilitas penunjang, dan badge status okupansi.
- Teks Narasi Suara:
  "Fitur ketiga dikembangkan oleh Adrian, yaitu modul Manajemen Sewa Ruangan.

  Modul ini mencatat seluruh fasilitas ruangan yang dapat dimanfaatkan untuk kegiatan ibadah, kajian, maupun pertemuan organisasi. Setiap ruangan memuat spesifikasi kapasitas jamaah, lokasi lantai, serta fasilitas yang tersedia seperti AC, sistem audio, dan proyektor.

  Fitur unggulan pada modul ini adalah kalkulasi status okupansi waktu nyata. Sistem mencocokkan waktu saat ini dengan rentang jadwal peminjaman yang telah disetujui. Jika ruangan sedang digunakan untuk suatu agenda, statusnya otomatis berubah menjadi Dipakai, dan apabila kosong, statusnya adalah Tersedia. Logika ini efektif mencegah terjadinya benturan jadwal penggunaan ruangan."

---

### Bagian 5: Arzu — Fitur Kalender Jadwal Reservasi

- Panduan Visual Layar:
  1. Klik menu "Kalender" pada sidebar.
  2. Tampilkan kisi kalender bulanan terpadu.
  3. Klik tombol navigasi bulan sebelumnya dan bulan berikutnya untuk memperlihatkan fleksibilitas jadwal.
  4. Tunjukkan tanggal yang memiliki penanda kegiatan atau reservasi peminjaman.
- Teks Narasi Suara:
  "Fitur keempat dikembangkan oleh Arzu, yaitu modul Kalender Jadwal Reservasi Terpadu.

  Modul ini menyajikan visualisasi kalender bulanan interaktif yang menggabungkan seluruh agenda kegiatan ruangan dan peminjaman inventaris ke dalam satu tampilan terpadu. Administrator dapat berpindah bulan dan tahun dengan cepat untuk meninjau kepadatan jadwal.

  Sistem kalender ini dilengkapi validasi waktu yang ketat, di mana batas akhir peminjaman wajib diatur setelah waktu mulai kegiatan. Selain itu, penanganan galat yang tangguh memastikan kalender tetap tampil stabil meskipun terjadi keterlambatan respon data."

---

### Bagian 6: Aryo — Fitur Notifikasi dan Pemantauan Permintaan

- Panduan Visual Layar:
  1. Klik menu "Notifikasi" pada sidebar.
  2. Tunjukkan panel "Stok Kritis" yang mendeteksi aset dengan jumlah stok lima unit ke bawah (contoh: Mikrofon Wireless).
  3. Tunjukkan panel pengingat pengajuan peminjaman yang masih berstatus Pending.
- Teks Narasi Suara:
  "Fitur kelima dikembangkan oleh Aryo, yaitu modul Pusat Notifikasi dan Pemantauan Permintaan.

  Modul ini berfungsi sebagai sistem deteksi dini bagi pengelola sarana prasarana. Secara otomatis, sistem memfilter dan menampilkan barang-barang inventaris yang telah mencapai batas stok kritis, yaitu lima unit ke bawah. Informasi ini sangat krusial agar pengelola dapat segera merencanakan pengadaan atau perbaikan sebelum operasional terganggu.

  Di samping itu, halaman ini juga memberikan peringatan terhadap permohonan peminjaman yang masih tertahan pada status pending agar segera ditindaklanjuti oleh admin."

---

### Bagian 7: Dillan — Fitur Persetujuan Permintaan (Approval)

- Panduan Visual Layar:
  1. Klik menu "Permintaan" pada sidebar.
  2. Tunjukkan kartu metrik di bagian atas: Total Permintaan, Pending, Disetujui, dan Ditolak.
  3. Sorot baris pengajuan pemohon yang berstatus Pending di urutan paling atas tabel.
  4. Perlihatkan tombol aksi tindakan persetujuan (Setujui dan Tolak).
- Teks Narasi Suara:
  "Fitur keenam dikembangkan oleh Dillan, yaitu modul Persetujuan atau Approval Permintaan.

  Halaman ini menjadi pusat eksekusi operasional bagi admin untuk memvalidasi permohonan fasilitas yang diajukan oleh pengguna. Di bagian atas, tersedia kartu ringkasan status yang memperlihatkan rasio persetujuan secara transparan.

  Sistem menerapkan algoritma pengurutan prioritas otomatis, di mana permohonan yang masih berstatus Pending akan selalu ditempatkan pada baris teratas tabel. Administrator dapat meninjau identitas pemohon, peruntukan acara, kuantitas aset yang diminta, lalu mengeksekusi persetujuan atau penolakan dengan satu kali klik."

---

### Bagian 8: Fitur Laporan dan Penutup (Closing)

- Panduan Visual Layar:
  1. Klik menu "Laporan" pada sidebar, tunjukkan ringkasan grafik rekapitulasi data.
  2. Klik tombol "Export PDF", tunjukkan proses pengunduhan berkas PDF resmi.
  3. Klik tombol "Logout" di bagian bawah sidebar untuk mengakhiri sesi dan kembali ke halaman login awal.
- Teks Narasi Suara:
  "Sebagai pelengkap operasional, sistem ini juga menyediakan modul Laporan untuk menghasilkan rekapitulasi performa sarana prasarana serta ekspor dokumen PDF resmi secara otomatis.

  Setelah seluruh aktivitas manajemen selesai, administrator dapat menekan tombol Logout pada sidebar untuk mengakhiri sesi secara aman dan kembali ke halaman login.

  Seluruh modul yang dibangun oleh Bintang, Devon, Adrian, Arzu, Aryo, dan Dillan telah terintegrasi secara harmonis pada sistem SIMASU-Web ini. Terima kasih atas perhatian Anda, sampai jumpa pada kesempatan berikutnya."
