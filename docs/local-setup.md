# Menjalankan SIMASU secara lokal

Frontend menggunakan Laravel 12, PHP 8.2 atau lebih baru, Composer, Node.js 22, dan npm. Backend menggunakan Node.js, Express, Sequelize, dan SQLite. Node.js 20.19.3 pada komputer pengembangan ini juga telah berhasil membangun asset Vite.

## Backend

Dari folder `simasu-be`:

1. Jalankan `npm ci`.
2. Salin `.env.example` menjadi `.env` hanya jika `.env` belum tersedia.
3. Atur `PORT=7082` dan `HOST=127.0.0.1` pada `.env`. Isi `JWT_SECRET_KEY` dengan nilai acak untuk lingkungan lokal; jangan menyalin nilai ke dokumentasi atau Git.
4. Jalankan `npm start`.
5. Periksa `http://127.0.0.1:7082/docs/`.

Backend membuat tabel SQLite dan akun demo lokal melalui seeder saat startup. Gunakan data demo hanya untuk pengujian lokal. Frontend mengambil identitas dan token dari backend ini.

## Frontend

Dari folder `simasu-fe`:

1. Jalankan `composer install` dan `npm ci`.
2. Salin `.env.example` menjadi `.env` hanya jika `.env` belum tersedia. Pertahankan nilai lokal yang sudah benar.
3. Untuk environment baru yang `APP_KEY`-nya masih kosong, jalankan `php artisan key:generate`. Jangan mengganti key environment yang sudah berisi data terenkripsi tanpa evaluasi.
4. Pastikan file `database/database.sqlite` tersedia, lalu jalankan `php artisan migrate`.
5. Jalankan `npm run build`.
6. Jalankan `php artisan serve --host=127.0.0.1 --port=7080`.
7. Buka `http://localhost:7080/login`. Gunakan hostname `localhost` secara konsisten karena URL aplikasi dipaksa mengikuti `APP_URL`; mencampurnya dengan `127.0.0.1` dapat memisahkan cookie sesi.

Build asset sudah cukup untuk penggunaan lokal. Jika mengedit asset dengan hot reload, jalankan `npm run dev -- --host=127.0.0.1 --strictPort` pada terminal lain; Vite menggunakan port 7081.

## Pengujian

```bash
composer test
composer test:security
npm run build
```

PHPUnit 11 adalah framework pengujian untuk ketiga fitur autentikasi Laravel. Test memakai SQLite dalam memori dan HTTP fake, sehingga tidak mengubah data demo atau memanggil API sungguhan. Pengujian browser dan HTTP lokal digunakan terpisah untuk memeriksa integrasi dengan backend Express.

Untuk coverage, aktifkan Xdebug dalam mode coverage atau PCOV, lalu jalankan:

```bash
php artisan test --coverage --min=51 --coverage-clover=coverage.xml --log-junit=test-results.xml
```

GitHub Actions sudah menyiapkan Xdebug dan menjalankan seluruh test, termasuk test keamanan, dengan batas minimum coverage 51 persen untuk memenuhi target rubrik di atas 50 persen. Hasil Clover dan JUnit diunggah sebagai artifact. Perubahan workflow lokal belum dianggap sukses di GitHub sampai branch dipush dan run yang bersangkutan selesai.

## Konfigurasi fitur keamanan

| Variabel | Default | Fungsi |
| --- | --- | --- |
| `LOGIN_MAX_ATTEMPTS` | 5 | Jumlah kegagalan login yang diizinkan per kombinasi email dan alamat IP |
| `LOGIN_DECAY_SECONDS` | 60 | Durasi jendela percobaan dan waktu tunggu setelah batas tercapai |
| `ADMIN_IDLE_TIMEOUT_MINUTES` | 30 | Batas waktu tanpa request pada halaman admin yang dilindungi |

Navigasi halaman admin memperbarui waktu aktivitas. Halaman yang tetap terbuka diarahkan ke halaman login saat batas waktunya berakhir; server memeriksa batas sesi lagi dan membersihkan sesi. Input keyboard tanpa request halaman tidak memperpanjang sesi. Logout panel menghapus sesi web dan mengganti token CSRF; mekanisme ini tidak mencabut JWT yang telah diterbitkan backend.

Halaman `/keamanan` memerlukan sesi dengan role admin. Catatan hanya menyimpan jenis aktivitas, email, ID pengguna API, alamat IP, dan waktu. Password, JWT, isi request, serta token CSRF tidak disimpan. Tanggal pada tampilan menggunakan WIB; filter tanggal mengikuti timezone aplikasi Laravel.

Pembatasan login pada perubahan ini berlaku pada formulir admin Laravel. Endpoint `/api/login` Express memerlukan pembatasan dan pengujian Node.js tersendiri bila akan ditangani pada pekerjaan berikutnya.
