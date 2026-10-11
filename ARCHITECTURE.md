# CETAK BIRU ARSITEKTUR: SISTEM INFORMASI SEKOLAH SAAS TERPADU

> **Versi Framework:** Laravel 13.x (PHP 8.3+)  
> **Teknologi Frontend:** Blade + Livewire + Alpine.js + Tailwind CSS (TALL Stack)  
> **Mesin Multi-Tenancy:** `stancl/tenancy` v3 (Multi-Database Isolation)  
> **Sistem Basis Data:** MySQL 8.0+ / MariaDB (InnoDB Strict Engine)

---

## 1. Topologi Global Arsitektur SaaS

```
                                [ PENGGUNA INTERNET ]
                                          │
                   ┌──────────────────────┴──────────────────────┐
                   │                                             │
                   ▼                                             ▼
       [ Landing Page SaaS ]                        [ Subdomain Sekolah ]
         (platform.id)                      (sekolah-a.platform.id / custom domain)
                   │                                             │
                   ▼                                             ▼
┌──────────────────────────────────────┐     ┌──────────────────────────────────────┐
│       SERVER UTAMA / CENTRAL         │     │         TENANT INSTANCE              │
│       (VPS Spek Standar: 4-8 GB)     │     │      (11 Role Akses Sekolah)         │
├──────────────────────────────────────┤     ├──────────────────────────────────────┤
│ - Central DB (db_saas_central)       │     │ - Tenant DB Terisolasi (tenant_{uuid})│
│ - Subscription (Midtrans / Xendit)   │     │ - SIAKAD, SPP, Presensi, E-Kantin    │
│ - Auto-Provisioning Database Baru    │     │ - Manajemen Bank Soal & e-Raport     │
│ - Node Manager (Kelola Server CBT)   │     │ - WhatsApp Gateway Dispatcher        │
└──────────────────────────────────────┘     └──────────────────┬───────────────────┘
                                                                │
                                            (1) Deploy Soal     │  (3) Tarik Nilai
                                            sebelum ujian       │  setelah ujian
                                                                ▼
                                             ┌──────────────────────────────────────┐
                                             │         SERVER CBT TERPISAH          │
                                             │      (VPS High-Spec On-Demand)       │
                                             ├──────────────────────────────────────┤
                                             │ - cbt.platform.id                    │
                                             │ - Live Countdown Timer               │
                                             │ - Redis Cache (Auto-Save Jawaban)    │
                                             │ - Auto-Grading Pilihan Ganda         │
                                             └──────────────────────────────────────┘
```

---

## 2. Struktur Database Multi-Tenancy

### A. Central Database (`db_saas_central`)
Dikelola oleh **Super Admin Platform (Pemilik SaaS)**:
* `tenants`: UUID, nama_sekolah, npsn, status (`active`, `suspended`, `trial`), paket_id, trial_ends_at, db_name, db_user.
* `domains`: ID, tenant_id, domain (subdomain default `*.platform.id` atau custom domain `portal.sekolah.sch.id`).
* `plans`: Nama paket (Starter, Pro, Enterprise), harga bulanan/tahunan, batas kuota siswa, kuota guru, modul aktif (JSON).
* `subscriptions` & `invoices`: Riwayat transaksi langganan sekolah terintegrasi payment gateway (Midtrans / Xendit).
* `cbt_nodes`: IP Server CBT, nama node, kuota concurrent user, API secret key, status koneksi (idle/active/busy).

### B. Tenant Database (`tenant_{uuid}`)
Satu basis data terpisah untuk masing-masing sekolah, menjamin **100% isolasi data siswa, e-raport, dan dompet digital**:
* **Keamanan & Pengguna:**
  * `users`: username, email, password, nisn, pin_hash (6-digit Bcrypt), status, is_locked, lockout_until.
  * `roles`, `permissions`, `model_has_roles`: Implementasi **Spatie RBAC (11 Roles)**.
  * `audit_trails`: user_id, action, ip_address, user_agent, payload, created_at.
* **Data Induk Akademik:**
  * `academic_years`, `semesters`, `majors`, `grade_levels`, `classrooms`, `subjects`.
  * `students`: user_id, classroom_id, nisn, nik, rfid_uid (unique indexed), nama, nama_ortu, no_wa_ortu, foto_wajah_descriptor (vektor numerik 128-float).
  * `teachers`, `staff`: user_id, nip, rfid_uid (unique indexed), nama_lengkap, no_wa, jabatan.
* **Presensi Cerdas:**
  * `attendances`: user_id, type (siswa/guru), method (qr/gps/face), status (hadir/terlambat/izin/sakit/alpa), lat, lng, waktu_masuk, waktu_pulang, is_wa_notified.
* **Keuangan & Dompet Digital (E-Wallet):**
  * `wallets`: user_id (siswa/vendor), saldo (BigInteger), status.
  * `wallet_transactions`: wallet_id, type (credit/debit), nominal, deskripsi, reference_id, reference_type (SPP/Kantin/Topup). Dilindungi **Pessimistic Locking** (`lockForUpdate()`).
  * `fee_categories`, `student_bills`, `bill_payments`.
* **E-Kantin Digital:**
  * `canteen_stands`: vendor_user_id, nama_stand, status_buka (boolean).
  * `canteen_menus`: stand_id, nama_menu, harga, stok, foto.
  * `canteen_orders`: order_code, student_id, stand_id, total, status, payment_method (wallet/cash), qr_verification_code.
* **LMS & Penilaian:**
  * `lms_topics`, `lms_materials`, `lms_assignments`, `lms_submissions`.
  * `student_points`: Gamifikasi XP & Lencana (Badges).
  * `grade_records`, `report_cards`: Rekap nilai transkrip & e-Raport PDF.
* **Bimbingan Konseling (BK):**
  * `counseling_cases`: student_id, counselor_teacher_id, kategori_pelanggaran, poin, sanksi (SP-1/2/3), berkas_bap_path, status.
  * `counseling_sessions`: Catatan sesi bimbingan pribadi/kelompok.
* **Buku Tamu Digital (Front Office & Keamanan):**
  * `guest_books`: id, nama_tamu, instansi, no_wa, destination_user_id (guru/kepsek yang dituju), keperluan, foto_tamu_path, tanda_tangan_path, check_in_at, check_out_at, qr_pass_code, status (visiting/completed).
* **Manajemen Rapat, Notulen & Berita Acara (BAP):**
  * `school_meetings`: id, title, meeting_type (guru/wali_murid/komite), meeting_date, start_time, end_time, room_location, leader_user_id, minute_taker_user_id, agenda_text, minutes_text, decisions_text, bap_document_path, status (scheduled/ongoing/completed).
  * `meeting_attendances`: id, meeting_id, user_id, checked_in_at, signature_path (tanda tangan digital canvas), attendance_status (hadir/izin/alpa), notes.
  * `meeting_documentations`: id, meeting_id, file_path, file_type (photo/document), caption.

---

## 3. Protokol Decoupled CBT & Client Multi-Platform

### A. Dua Mode Deployment CBT
1. **Mode 1: Full Online Cloud CBT (VPS On-Demand)**
   * Dijalankan pada domain `cbt.platform.id` menggunakan VPS berkecepatan tinggi dengan Redis Caching.
2. **Mode 2: Semi-Offline Local CBT (Gaya ANBK / UNBK Kemendikbud)**
   * Dijalankan di 1 PC Server / Laptop di Lab Komputer Sekolah via jaringan lokal (LAN / WiFi lokal).
   * **Tahap Download:** Mengunduh paket soal dan token siswa dari SaaS Cloud ke server lab lokal.
   * **Tahap Ujian (100% Offline):** Siswa mengakses IP lokal (`http://192.168.x.x`). Nol kuota internet saat ujian berlangsung.
   * **Tahap Upload:** Setelah ujian usai, server lokal dihubungkan ke internet dan mengirimkan rekap skor ke SaaS Cloud (e-Raport).

### B. Client Ujian Siswa (Android Exambro & Web Anti-Cheat)
1. **Aplikasi Android Exambrowser (Exambro APK):**
   * Berukuran 3–5 MB khusus smartphone Android siswa (BYOD).
   * **Lockdown Mode (Kiosk Pinning):** Mematikan tombol Home, Back, dan Recent Apps.
   * **Proteksi Screenshot & Recording:** Mengaktifkan `FLAG_SECURE` (layar hitam jika direkam/screenshot).
   * **Blokir Multitasking:** Mematikan split-screen, floating apps, dan pop-up notifikasi pesan (WA, dll).
   * **Pelanggaran:** Terdeteksi keluar aplikasi $\rightarrow$ otomatis terkunci dan membutuhkan reset token dari pengawas/proktor.
2. **Web Browser Client (PC Lab / Laptop / iOS):**
   * Fullscreen Enforcement (Layar Penuh Wajib).
   * Page Visibility API: Deteksi pergantian tab browser dengan toleransi maksimal pelanggaran (Auto-Lockout).
   * Disable copy-paste, klik kanan, dan inspect element.

### C. Alur Sinkronisasi Data (Deploy & Pull)
1. **Deploy Paket Ujian (SIAKAD -> VPS/Local CBT):**
   * Endpoint: `POST /api/v1/sync/deploy-exam`
   * Header: `Authorization: Bearer <CBT_SECRET_KEY>`, `X-Tenant-UUID: <tenant_id>`
   * Payload: Data siswa peserta, token login ujian, bank soal terenkripsi, kunci jawaban terenkripsi.
2. **Eksekusi Ujian:**
   * Live countdown timer berjalan di browser/Exambro (Alpine.js).
   * Auto-save jawaban ke **Redis** per 500ms (resilience via LocalStorage & IndexedDB jika koneksi goyang).
   * Auto-submit saat waktu habis.
3. **Koreksi & Tarik Nilai (CBT -> SIAKAD):**
   * Auto-grading pilihan ganda & antarmuka penilaian essay.
   * Endpoint penarikan: `POST /api/v1/sync/pull-results`
   * Nilai langsung masuk ke database Tenant, terintegrasi ke modul e-Raport.

---

## 4. Arsitektur IoT Standalone RFID Device (Opsi B - Presensi Mandiri)
Sistem mendukung perangkat keras mandiri (ESP32 / NodeMCU + RFID RC522/PN532 + LCD/OLED I2C + Buzzer) yang terpasang di dinding gerbang sekolah dan terhubung ke WiFi lokal.

```
[ Siswa Tap Kartu RFID ] ──► [ Perangkat IoT ESP32 ]
                                     │ WiFi HTTP POST (< 300ms)
                                     ▼
                      [ REST API Tenant Sekolah ]
                       POST /api/v1/attendance/tap-rfid
                                     │
           ┌─────────────────────────┴─────────────────────────┐
           ▼                                                   ▼
[ Respon ke Perangkat IoT ]                         [ WhatsApp Gateway Queue ]
{                                                   Kirim notifikasi instan
  "status": "success",                              ke nomor HP orang tua:
  "name": "Ahmad Fauzi",                            "Ananda telah hadir di
  "time": "06:45:10",                               sekolah pkl 06:45 WIB"
  "message": "Selamat Pagi!",
  "buzzer": "beep_single"
}
           │
           ▼
[ Layar LCD Tampil Nama Siswa & Buzzer Berbunyi ]
```

* **Spesifikasi Endpoint API IoT:**
  * **URL:** `POST https://{sekolah}.platform.id/api/v1/attendance/tap-rfid`
  * **Security Header:** `X-Device-Token: <SECRET_DEVICE_TOKEN_PER_SEKOLAH>`
  * **Payload Request:**
    ```json
    {
      "rfid_uid": "A1B2C3D4",
      "device_id": "GATE_01_IN"
    }
    ```
  * **Fitur Caching & Anti-Double Tap:** Mencegah kartu yang sama di-tap berulang kali dalam rentang 3 menit untuk menghindari duplikasi data presensi.

---

## 5. Standar Keamanan & Performa Industri

* **Proteksi IDOR (Insecure Direct Object References):**
  Menggunakan `vinkla/hashids` untuk menyamarkan primary key integer pada URL (`/siswa/d7a9F2b1`).
* **Proteksi Transaksi Finansial:**
  Semua operasi mutasi saldo tabungan dan pembayaran belanja e-kantin wajib dibungkus dalam `DB::transaction()` dengan pessimistic locking `lockForUpdate()` untuk mencegah *race conditions* atau *double spending*.
* **Privasi Biometrik Wajah (AI Face ID):**
  Pemindaian wajah dilakukan di browser menggunakan `face-api.js` (TensorFlow.js). Server hanya menyimpan **vektor floating-point 128-dimensi**, bukan foto mentah berulang kali. Ini menghemat kuota penyimpanan server dan menjaga privasi biometrik pengguna.
* **Standardisasi Kartu Siswa (ISO CR-80):**
  Ukuran standar 85.6mm x 54mm dengan QR code vektor berbasis SVG untuk presisi tinggi saat dicetak ke printer kartu PVC (Zebra / Evolis) maupun kertas biasa.

