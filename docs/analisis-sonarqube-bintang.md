# Analisis kualitas kode kontribusi Bintang

Tanggal pekerjaan: 8 November 2026, WIB. Kontributor: Bintang Ridwan Pribadi, GitHub `bntngridp`.

Hasil pemindaian ulang kode baru pada PR: quality gate Passed, 0 bugs baru, 0 vulnerabilities baru, 0 code smells baru, coverage kode baru 100 persen, dan duplikasi kode baru 0 persen. Temuan kode lama tetap dicatat terpisah di bawah.

## Platform dan cakupan

Analisis menggunakan SonarQube Cloud online, project `bntngridp_simasu-fe` pada organisasi `bintangridwanp`, region EU. Key organisasi merupakan key lama akun yang sudah terhubung ke GitHub bntngridp.

- [Ringkasan analisis PR #1](https://sonarcloud.io/summary/new_code?id=bntngridp_simasu-fe&pullRequest=1)
- [Pull Request kontribusi](https://github.com/bntngridp/simasu-fe/pull/1)
- [CI analisis pertama yang berhasil](https://github.com/bntngridp/simasu-fe/actions/runs/42471849377)

Analisis sumber mencakup `app`, `config`, `routes`, `resources`, dan `database/migrations`. Test berada pada `tests`. Pengukuran coverage dibatasi pada folder `app`, sesuai sumber yang diukur PHPUnit. Folder lain tetap diperiksa kualitas kodenya, termasuk migration baru untuk tabel riwayat keamanan.

Tiga kontribusi yang dievaluasi adalah BNT-01 pembatasan login, BNT-02 sesi admin dan logout, serta BNT-03 riwayat keamanan. Analisis ini tidak mencakup kode Express pada repository backend.

## Metode dan baseline

Baseline menggunakan isi commit main `8441da538fe8c3ce6524384b1efac09c54125193`, diekspor secara terpisah tanpa mengubah branch main. Scanner mencatat revision tersebut. Pada ekspor baseline, publikasi informasi SCM dinonaktifkan karena ekspor tidak memuat metadata Git. Analisis PR di GitHub Actions menggunakan checkout dengan seluruh riwayat Git.

Baseline pertama berhasil diproses oleh Cloud. Baseline berikutnya menggunakan cakupan migration yang konsisten dan coverage PHPUnit asli untuk kode main. Label versi analisis baseline adalah `baseline-8441da5`. Definisi New Code project memakai Previous version. PR dibandingkan terhadap main agar masalah lama dan temuan pada kontribusi dapat dipisahkan.

Dependency baseline dipasang dari lockfile yang sama dengan project. Pengujian baseline memakai SQLite dalam memori dan HTTP fake; layanan backend produksi tidak digunakan oleh suite test. Token Cloud dikirim melalui environment scanner. Token CI disimpan sebagai GitHub Secret `SONAR_TOKEN`; nilai token tidak ditulis ke repository, laporan, maupun artifact.

Automatic Analysis dinonaktifkan. GitHub Actions menjalankan PHPUnit lebih dahulu, mengunggah laporan Clover dan JUnit, kemudian scanner Cloud mengimpor coverage melalui `sonar.php.coverage.reportPaths=coverage.xml`. Scanner menunggu quality gate dengan `sonar.qualitygate.wait=true`; kegagalan gate tidak diabaikan.

## Hasil baseline kode lama

| Ukuran | Hasil |
| --- | ---: |
| Bugs | 29 |
| Vulnerabilities | 5 |
| Code smells | 130 |
| Duplicated lines | 0,9% |
| Coverage yang ditampilkan SonarQube untuk baseline | 59,4% |
| Test baseline | 39 |
| Assertion baseline | 131 |
| Coverage PHPUnit/PCOV baseline | 59,48% (571/960 statement terukur) |

Temuan bugs baseline terdiri dari 27 temuan `Web:InputWithoutLabelCheck`, satu `php:S1763`, dan satu `Web:S5254`. Temuan vulnerabilities baseline berjumlah lima dan berasal dari `Web:S5725`, yaitu pemuatan asset eksternal tanpa atribut integrity/crossorigin yang diminta rule. Lokasinya mencakup layout aplikasi serta halaman inventaris dan ruangan. Temuan tersebut sudah ada pada main sebelum kontribusi Bintang.

Tindak lanjut kode lama: periksa dan perbaiki label input, alur kode yang tidak terjangkau, deklarasi bahasa dokumen, serta integritas asset eksternal. Untuk asset eksternal, evaluasi penggunaan asset yang dikelola melalui build project atau versi yang dipatok beserta SRI yang benar. Pekerjaan ini belum dinyatakan selesai oleh analisis kontribusi Bintang.

## Hasil PR sebelum perbaikan

Analisis commit `577925de0df677df40348a3ef4b48e140f7facdc` selesai pada 8 November 2026 pukul 17:33:10 WIB. CI dan job SonarQube Cloud Analysis berhasil.

| Ukuran pada kode baru di PR | Hasil |
| --- | ---: |
| Quality gate | Passed / OK |
| Bugs baru | 0 |
| Vulnerabilities baru | 0 |
| Code smells baru | 1 |
| Coverage kode baru | 100% |
| Baris baru yang perlu dicakup | 109 |
| Baris baru yang belum dicakup | 0 |
| Duplikasi kode baru | 0,0% |

Quality gate Sonar way mensyaratkan coverage kode baru minimal 80 persen dan duplikasi maksimal 3 persen. Gate juga memeriksa rating reliability, security, maintainability, dan review security hotspots. Status Passed pada analisis awal tidak menghapus satu code smell yang masih ditemukan.

## Temuan dan perbaikan

| Rule | Lokasi | Penyebab | Perbaikan |
| --- | --- | --- | --- |
| php:S1192 | `app/Http/Controllers/AuthController.php`, awalnya baris 31 | Literal header `no-store, private` digunakan tiga kali | Tambahkan konstanta private `NO_STORE_CACHE_CONTROL` dan gunakan pada ketiga response |

API SonarQube mengklasifikasikan temuan ini sebagai CODE_SMELL dengan severity legacy CRITICAL. Temuan tersebut menyangkut maintainability, bukan laporan vulnerability baru. Perbaikan mengurangi pengulangan kebijakan cache tanpa mengubah nilai header atau perilaku login/logout.

Perbaikan berada pada commit `ff1487546ca12f901a0489077930d0bd6fa79302`. Setelah perbaikan, pengujian lokal menghasilkan 69 test, 426 assertion, 0 failure, 0 error, dan 0 skipped. Coverage PHP adalah 65,12% (674/1035 statement terukur), dan Laravel Pint pada controller lulus. Tidak ada perubahan versi dependency.

Tiga fitur Bintang menyumbang 30 test baru dan 295 assertion: pembatasan login 11 test, sesi admin 10 test, dan riwayat keamanan 9 test. Sisanya merupakan 39 test dan 131 assertion yang sudah ada pada baseline. Jumlah test tidak sama dengan jumlah fitur.

## Hasil pemindaian ulang setelah perbaikan

Analisis commit `ff1487546ca12f901a0489077930d0bd6fa79302` selesai pada 8 November 2026 pukul 17:40:06 WIB. [CI pemindaian ulang](https://github.com/bntngridp/simasu-fe/actions/runs/42472096930) berhasil, termasuk build, PHPUnit, dan scanner Cloud. Scanner benar-benar berjalan dan tidak berstatus skipped.

| Ukuran pada kode baru di PR | Sebelum perbaikan | Setelah perbaikan |
| --- | ---: | ---: |
| Quality gate | Passed | Passed |
| Bugs baru | 0 | 0 |
| Vulnerabilities baru | 0 | 0 |
| Code smells baru | 1 | 0 |
| Coverage kode baru | 100% | 100% |
| Baris baru yang perlu dicakup | 109 | 109 |
| Baris baru yang belum dicakup | 0 | 0 |
| Duplikasi kode baru | 0,0% | 0,0% |

API issues untuk PR mengembalikan 0 issue terbuka setelah perbaikan. Rule tidak dinonaktifkan, temuan tidak ditandai Accepted atau False positive, dan ambang quality gate tidak diturunkan. Perbaikan terjadi melalui perubahan kode controller.

Check `SonarCloud Code Analysis` dari aplikasi SonarQube Cloud ditambahkan sebagai required check pada main, bersama Build and Test Laravel dan Dependency Check. Persyaratan satu approval, strict checks, dan penyelesaian percakapan review tetap berlaku.

## Batas kesimpulan

Coverage 100 persen di SonarQube PR berlaku pada kode baru yang diukur, sedangkan 65,12 persen adalah coverage PHP seluruh folder app dari PHPUnit. Keduanya tidak mengukur functional test Express maupun JavaScript browser.

Hasil bersih pada PR tidak menyatakan seluruh kode lama bebas masalah. Lima vulnerabilities dan temuan lain pada baseline tetap perlu ditangani. SonarQube Cloud tidak menggantikan npm audit atau composer audit; perintah audit lama di CI masih mengabaikan kegagalan melalui `|| true` dan membutuhkan pekerjaan terpisah.

Review minimal satu anggota kelompok tetap diperlukan sebelum merge. Bukti CI dan analisis otomatis tidak menggantikan approval manusia. Hasil di atas berasal dari respons Cloud dan run CI yang merujuk commit perbaikan; commit dokumentasi berikutnya tidak mengubah implementasi fitur.

## Referensi

- [Konfigurasi GitHub Actions resmi SonarQube Cloud](https://docs.sonarsource.com/sonarqube-cloud/analyzing-source-code/ci-based-analysis/github-actions-for-sonarcloud)
- [Impor coverage PHP resmi](https://docs.sonarsource.com/sonarqube-cloud/analyzing-source-code/test-coverage/php-test-coverage)
- [Cara analisis PR membandingkan kode dengan target branch](https://docs.sonarsource.com/sonarqube-cloud/analyzing-source-code/pull-request-analysis)
