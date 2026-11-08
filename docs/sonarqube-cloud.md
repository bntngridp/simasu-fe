# Analisis kualitas kode SIMASU dengan SonarQube Cloud

Kontributor: Bintang Ridwan Pribadi. Platform yang dipilih adalah SonarQube Cloud online. Server SonarQube lokal tidak diperlukan untuk menjalankan workflow ini.

Project yang sudah terdaftar: `bntngridp_simasu-fe`, organisasi `bintangridwanp`, region EU. Organisasi menggunakan key lama; akun GitHub yang terhubung dan digunakan untuk repository adalah `bntngridp`. Dashboard: https://sonarcloud.io/project/overview?id=bntngridp_simasu-fe.

## Konfigurasi

1. Masuk ke SonarQube Cloud menggunakan akun GitHub yang sesuai dengan repository `bntngridp/simasu-fe`.
2. Siapkan organisasi dan project untuk repository frontend. Catat key yang benar-benar diberikan platform; jangan menganggap key sama dengan nama tampilan.
3. Pilih analisis melalui GitHub Actions. Automatic Analysis perlu dinonaktifkan karena laporan coverage PHP dihasilkan oleh PHPUnit dalam CI.
4. Simpan token analisis pada GitHub repository secret `SONAR_TOKEN`. Jangan menaruh token pada source code, property file, command yang dicatat, maupun laporan.
5. Atur repository variables `SONAR_ORGANIZATION` dan `SONAR_PROJECT_KEY` sesuai project Cloud. `SONAR_HOST_URL` opsional dan secara default memakai `https://sonarcloud.io` untuk region EU; sesuaikan jika project menggunakan region lain.
6. Jalankan analisis awal branch main sebelum menggunakan hasil analisis PR untuk membandingkan kode baru. Sumber baseline harus berasal dari commit main yang sebenarnya, bukan salinan branch kontribusi yang diberi label main.

## Alur CI

Job build memasang dependency, membangun asset Vite, dan menjalankan PHPUnit dengan minimum coverage 51 persen. Artifact `phpunit-coverage` berisi `coverage.xml` berformat Clover dan `test-results.xml` berformat JUnit.

Job `SonarQube Cloud Analysis` mengunduh artifact coverage setelah build berhasil. Scanner membaca cakupan sumber dan test dari `sonar-project.properties`, lalu mengirim analisis ke project Cloud yang dipilih. Parameter `sonar.php.coverage.reportPaths=coverage.xml` mengimpor coverage PHP; SonarQube tidak menjalankan PHPUnit untuk menghasilkan coverage itu sendiri.

Job Cloud berjalan pada main/master dan PR internal menuju branch utama. Push ke branch devops menjalankan build/test, sedangkan analisis Cloud untuk perubahan tersebut berjalan melalui event PR. Pola ini menghindari analisis branch tambahan yang tidak tersedia pada Free plan.

Jika key project atau organisasi belum dikonfigurasi, job Cloud berstatus skipped secara eksplisit. Jika project sudah diaktifkan tetapi token tidak tersedia, job gagal dengan pesan konfigurasi. Scanner menunggu quality gate dan mengembalikan kegagalan jika gate tidak terpenuhi; tidak ada `|| true` pada langkah scanner.

## Bukti dan batas laporan

Analisis baru dinyatakan selesai setelah ada hasil scanner, status pemrosesan berhasil, tautan project/PR, dan hasil quality gate yang dapat diperiksa. Hasil test PHP atau status job build hijau bukan pengganti bukti analisis kualitas kode.

Laporan akhir perlu memuat commit dan branch yang dianalisis, waktu analisis, cakupan kode, bugs/reliability, vulnerabilities/security, code smells/maintainability, security hotspots, duplication, coverage, temuan pada tiga kontribusi Bintang, serta perbaikan dan hasil scan ulang. Pisahkan masalah lama project dari temuan pada perubahan baru. Jangan menyatakan hotspot aman tanpa meninjau alur kode yang dirujuk.

Coverage PHP tidak mengukur functional test Express atau JavaScript browser. SonarQube Cloud juga tidak menggantikan `composer audit` atau `npm audit`. Workflow audit dependency lama masih perlu diperbaiki terpisah.

## Referensi resmi

- [GitHub Actions untuk SonarQube Cloud](https://docs.sonarsource.com/sonarqube-cloud/analyzing-source-code/ci-based-analysis/github-actions-for-sonarcloud)
- [Coverage PHP](https://docs.sonarsource.com/sonarqube-cloud/analyzing-source-code/test-coverage/php-test-coverage)
- [Analisis branch](https://docs.sonarsource.com/sonarqube-cloud/analyzing-source-code/branch-analysis/branch-analysis)
- [Analisis pull request](https://docs.sonarsource.com/sonarqube-cloud/analyzing-source-code/pull-request-analysis)
