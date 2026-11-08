# Dokumentasi CI DevSecOps SIMASU

## Continuous Integration

Workflow GitHub Actions berada di `.github/workflows/ci.yml`. Build/test berjalan pada push ke main/master/devops/**, PR menuju main/master, serta manual melalui `workflow_dispatch`.

Tahapan workflow:

1. `build-test`
   - Checkout repository.
   - Setup PHP 8.2.
   - Setup Node.js 22.
   - Install dependency PHP dengan Composer.
   - Menyiapkan environment Laravel testing.
   - Install dependency frontend dengan `npm ci`.
   - Build asset frontend dengan `npm run build`.
   - Menjalankan PHPUnit dengan minimum coverage 51 persen.
   - Upload artifact `phpunit-coverage` berisi Clover `coverage.xml` dan JUnit `test-results.xml`.

2. `dependency-check`
   - Menjalankan `composer audit` untuk dependency PHP.
   - Menjalankan `npm audit --audit-level=high` untuk dependency Node.js.

3. `sonar-analysis`
   - Mengambil artifact coverage.
   - Mengirim analisis ke SonarQube Cloud dan menunggu hasil quality gate.
   - Menggunakan secret `SONAR_TOKEN` serta variables `SONAR_ORGANIZATION` dan `SONAR_PROJECT_KEY`; host EU default `https://sonarcloud.io`.
   - Berjalan pada main/master dan PR internal ke branch utama setelah project dikonfigurasi. Jika key belum tersedia, job skipped; jika key sudah tersedia tetapi token kosong, job gagal.
   - Petunjuk setup dan batas analisis dijelaskan pada `docs/sonarqube-cloud.md`.

## Secret Management

Token analisis Cloud disimpan melalui GitHub Secrets:

- `SONAR_TOKEN`: token akses SonarQube Cloud.
- `SONAR_HOST_URL`: URL Cloud merupakan repository variable non-rahasia, bukan secret. Key organisasi dan project juga disimpan sebagai variables.
- Credential API, database, atau token lain wajib masuk ke `.env` lokal atau GitHub Secrets.

Kode lama masih memerlukan penanganan terpisah, termasuk file `env` yang sudah terlacak Git. Penyiapan token Cloud tidak menyatakan seluruh secret management project sudah selesai.

## Dependency Check

Dependency check otomatis dilakukan di pipeline:

- PHP: `composer audit`.
- Node.js: `npm audit --audit-level=high`.

Implementasi audit saat ini masih memakai `|| true`, sehingga temuan vulnerability belum menggagalkan job. Temuan perlu dianalisis dan diperbaiki, lalu kebijakan kegagalan audit diterapkan. Keterbatasan ini terpisah dari quality gate SonarQube Cloud yang tidak mengabaikan exit code scanner.

## Branch Protection dan Code Review

Branch utama `main` sudah dikonfigurasi dengan PR wajib, minimal satu approval, required checks, penyelesaian percakapan, dan enforce admins. Required checks FE yang diverifikasi adalah `Build and Test Laravel` serta `Dependency Check`. Ketentuan review:

- Require pull request before merging.
- Require at least 1 approval.
- Require status checks to pass before merging.
- Required checks saat ini: `Build and Test Laravel` dan `Dependency Check`. Penambahan check Cloud sebagai required check perlu mengikuti nama check aktual setelah integrasi berhasil.
- Disable direct push ke branch `main` untuk anggota biasa.

## STRIDE Singkat

| Kategori | Ancaman Pada SIMASU | Risiko | Mitigasi |
|---|---|---|---|
| Spoofing | User palsu mencoba login admin | High | Validasi token API, session Laravel, role admin |
| Tampering | Data inventaris/ruangan dimodifikasi lewat request palsu | Medium | Validasi request, token bearer, method HTTP sesuai aksi |
| Repudiation | Admin menyangkal perubahan data | Medium | Tambahkan audit log pada perubahan penting |
| Information Disclosure | API token atau credential bocor | High | GitHub Secrets, `.env`, jangan commit credential |
| Denial of Service | Request berulang ke endpoint booking/inventaris | Medium | Timeout HTTP client, rate limit di backend API |
| Elevation of Privilege | User non-admin mengakses halaman admin | High | Middleware login, cek role admin saat login, least privilege |
