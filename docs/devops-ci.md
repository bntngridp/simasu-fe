# Dokumentasi CI DevSecOps SIMASU

## Continuous Integration

Workflow GitHub Actions berada di `.github/workflows/ci.yml` dan berjalan otomatis pada `push`, `pull_request`, serta manual melalui `workflow_dispatch`.

Tahapan workflow:

1. `build-test`
   - Checkout repository.
   - Setup PHP 8.2.
   - Setup Node.js 22.
   - Install dependency PHP dengan Composer.
   - Menyiapkan environment Laravel testing.
   - Install dependency frontend dengan `npm ci`.
   - Build asset frontend dengan `npm run build`.
   - Menjalankan PHPUnit dan menghasilkan file coverage untuk SonarQube.
   - Upload file `coverage.xml` untuk analisis SonarQube.

2. `dependency-check`
   - Menjalankan `composer audit` untuk dependency PHP.
   - Menjalankan `npm audit --audit-level=high` untuk dependency Node.js.

3. `sonar-analysis`
   - Mengambil artifact coverage.
   - Menjalankan SonarQube scanner jika secret `SONAR_TOKEN` dan `SONAR_HOST_URL` sudah diset.

## Secret Management

Credential tidak ditulis langsung di kode. Konfigurasi sensitif disimpan melalui GitHub Secrets:

- `SONAR_TOKEN`: token akses SonarQube.
- `SONAR_HOST_URL`: URL server SonarQube.
- Credential API, database, atau token lain wajib masuk ke `.env` lokal atau GitHub Secrets.

## Dependency Check

Dependency check otomatis dilakukan di pipeline:

- PHP: `composer audit`.
- Node.js: `npm audit --audit-level=high`.

Jika ada vulnerability, pipeline akan gagal dan dependency perlu diperbarui sebelum PR digabung.

## Branch Protection dan Code Review

Branch utama `main` disarankan memakai rules berikut:

- Require pull request before merging.
- Require at least 1 approval.
- Require status checks to pass before merging.
- Pilih status check `Build and Test Laravel`, `Dependency Check`, dan `SonarQube Analysis`.
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
