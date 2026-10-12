# Sistem Informasi Sekolah (SaaS)

Platform SaaS sekolah: SIAKAD, presensi cerdas (RFID/GPS/Face), keuangan & SPP,
tabungan siswa (e-wallet), e-kantin, CBT, dan bimbingan konseling.

## Stack sebenarnya

- PHP 8.3, Laravel 13
- **Inertia 2 + Vue 3 + Tailwind CSS 3 + Vite** untuk portal pengguna
  (bukan Livewire/Alpine — `ARCHITECTURE.md` versi awal menyebut TALL stack,
  implementasinya Inertia + Vue)
- Filament 5 untuk panel admin `/admin` (Filament sendiri memakai Livewire)
- `stancl/tenancy` 3.10 untuk multi-tenancy — **lihat status di bawah**
- Laravel Sanctum untuk API bearer token
- `spatie/laravel-permission` untuk 16 role
- SQLite sebagai database development default; MySQL 8 / MariaDB untuk produksi

## Status multi-tenancy (penting)

`stancl/tenancy` sudah terpasang dan dikonfigurasi, namun **belum menegakkan
isolasi data antar sekolah**. Seluruh migrasi operasional masih berada di database
central dan rute aplikasi belum memakai `InitializeTenancyByDomain`, sehingga satu
instalasi saat ini praktis melayani satu sekolah.

Langkah yang tersisa beserta dampaknya terdokumentasi di
[`database/migrations/tenant/README.md`](database/migrations/tenant/README.md).
Jangan deploy sebagai SaaS multi-sekolah sebelum langkah tersebut selesai.

## Instalasi

```bash
composer run setup
php artisan db:seed-central
composer run dev
```

Aplikasi tersedia di `http://127.0.0.1:8000`, panel admin di `http://127.0.0.1:8000/admin`.

Kredensial development hasil seeder (`php artisan db:seed-central`):

| Peran | Email | Password |
|---|---|---|
| Super Admin SaaS | `superadmin@platform.id` | `password` |
| Admin Sekolah (TU) | `admin@sekolah.id` | `password` |
| Kepala Sekolah | `kepsek@sekolah.id` | `password` |
| Guru | `guru@sekolah.id` | `password` |
| Guru BK | `gurubk@sekolah.id` | `password` |
| Bendahara | `bendahara@sekolah.id` | `password` |
| Siswa | `siswa@sekolah.id` | `password` |
| Orang Tua | `ortu@sekolah.id` | `password` |

Kredensial di atas **hanya untuk pengembangan lokal**. Ganti sebelum dipakai di
lingkungan lain. Untuk membuat admin tanpa kredensial default:
`php artisan app:create-admin`.

Sekolah baru yang mendaftar lewat `POST /register-school` mendapat password admin
acak yang dikembalikan sekali pada respons registrasi (`data.initial_password`).

## Matriks peran per modul

Seluruh rute di `routes/web.php` dijaga middleware `role:` (lihat komentar di awal
berkas tersebut). Ringkasnya:

| Modul | Peran yang diizinkan |
|---|---|
| Master data akademik, jadwal, KTS massal, pengaturan presensi | `admin`, `kepala_sekolah`, `operator` |
| Nilai, rapor, presensi kelas, CBT | ditambah `guru` |
| Jurnal mengajar | `admin`, `kepala_sekolah`, `guru` |
| Verifikasi jurnal, tindak lanjut pelanggaran, kategori BK | `admin`, `kepala_sekolah`, `guru_bk` |
| Input pelanggaran siswa | ditambah `guru` |
| Keuangan, tagihan, kuitansi, pos tarif | `admin`, `kepala_sekolah`, `bendahara` |
| Setor / tarik saldo tabungan siswa | `admin`, `bendahara` |
| POS stand kantin & status pesanan | `admin`, `kantin`, `vendor_kantin`, `kasir_kantin` |
| Portal siswa/orang tua, presensi mandiri, saldo & PIN sendiri, pesan kantin, preview KTS sendiri | semua pengguna terautentikasi (data dibatasi ke miliknya) |

Panel Filament memisahkan resource lewat trait di `app/Filament/Concerns`:
`HasSuperAdminAccessOnly` (tenant, paket, node CBT), `HasSchoolAccessOnly`
(operasional sekolah), dan `HasFinanceAccessOnly` (tagihan, pembayaran, dompet).

## API

Endpoint autentikasi di bawah `/api/v1`:

- `POST /register` (throttle 3/menit per IP)
- `POST /login` (throttle 5/menit)
- `POST /logout` (bearer token)
- `GET /user` (bearer token)

Login API memakai satu token aktif per user; token lama dicabut saat login baru.
Masa berlaku token diatur `SANCTUM_EXPIRATION` (menit, default 7 hari).

### Endpoint IoT presensi RFID

- `POST /api/v1/attendance/tap-rfid`
- `GET /api/v1/attendance/ping`

Keduanya:

- menerima token **hanya** lewat header `X-Device-Token` (tidak lewat query/body,
  agar token tidak bocor ke access log),
- dibatasi `throttle:iot-device` (60 permintaan/menit per token + IP),
- token perangkat disimpan sebagai hash SHA-256; plaintext hanya tampil sekali saat
  perangkat dibuat atau token diterbitkan ulang dari panel,
- tap kartu yang sama ditolak dalam rentang 3 menit (anti-double-tap).

## Pengembangan

```bash
composer run dev
php artisan test
vendor/bin/pint
vendor/bin/phpstan analyse --memory-limit=2G
composer audit
npm audit --omit=dev --audit-level=high
npm run build
```

PHPStan memakai `phpstan-baseline.neon` berisi error yang sudah ada sebelum static
analysis ditegakkan (mayoritas anotasi `@property` model yang belum ditulis).
Error **baru** akan menggagalkan CI. Jangan regenerate baseline untuk menutup error
baru — perbaiki errornya.

PHPStan membutuhkan `--memory-limit=2G`; dengan `memory_limit` bawaan PHP (128M)
prosesnya crash, bukan sekadar melaporkan error.

Pipeline GitHub Actions menjalankan Pint, audit dependensi, PHPStan, test, dan
production build. Untuk produksi: `APP_ENV=production`, `APP_DEBUG=false`,
`SESSION_ENCRYPT=true`, `LOG_LEVEL=warning`, serta atur `CENTRAL_DOMAINS`,
database, mail, queue, cache, dan domain Sanctum sesuai lingkungan.

## Catatan keamanan yang masih terbuka

- **Isolasi tenant** belum aktif (lihat bagian status di atas).
- **Tailwind CSS masih v3**; rantai dependensi build-nya (`braces`, `micromatch`,
  `chokidar`, `fast-glob`, `postcss-selector-parser`) membawa advisory DoS yang baru
  bersih setelah migrasi ke Tailwind v4. Kerentanan ini hanya berdampak pada mesin
  build, bukan runtime produksi.
- **Hash ID anti-IDOR** baru efektif pada modul KTS dan kuitansi pembayaran. Modul
  CBT dan geofence masih memakai ID integer pada URL; kini tertutup oleh middleware
  peran, bukan oleh obfuscation.
- **Login orang tua** memakai NISN + tanggal lahir (entropi rendah). ROADMAP
  merencanakan OTP WhatsApp sebagai faktor kedua.
- **Bukti transfer** (`FinanceController::uploadProof`) masih disimpan di disk
  `public` sehingga dapat diakses tanpa autentikasi bila URL-nya diketahui.
