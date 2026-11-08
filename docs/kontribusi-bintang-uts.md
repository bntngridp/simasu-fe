# Implementasi kontribusi Bintang untuk UTS DevOps SIMASU

Tanggal verifikasi: 4 November 2026, Asia/Jakarta.

Kontributor: Bintang Ridwan Pribadi. Branch lokal kedua repository: `devops/uts-ci`. Dokumen ini mencatat implementasi dan hasil pemeriksaan lokal. Status publikasi dapat diperiksa pada riwayat branch `devops/uts-ci`; hasil CI remote dapat diperiksa pada tab Actions GitHub.

## Ringkasan tiga perubahan

| ID | Perubahan fungsional | Perilaku yang ditambahkan | Test baru |
| --- | --- | --- | --- |
| BNT-01 | Pembatasan percobaan login | Lima kegagalan per kombinasi email dan IP selama 60 detik; percobaan berikutnya mendapat HTTP 429 dan Retry-After. Login berhasil mereset penghitung, email dinormalisasi, gangguan layanan tidak dihitung sebagai password salah. | 11 |
| BNT-02 | Sesi admin dan logout | Sesi kedaluwarsa setelah 30 menit tanpa request halaman yang dilindungi. ID sesi diperbarui saat login; logout dan sesi kedaluwarsa membersihkan seluruh sesi dan mengganti token CSRF. Halaman terbuka diarahkan ke login pada batas waktu; response admin menggunakan no-store. | 10 |
| BNT-03 | Riwayat keamanan | Halaman /keamanan khusus admin, mencatat login berhasil/gagal/dibatasi, penolakan non-admin, layanan login tidak tersedia, logout, dan sesi kedaluwarsa. Mendukung filter jenis aktivitas, email, tanggal, serta pagination 20 catatan. | 9 |

Ketiga perubahan berada pada panel Laravel dan termasuk modul autentikasi/keamanan. Backend Express menerima pengaturan host bind melalui `HOST`; konfigurasi lokal dan template memakai `127.0.0.1`, sedangkan fallback server `0.0.0.0` dipertahankan untuk kompatibilitas container Docker. Tidak ada klaim kontribusi atas kode lama anggota lain.

## Setup yang sudah dilakukan

- Dependency JavaScript kedua repository dipasang dari package-lock.json. Seluruh dependency PHP termasuk require-dev dipasang dari composer.lock; tidak ada perubahan versi lockfile.
- Database SQLite frontend dibuat dan migration lama serta tabel security_events dijalankan tanpa menghapus data. Backend berjalan dengan SQLite lokal dan seeder yang sudah ada.
- Template .env.example frontend ditambahkan dengan APP_KEY kosong dan konfigurasi lokal. Nilai rahasia .env lokal dipertahankan dan tidak disalin ke dokumen ini.
- Frontend: http://localhost:7080/login. Backend/docs: http://127.0.0.1:7082/docs/.
- Sebelum penambahan fitur, login web berhasil mencapai dashboard. Halaman inventaris, ruangan, kalender, profil, notifikasi, dan laporan merespons 200 pada sesi admin. Endpoint API profil, inventaris, ruangan, booking, event, dan pengumuman juga merespons 200.
- Petunjuk menjalankan ulang tersedia di docs/local-setup.md pada repository frontend.

## Framework dan strategi pengujian

Pengujian fitur Laravel menggunakan **PHPUnit 11.5.55**, sesuai dependency project. SQLite dalam memori dan HTTP fake mengisolasi test dari data demo maupun API sungguhan. Framework PHPUnit tidak digunakan untuk menguji implementasi internal Node.js. Backend pada tahap ini diperiksa dengan syntax check serta smoke test HTTP bersama frontend; belum ditambahkan suite Jest/Mocha.

Setiap fitur memiliki lebih dari tiga test case. Skenario mencakup alur berhasil, input tidak valid, batas waktu, pergantian email/IP, kegagalan API, otorisasi, escaping HTML, dan pagination. Pengujian batas waktu memakai waktu virtual pada PHPUnit, sehingga tidak perlu menunggu 30 menit.

## Hasil terverifikasi

| Pemeriksaan | Hasil |
| --- | --- |
| Seluruh test PHP, lama dan baru | 69 lulus; 426 assertion; 0 failure; 0 error; 0 skipped |
| Test baru untuk tiga kontribusi | 30 lulus; 295 assertion |
| Coverage baris seluruh folder app | 65.31% (676/1035 baris executable) |
| Coverage komponen autentikasi yang diubah/ditambah | AuthController, SecurityEventController, CekLoginApi, AdminSession, dan SecurityAudit masing-masing 100% baris menurut laporan PCOV |
| Ambang coverage yang diuji | 51%; lolos |
| Build Vite | Lulus |
| Syntax check backend | Lulus |
| Composer validate | Valid |
| Laravel Pint pada file PHP kontribusi | Lulus |
| Smoke test HTTP aplikasi lokal | 9 pemeriksaan lulus, termasuk percobaan keenam 429, akun lain tetap dapat login, filter lima login gagal, dan akses ditolak setelah logout |
| Pemeriksaan browser | Login admin mencapai dashboard; halaman Riwayat Keamanan menampilkan catatan login dan aktivitas pengujian lokal |

Coverage di atas adalah coverage PHP, bukan coverage JavaScript browser atau Express. PCOV 1.0.12 dibangun dan diaktifkan khusus proses pengujian melalui konfigurasi di workspace; konfigurasi PHP global tidak diubah. Runner sementara sempat dipakai saat unduhan alat pengembangan belum selesai, kemudian hasil final dihasilkan ulang menggunakan dependency project lengkap.

## Menjalankan test

```bash
composer test
composer test:security
npm run build
```

Dengan Xdebug coverage atau PCOV aktif:

```bash
php artisan test --coverage --min=51 --coverage-clover=coverage.xml --log-junit=test-results.xml
```

Workflow CI sekarang juga menerima push ke branch devops/** dan menjalankan seluruh test dengan batas coverage 51 persen. Clover dan JUnit diunggah sebagai artifact. Hasil pada tabel verifikasi berasal dari pemeriksaan lokal. Status workflow remote harus diperiksa pada run Actions yang merujuk commit branch terkait.

## Pemetaan file

- BNT-01: app/Http/Controllers/AuthController.php, config/security.php, resources/views/auth/login.blade.php, tests/Feature/LoginThrottlingTest.php.
- BNT-02: app/Support/AdminSession.php, app/Http/Middleware/CekLoginApi.php, AuthController.php, resources/views/layouts/app.blade.php, tests/Feature/AdminSessionTest.php.
- BNT-03: app/Models/SecurityEvent.php, app/Support/SecurityAudit.php, app/Http/Controllers/SecurityEventController.php, database/migrations/2026_11_04_000001_create_security_events_table.php, resources/views/keamanan/index.blade.php, routes/web.php, sidebar dan navigasi cepat, tests/Feature/SecurityAuditTest.php.
- Infrastruktur test bersama: tests/AuthSecurityTestCase.php, composer.json, .github/workflows/ci.yml.

## Matriks test baru

Nama metode berikut diambil dari hasil JUnit aktual. Semua credential yang dipakai unit test merupakan nilai contoh khusus pengujian.

| ID | Metode test | Status | Assertion |
| --- | --- | --- | --- |
| BNT-01-01 | `test_sixth_failed_attempt_is_blocked_without_calling_the_api` | Lulus | 26 |
| BNT-01-02 | `test_login_is_allowed_after_the_lockout_window_expires` | Lulus | 25 |
| BNT-01-03 | `test_successful_login_resets_the_failure_counter` | Lulus | 27 |
| BNT-01-04 | `test_email_case_does_not_bypass_the_limit` | Lulus | 24 |
| BNT-01-05 | `test_other_accounts_have_independent_attempt_counters` | Lulus | 25 |
| BNT-01-06 | `test_other_client_ips_have_independent_attempt_counters` | Lulus | 25 |
| BNT-01-07 | `test_api_failure_does_not_consume_bad_password_attempts` | Lulus | 27 |
| BNT-01-08 | `test_connection_timeout_shows_a_safe_error_without_filling_the_password` | Lulus | 7 |
| BNT-01-09 | `test_invalid_form_data_never_calls_the_api` | Lulus | 6 |
| BNT-01-10 | `test_non_admin_login_never_keeps_the_api_token` | Lulus | 7 |
| BNT-01-11 | `test_malformed_success_response_does_not_create_an_authenticated_session` | Lulus | 6 |
| BNT-02-01 | `test_login_regenerates_the_session_and_sets_the_activity_timestamp` | Lulus | 6 |
| BNT-02-02 | `test_session_expires_at_the_exact_idle_limit` | Lulus | 12 |
| BNT-02-03 | `test_active_request_before_the_limit_refreshes_activity` | Lulus | 5 |
| BNT-02-04 | `test_idle_limit_is_configurable` | Lulus | 3 |
| BNT-02-05 | `test_existing_session_without_a_timestamp_is_initialized` | Lulus | 2 |
| BNT-02-06 | `test_guest_cannot_access_protected_pages` | Lulus | 3 |
| BNT-02-07 | `test_logout_clears_all_session_data_and_rotates_csrf` | Lulus | 13 |
| BNT-02-08 | `test_login_page_expires_an_idle_session_without_a_redirect_loop` | Lulus | 6 |
| BNT-02-09 | `test_authenticated_login_page_returns_to_dashboard` | Lulus | 2 |
| BNT-02-10 | `test_guest_logout_is_safe_and_does_not_fabricate_an_audit_event` | Lulus | 3 |
| BNT-03-01 | `test_successful_login_records_identity_without_password_or_token` | Lulus | 8 |
| BNT-03-02 | `test_failed_login_and_lockout_are_persisted_as_separate_events` | Lulus | 5 |
| BNT-03-03 | `test_admin_can_view_the_audit_page_and_empty_state` | Lulus | 4 |
| BNT-03-04 | `test_non_admin_and_missing_role_cannot_read_the_audit_log` | Lulus | 2 |
| BNT-03-05 | `test_event_and_email_filters_are_combined` | Lulus | 2 |
| BNT-03-06 | `test_date_filter_excludes_other_days` | Lulus | 2 |
| BNT-03-07 | `test_invalid_filters_are_rejected` | Lulus | 5 |
| BNT-03-08 | `test_results_are_paginated_newest_first_and_preserve_filters` | Lulus | 4 |
| BNT-03-09 | `test_audit_values_are_escaped_in_the_page` | Lulus | 3 |

## Batas dan pekerjaan berikutnya

- Limiter melindungi formulir login panel Laravel. Endpoint /api/login Express belum memiliki limiter pada perubahan ini.
- Aktivitas sesi dihitung dari request halaman admin; mengetik tanpa request tidak memperpanjang sesi. Penghapusan sesi panel tidak mencabut JWT backend yang sudah diterbitkan.
- Audit menyimpan metadata whitelist, tanpa password, JWT, token CSRF, atau body request. Smoke test menyisakan catatan beralamat example.test sebagai data pengujian lokal; catatan itu bukan aktivitas pengguna produksi.
- Pekerjaan UTS lain seperti SonarQube, evaluasi/perbaikan dependency audit, dan penanganan file env lama yang sudah terlacak Git masih terpisah. Template baru tidak menghapus kunci dari riwayat Git.
- Gunakan commit dan PR milik Bintang sebagai bukti kontribusi. Status review dan hasil CI remote harus mengacu pada aktivitas GitHub yang benar-benar terjadi.
