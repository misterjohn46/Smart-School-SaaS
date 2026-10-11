# Laravel Starter Kit

Starter kit Laravel dengan Vue/Inertia, Filament, Sanctum, dan role-based access control.

## Stack

- PHP 8.3 dan Laravel 13
- Vue 3, Inertia 2, Tailwind CSS 3, dan Vite
- Filament 5 untuk panel admin
- Laravel Sanctum untuk bearer token API
- Spatie Laravel Permission untuk role `admin` dan `user`
- SQLite sebagai database development default

## Instalasi

```bash
composer run setup
php artisan db:seed
composer run dev
```

Aplikasi tersedia di `http://127.0.0.1:8000` dan panel admin di
`http://127.0.0.1:8000/admin`.

Kredensial development hasil seeder:

```text
Email: admin@template.com
Password: password
```

Ganti password tersebut sebelum menggunakan aplikasi di lingkungan selain lokal.
Seeder aman dijalankan ulang dan tidak mereset password akun admin yang sudah ada.
Untuk membuat admin tanpa kredensial default, jalankan `php artisan app:create-admin`.

## API

Endpoint autentikasi berada di bawah `/api/v1`:

- `POST /register`
- `POST /login`
- `POST /logout` (bearer token)
- `GET /user` (bearer token)

Login API menggunakan satu token aktif per user. Token lama dicabut saat login baru.
Masa berlaku token dikontrol melalui `SANCTUM_EXPIRATION` dalam menit dan default-nya
7 hari. Login dibatasi 5 percobaan per menit per email/IP dan registrasi dibatasi 3
percobaan per menit per IP.

## Pengembangan

```bash
composer run dev
composer test
vendor/bin/pint
vendor/bin/phpstan analyse
composer audit
npm audit
npm run build
```

Pipeline GitHub Actions menjalankan Pint, test PHP, dan production build. Salin
`.env.example` ke `.env` bila melakukan instalasi manual. Untuk production, atur
`APP_ENV=production`, `APP_DEBUG=false`, database, mail, queue, cache, dan domain
Sanctum sesuai lingkungan deployment.
