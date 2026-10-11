# Migrasi Database Tenant

`config/tenancy.php` mengarahkan `migration_parameters['--path']` ke direktori ini.
Setiap database tenant baru (`tenant<uuid>`) dimigrasi **hanya** dengan berkas di sini.

## Status saat ini: KOSONG — disengaja, menunggu keputusan arsitektur

Seluruh 13 migrasi yang secara logis milik tenant masih berada di `database/migrations`
(database central), yaitu:

```
2026_09_13_000002_create_academic_and_security_tables.php
2026_09_13_000003_create_attendance_and_kts_tables.php
2026_09_13_000004_create_finance_wallet_canteen_tables.php
2026_09_13_000005_create_lesson_schedules_table.php
2026_09_13_000006_create_lesson_attendances_table.php
2026_09_13_000007_create_student_grades_table.php
2026_09_13_000008_create_teaching_journals_table.php
2026_09_13_000009_create_counseling_and_violations_tables.php
2026_09_13_000010_add_working_hours_to_school_geofences_table.php
2026_09_13_000011_create_cbt_tables.php
2026_09_13_000012_create_report_cards_table.php
2026_09_13_000013_hash_attendance_device_tokens.php
2026_07_11_003547_create_permission_tables.php
```

Akibatnya database tenant yang terbentuk hanya berisi tabel `migrations`, dan seluruh
data sekolah berada di satu database central tanpa isolasi antar sekolah.

## Yang tersisa untuk mengaktifkan isolasi penuh

1. Pindahkan 13 migrasi di atas ke direktori ini (`0001_01_01_000000_create_users_table.php`
   juga perlu versi tenant karena `users` bersifat per-sekolah).
2. Tetap tinggalkan di central: `tenants`, `domains`, `plans`, `subscriptions`,
   `invoices`, `cbt_nodes`, `sessions`, `cache`, `jobs`.
3. Pasang `InitializeTenancyByDomain` + `PreventAccessFromCentralDomains` pada grup
   rute aplikasi di `routes/web.php` dan pada panel Filament sekolah.
4. Pisahkan panel Filament: satu panel central untuk Super Admin SaaS, satu panel
   tenant untuk sekolah.
5. Arahkan `CACHE_STORE` ke redis/memcached (store `database`/`file` tidak mendukung
   cache tag yang dipakai `CacheTenancyBootstrapper`).
6. Sesuaikan test suite: setiap test yang menyentuh rute aplikasi perlu konteks tenant
   (inisialisasi tenancy + migrasi database tenant), bukan lagi database central.

Langkah 1-6 mengubah kontrak seluruh aplikasi dan test, sehingga tidak dikerjakan
bersamaan dengan perbaikan keamanan lain agar aplikasi tidak tertinggal dalam
kondisi setengah jalan.
