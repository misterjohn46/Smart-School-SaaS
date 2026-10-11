# ROADMAP IMPLEMENTASI SAAS SISTEM INFORMASI SEKOLAH TERPADU

> **Versi Framework:** Laravel 13.x (PHP 8.3+)  
> **Arsitektur Utama:** Multi-Tenancy (Multi-Database via `stancl/tenancy` v3)  
> **Mesin CBT:** Decoupled Architecture (Server Ujian VPS Terpisah On-Demand)  
> **Status:** Fase 1 Siap Dijalankan

---

## 👥 Tim Subagent Pengembang
Proyek ini divalidasi dan dikerjakan oleh 4 subagent spesialis:
1. **`saas_architect`**: Mengelola Multi-Tenancy (`stancl/tenancy`), Central DB vs Tenant DB, migrasi skema, dan relasi data antar modul.
2. **`auth_security_specialist`**: Mengelola otentikasi 11 role Spatie, login multi-metode, Obfuscated Hash ID (Anti-IDOR), PIN 6-digit e-wallet, brute-force lockout, dan audit trail.
3. **`frontend_ui_designer`**: Mengelola antarmuka Livewire 3/Alpine.js/Tailwind CSS, Glassmorphic Mobile-First, ISO CR-80 card builder, dan POS kasir kantin.
4. **`cbt_engine_specialist`**: Mengelola arsitektur Node CBT terpisah, Redis high-concurrency caching, sync API soal & nilai, dan auto-grading.

---

## 📋 Matriks 11 Role Pengguna
- [x] **1. Super Admin Platform:** Pemilik SaaS (billing langganan, manajemen tenant sekolah, node CBT).
- [ ] **2. Admin Sekolah / Tata Usaha (TU):** Pengelola data induk sekolah, konfigurasi, dan pengguna.
- [ ] **3. Guru Pengajar:** Pengampu mata pelajaran, absensi, input nilai, dan materi LMS.
- [ ] **4. Guru BK (Peran Terisolasi):** Penanganan kasus pelanggaran, poin kedisiplinan, SP 1-3, Berita Acara (BAP), dan konseling.
- [ ] **5. Bendahara Sekolah:** POS SPP, arus kas keluar/masuk, verifikasi transfer, dan manajemen tabungan siswa.
- [ ] **6. Operator Sekolah:** Pembantu administrasi akademik dan sinkronisasi data.
- [ ] **7. Staf Sekolah:** Tenaga kependidikan non-guru, presensi harian GPS.
- [ ] **8. Siswa:** Portal Mobile-First Glassmorphic, KTS digital, LMS, tugas, kuis, CBT, dan e-kantin.
- [ ] **9. Orang Tua:** Portal monitoring 4-tab instan tanpa password (NISN + Tgl Lahir + OTP WA).
- [ ] **10. Calon Siswa (PPDB):** Pendaftaran online multi-step, cetak bukti daftar, dan pengumuman seleksi.
- [ ] **11. Vendor & Kasir Kantin:** Pengelola stand kantin, stok menu harian, kasir POS belanja, dan penarikan saldo (withdrawal).

---

## 🚀 Rencana Kerja Bertahap (Milestones)

### [Fase 1] Pondasi SaaS, Multi-Tenancy & Platform Onboarding
- [x] Inisialisasi Proyek Laravel 13, Tailwind CSS, Alpine.js, & Livewire
- [x] Instalasi & Konfigurasi `stancl/tenancy` v3 (Multi-Database Driver)
- [x] Desain Database Central (`db_saas_central`):
  - [x] Tabel `tenants`, `domains` (Telah termigrasi)
  - [x] Tabel `plans`, `subscriptions`, `invoices`, `cbt_nodes` (Termigrasi & Seeder Aktif)
- [x] Landing Page SaaS & Form Registrasi Sekolah (Onboarding Wizard Interaktif)
- [x] Otomatisasi Pembuatan Database Tenant & Migrasi Skema Awal
- [ ] Manajemen Custom Domain Sekolah (Input Domain, DNS CNAME Verification Tool, & Auto-SSL)

### [Fase 2] RBAC 11 Role & Master Data Akademik
- [x] Instalasi `spatie/laravel-permission` (v8.x) pada tingkat Tenant
- [x] Multi-Method Authentication:
  - [x] Admin / Guru / Staf / Vendor: Email/Username + Password
  - [x] Siswa: NISN + Password
  - [x] Orang Tua: NISN + Tanggal Lahir Siswa + Direct Child Verification
- [x] Sistem Proteksi Keamanan:
  - [x] Obfuscated Route Hash ID (`hashids/hashids`) pencegah IDOR (`HasObfuscatedId` trait)
  - [x] Proteksi Brute-force & Account Lockout Otomatis (5 kali gagal terkunci 30 menit)
  - [x] Fitur 1-Klik Buka Kunci Akun (Lockout Unlocker) oleh Admin di Filament
  - [x] Dashboard Audit Trail & Login Activity Log Real-Time (`AuditTrailResource`)
- [x] Master Data Akademik:
  - [x] Tahun Akademik & Semester (Aktif/Nonaktif Toggle) (`AcademicYearResource`)
  - [x] Tingkat, Jurusan, Kelas, & Rombel (`DepartmentResource`, `ClassroomResource`)
  - [x] Manajemen Guru & Tenaga Kependidikan (`TeacherProfile` & seeder)
  - [x] Manajemen Siswa & Relasi Orang Tua (`StudentProfile` & seeder)
  - [x] Manajemen Mata Pelajaran & Standar KKM (`SubjectResource`)
- [x] Layout Multi-Portal:
  - [x] Desktop Layout (Admin Filament `/admin`, Dashboard Pegawai/Guru)
  - [x] Mobile-First Layout Glassmorphic (Portal Siswa `/portal/siswa` & Orang Tua `/portal/orangtua`)

### [Fase 3] KTS Digital & Presensi Cerdas Terintegrasi
- [x] Generator Kartu Tanda Siswa (KTS) Digital format standar ISO CR-80 (85.6mm x 54mm)
- [x] Generator QR Code Vektor presisi tinggi berbasis NISN
- [x] KTS Template Builder (warna header, aksen, watermark) & Bulk Print PDF format A4 (8 kartu/halaman)
- [x] Modul Presensi QR Code Siswa & preview kartu bolak-balik 3D
- [x] **Presensi IoT RFID Standalone (Opsi B - ESP32 / RC522 via WiFi):**
  - [x] Endpoint API Aman: `POST /api/v1/attendance/tap-rfid` dengan validasi `X-Device-Token`
  - [x] Respon Cepat LCD/OLED & Audio Buzzer (Nama Siswa, Jam Masuk, Status Hadir/Terlambat)
  - [x] Modul Pairing / Registrasi Kartu RFID Siswa & Guru di Panel Admin
- [x] Presensi Guru & Staf via GPS Geolocation & Radius Geofencing Sekolah (Formula Haversine 100m) + Swafoto
- [x] Presensi AI Face Recognition (Client-side camera matching)
- [x] Overlay Siluet Wajah Manusia Ergonomis & Indikator Titik Tengah
- [x] Notifikasi Presensi WhatsApp Otomatis ke Orang Tua (Masuk & Pulang)
- [x] Pengajuan Izin / Sakit dengan Lampiran Berkas (E-Leave Approval Workflow)
- [x] Rekapitulasi Presensi & Ekspor Data (`AttendanceResource`)

### [Fase 4] Keuangan Sekolah, Tabungan (E-Wallet) & E-Kantin Digital
- [ ] Master Pos Biaya (SPP Bulanan, Uang Gedung, Seragam, Ujian)
- [ ] Distribusi Tagihan Massal per Kelas / Angkatan
- [ ] POS Kasir Pembayaran SPP Sekolah dengan Input Rupiah Dinamis + Terbilang
- [ ] Verifikasi Transfer Bank Manual (Approval/Rejection Bukti Bayar)
- [ ] Cetak Kwitansi Transaksi & Riwayat Pembayaran Siswa
- [ ] Pencatatan Arus Kas (Income & Expense) & Laporan Neraca Keuangan
- [ ] Tabungan Siswa & Dompet Digital (E-Wallet): Setor, Tarik, Mutasi Buku Tabungan
- [ ] Keamanan Transaksi Finansial: PIN 6-Digit & Database Pessimistic Locking (`lockForUpdate`)
- [ ] E-Kantin Portal Vendor:
  - [ ] Sakelar Status Stand Buka / Tutup Real-Time
  - [ ] Manajemen Menu, Kategori & Batch Stok Harian
  - [ ] Kasir POS Vendor (Pencarian Siswa, Checkout, Scan QR Kupon Makanan)
  - [ ] Penarikan Saldo Vendor (Withdrawal) & Laporan Omzet Penjualan
- [ ] E-Kantin Portal Siswa (Mobile Marketplace & Tap RFID):
  - [ ] Jelajah Menu per Stand & Pemesanan Makanan Online via Smartphone
  - [ ] Pembayaran via Saldo E-Wallet Tabungan Siswa
  - [ ] Kasir Tap Kartu RFID Siswa (Pembayaran Cepat Kantin Tanpa HP)
  - [ ] Pelacakan Status Pesanan Real-Time (Menunggu, Diproses, Siap Diambil, Selesai)

### [Fase 5] Pembelajaran LMS & e-Raport Digital
- [ ] LMS: Struktur Bab, Topik Pembelajaran, Distribusi Bahan Ajar (PDF, Video, Link)
- [ ] LMS: Tugas Siswa, Batas Deadline, Submisi Berkas & Penilaian Guru
- [ ] LMS: Topik Kuis Interaktif & Integrasi Live Class Meeting Stream
- [ ] Gamifikasi Belajar: Perolehan XP, Lencana (Badges), Leaderboard, & Progress Bar
- [ ] Transkrip Nilai Akademik Terpadu (Tugas, Harian, UTS, UAS) & Bulk Grading
- [ ] Modul Cetak e-Raport Digital PDF (Format Resmi Kurikulum Merdeka / K13)
- [ ] Kalkulasi Predikat KKM & Catatan Akademik Wali Kelas Otomatis

### [Fase 6] Standalone CBT Engine (Server Ujian Terpisah & Android Exambro)
- [ ] Modul Manajemen Ujian & Bank Soal di Server Utama (Impor/Ekspor Excel .xlsx)
- [ ] Aplikasi Node CBT Terpisah (High-Performance Engine):
  - [ ] **Mode 1 (Full Online):** VPS Cloud On-Demand terpisah dengan Redis Auto-Save Caching
  - [ ] **Mode 2 (Semi-Offline Gaya ANBK):** Server Lab Lokal Sekolah (Download Soal, Ujian 100% Offline via LAN/WiFi, Upload Nilai ke Cloud)
- [ ] **Aplikasi Android Exambrowser (Exambro APK 3-5 MB):**
  - [ ] Fitur Lockdown Kiosk Mode (Pin Screen, Matikan Home/Back/Recent)
  - [ ] Proteksi Anti-Screenshot & Screen Recording (`FLAG_SECURE`)
  - [ ] Blokir Split Screen, Floating Apps, & Notifikasi Masuk
  - [ ] Auto-Lockout Pelanggaran & Sistem Reset Token Pengawas
- [ ] **Web Browser Client Anti-Cheat (Lab PC Windows / Mac / iOS):**
  - [ ] Fullscreen Mandatory Mode & Deteksi Pergantian Tab (Page Visibility API)
  - [ ] Blokir Klik Kanan, Inspect Element, Text Selection & Copy-Paste
- [ ] REST API Deploy Soal & Peserta dari Server Utama ke Server CBT
- [ ] Antarmuka Ujian Siswa: Live Timer, Indikator Ragu-ragu, Auto-Save LocalStorage + Redis
- [ ] Auto-Grading Pilihan Ganda & Antarmuka Koreksi Manual Essay + Catatan Guru
- [ ] REST API Sinkronisasi Nilai Ujian kembali ke Server Utama (e-Raport)
- [ ] Dashboard Diagnostik Server CBT: RAM, CPU, PHP Memory, Concurrent Users Calculator, 1-Click Cache Toolkit

### [Fase 7] Layanan Pendukung (BK, PPDB, Perpustakaan, Profil Web, Maintenance)
- [ ] Modul BK: Poin Pelanggaran (Ringan-Sangat Berat), SP-1/2/3, Berita Acara (BAP) Generator PDF
- [ ] Modul BK: Sesi Konseling (Individu, Kelompok, Karir) & Asesmen Minat Bakat
- [ ] Portal Orang Tua: Dashboard Monitoring 4-Tab (Presensi, Nilai, SPP, Jadwal/Pengumuman)
- [ ] Penerimaan Murid Baru (SPMB / PPDB Online) Multi-Step & Konversi Otomatis Akun Siswa
- [ ] Perpustakaan Digital: Katalog Buku Fisik, Sirkulasi Tap RFID & Built-in Mozilla PDF.js Reader
- [ ] Manajemen Akademik: Jadwal Pelajaran KBM, Kurikulum & Ekstrakurikuler
- [ ] WhatsApp Gateway & Broadcast Manager (Fonnte, Wablas, Baileys HTTP)
- [ ] Website Utama Profil Sekolah (CMS, Berita SEO, Banner Slider, Galeri)
- [ ] **Buku Tamu Digital (Digital Guest Book & Front Office Kiosk):**
  - [ ] Antarmuka Kios Tablet Lobi: Data Tamu, Instansi, Keperluan & Pihak yang Dituju (Guru/Kepsek)
  - [ ] Tangkapan Foto Wajah Tamu (Kamera Lobi) & Tanda Tangan Digital (Canvas Signature)
  - [ ] Notifikasi WhatsApp Otomatis ke Guru / Kepala Sekolah yang Dituju saat tamu tiba
  - [ ] Cetak Guest Pass / QR Code Tiket Tamu & Scan Check-Out saat pulang
  - [ ] Rekapitulasi & Ekspor Laporan Kunjungan Tamu (Excel & PDF)
- [ ] **Manajemen Rapat, Notulen & Berita Acara (BAP) Digital:**
  - [ ] Penjadwalan Rapat (Dewan Guru, Staf, Wali Murid, Komite) & Broadcast Undangan WhatsApp
  - [ ] Presensi Kehadiran & Tanda Tangan Digital Guru di Layar HP/Tablet (Canvas Signature Pad)
  - [ ] Pencatatan Notulen Rapat, Resume Keputusan & Tindak Lanjut (*Action Items / PIC*)
  - [ ] Generator Berita Acara Rapat (BAP) Format PDF Resmi (Kop Surat, Lampiran Daftar Hadir Bertanda Tangan, Pengesahan Kepsek)
  - [ ] Unggah Dokumentasi Foto Kegiatan Rapat & Lampiran Berkas Materi (PDF/PPT)
- [ ] Pengaturan Sistem, Database Backup Otomatis & Web Installer Setup Wizard
