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

## Multi-tenancy: single-database + global scope

Seluruh sekolah berbagi **satu database**. Isolasi datanya dilakukan kolom
`tenant_id` pada 35 tabel operasional ditambah global scope dari
`App\Models\Concerns\BelongsToSchool` (membungkus `BelongsToTenant` milik
`stancl/tenancy`).

Cara kerjanya:

| Konteks | Tenancy | Lingkup data |
|---|---|---|
| Central domain (`CENTRAL_DOMAINS`) | tidak diinisialisasi | seluruh platform — landing page, pendaftaran sekolah, panel Super Admin |
| Domain sekolah (`sekolah-a.platform.id`) | diinisialisasi dari domain | hanya baris milik sekolah tersebut |
| Perintah artisan, queue worker, test | tidak diinisialisasi | seluruh data (tanpa penyaringan) |

`App\Http\Middleware\InitializeTenancyUnlessCentralDomain` dipasang **di awal**
grup middleware `web` dan `api` serta pada panel Filament, karena model `User`
juga tenant-scoped: identifikasi tenant harus terjadi sebelum autentikasi
diselesaikan. Domain yang tidak terdaftar menghasilkan 404.

Konsekuensi yang perlu diketahui:

- Nilai yang hanya perlu unik di dalam satu sekolah (`username`, `nisn`,
  kode jurusan/mapel/tarif/pelanggaran, `rfid_uid`, nomor rekening tabungan)
  memakai unique komposit `(tenant_id, kolom)`. Gunakan `App\Rules\TenantUnique`
  untuk validasinya — aturan `unique` bawaan Laravel memakai query builder
  sehingga tidak terkena global scope.
- **`users.email` tetap unique global** (satu email = satu akun di seluruh
  platform). Alasannya `password_reset_tokens` berkunci alamat email tanpa
  `tenant_id`; bila satu email dipakai dua sekolah, token reset bisa saling
  tertukar.
- Menembus isolasi secara sengaja dilakukan lewat `Model::withoutTenancy()`.
  Setiap pemanggilannya adalah titik yang perlu ditinjau.
- Data instalasi lama bertenant_id `NULL` hanya terlihat dari central domain.
  Alihkan ke satu sekolah dengan:

  ```bash
  php artisan tenants:adopt-data {tenant-uuid} --dry-run
  php artisan tenants:adopt-data {tenant-uuid}
  ```

Isolasi ini diuji di `tests/Feature/TenantDataIsolationTest.php`.

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

## Verifikasi email

`App\Models\User` mengimplementasikan `Illuminate\Contracts\Auth\MustVerifyEmail`,
sehingga middleware `verified` benar-benar berlaku.

| Jalur pembuatan akun | Status awal |
|---|---|
| Pendaftaran mandiri `/register` dan `POST /api/v1/register` | **belum terverifikasi** — tautan verifikasi dikirim lewat email |
| Siswa & guru yang diinput petugas sekolah | pra-verifikasi (banyak siswa tidak memegang email yang didaftarkan sekolah) |
| Admin sekolah hasil pendaftaran sekolah | pra-verifikasi (lihat catatan di bagian akhir) |
| Akun seeder pengembangan & `app:create-admin` | pra-verifikasi |

Rute yang menuntut verifikasi: `/dashboard` dan seluruh endpoint API terproteksi
(`auth:sanctum` + `verified`). Diuji di
`tests/Feature/EmailVerificationEnforcementTest.php`.

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

## Penyimpanan berkas sensitif

Bukti transfer bank disimpan di disk **`secure`** (`storage/app/secure`), bukan di
`public` maupun `local`. Alasannya: disk `local` bawaan Laravel memakai
`'serve' => true` yang mendaftarkan rute publik `GET /storage/{path}` dan menyajikan
seluruh isi disk tanpa autentikasi. Opsi itu kini dimatikan, dan berkas sensitif
hanya dapat diakses lewat `GET /finance/proof/{hashId}` yang memeriksa peran
(`admin`/`kepala_sekolah`/`bendahara`) atau kepemilikan tagihan.

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
- **Pengiriman email belum dikonfigurasi** (`MAIL_MAILER=log`). Verifikasi email
  sudah ditegakkan, jadi atur mailer sungguhan sebelum produksi — tanpa itu
  pendaftar mandiri tidak akan menerima tautan verifikasinya.
- **Admin sekolah hasil pendaftaran sengaja dibuat pra-verifikasi** karena
  password awalnya dikirim lewat respons pendaftaran, bukan email. Setelah mailer
  produksi aktif, hapus pengisian `email_verified_at` di `App\Models\Tenant::booted()`
  agar mereka juga wajib memverifikasi alamat email.
