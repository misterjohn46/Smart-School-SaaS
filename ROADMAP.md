# ROADMAP IMPLEMENTASI SAAS SISTEM INFORMASI SEKOLAH TERPADU

> **Versi Framework:** Laravel 13.x (PHP 8.3+)  
> **Arsitektur Utama:** Multi-Tenancy (Multi-Database via `stancl/tenancy` v3)  
> **Mesin CBT:** Decoupled Architecture (Server Ujian VPS Terpisah On-Demand)  
> **Frontend Nyata:** Inertia 2 + Vue 3 + Tailwind CSS 3 (panel admin: Filament 5)  
> **Status:** Fase 1-4 praktis tuntas (sisa: arus kas & kelengkapan kantin vendor).
> Fase 5 (LMS), Fase 6 (mesin CBT & klien ujian), dan Fase 7 (layanan pendukung) belum dimulai.
>
> Legenda: `[x]` selesai · `[~]` sebagian · `[ ]` belum

> ⚠️ **Perbedaan dengan rancangan awal:** dokumen ini semula merencanakan TALL stack
> (Livewire 3 + Alpine.js). Implementasinya memakai **Inertia + Vue 3**; Livewire hanya
> hadir sebagai dependensi Filament. Status per komponen ada di `ARCHITECTURE.md` bagian 0.

---

## 📋 Matriks Role Pengguna
- [x] **1. Super Admin Platform:** Pemilik SaaS (billing langganan, manajemen tenant sekolah, node CBT).
- [x] **2. Admin Sekolah / Tata Usaha (TU):** Pengelola data induk sekolah, konfigurasi, dan pengguna.
- [x] **3. Guru Pengajar:** Pengampu mata pelajaran, absensi, input nilai. *(materi LMS belum)*
- [x] **4. Guru BK:** Pelanggaran, poin kedisiplinan, dan level SP-1/2/3. *(BAP PDF & sesi konseling belum)*
- [x] **5. Bendahara Sekolah:** POS SPP, tagihan, tabungan siswa, verifikasi transfer. *(arus kas & neraca belum)*
- [x] **6. Operator Sekolah:** Pembantu administrasi akademik.
- [x] **7. Staf Sekolah:** Tenaga kependidikan non-guru, presensi harian GPS.
- [x] **8. Siswa:** Portal Mobile-First, KTS digital, e-kantin, dompet. *(LMS, tugas, kuis belum)*
- [x] **9. Orang Tua:** Portal monitoring (NISN + Tgl Lahir). *(OTP WhatsApp sebagai faktor kedua belum)*
- [ ] **10. Calon Siswa (PPDB):** Pendaftaran online multi-step, cetak bukti daftar, dan pengumuman seleksi.
- [ ] **11. Vendor & Kasir Kantin:** Kasir POS stand sudah ada; stok menu dikelola dari panel admin. *(portal vendur mandiri & penarikan saldo belum)*

---

## 🚀 Rencana Kerja Bertahap (Milestones)

### [Fase 1] Pondasi SaaS, Multi-Tenancy & Platform Onboarding
- [x] Inisialisasi Proyek Laravel 13, Tailwind CSS, Inertia + Vue 3
- [x] Instalasi & Konfigurasi `stancl/tenancy` v3 (Multi-Database Driver)
- [x] Desain Database Central (`db_saas_central`):
  - [x] Tabel `tenants`, `domains` (Telah termigrasi)
  - [x] Tabel `plans`, `subscriptions`, `invoices`, `cbt_nodes` (Termigrasi & Seeder Aktif)
- [x] Landing Page SaaS & Form Registrasi Sekolah (Onboarding Wizard Interaktif)
- [x] Provisioning tenant: satu domain per sekolah, password admin acak
- [x] **Isolasi data per sekolah (single-database):** kolom `tenant_id` pada 35 tabel,
      global scope lewat `App\Models\Concerns\BelongsToSchool`, identifikasi tenant dari
      domain oleh `InitializeTenancyUnlessCentralDomain`, unique komposit per tenant, dan
      perintah `tenants:adopt-data` untuk data instalasi lama.
      Diuji di `tests/Feature/TenantDataIsolationTest.php`
  - Catatan: menyimpang dari rancangan multi-database di `ARCHITECTURE.md`; isolasi kini
    berada di lapisan aplikasi, sehingga `withoutTenancy()` dan SQL mentah wajib ditinjau
- [x] Panel Filament tenant-aware (satu panel, lingkup data mengikuti domain)
- [ ] Manajemen Custom Domain Sekolah (Input Domain, DNS CNAME Verification Tool, & Auto-SSL)

### [Fase 2] RBAC 11 Role & Master Data Akademik
- [x] Instalasi `spatie/laravel-permission` (v8.x) pada tingkat Tenant
- [x] Multi-Method Authentication:
  - [x] Admin / Guru / Staf / Vendor: Email/Username + Password
  - [x] Siswa: NISN + Password
  - [x] Orang Tua: NISN + Tanggal Lahir Siswa + Direct Child Verification
- [x] Verifikasi email ditegakkan (`User implements MustVerifyEmail`): pendaftaran mandiri
      wajib memverifikasi, akun yang diprovisikan sekolah pra-verifikasi
- [x] Sistem Proteksi Keamanan:
  - [x] Otorisasi peran per modul pada seluruh rute (`middleware('role:...')`) — matriks di `README.md`
  - [~] Obfuscated Route Hash ID (`hashids/hashids`) pencegah IDOR (`HasObfuscatedId` trait).
        Aktif di modul KTS & kuitansi; modul CBT dan geofence masih memakai ID integer.
        Hash ID bukan pengganti otorisasi — endpoint tetap memverifikasi kepemilikan.
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
- [x] KTS Template Builder (warna header, aksen, watermark) & Bulk Print PDF A4 (8 kartu/halaman)
      — PDF digenerate server (`kts.batch.pdf`, dompdf) dengan satuan milimeter sehingga
      kartu presisi 85,6mm x 54mm; cetak lewat dialog browser tetap tersedia sebagai opsi
- [x] Modul Presensi QR Code Siswa & preview kartu bolak-balik 3D
- [x] **Presensi IoT RFID Standalone (Opsi B - ESP32 / RC522 via WiFi):**
  - [x] Endpoint API Aman: `POST /api/v1/attendance/tap-rfid` dengan validasi `X-Device-Token`
  - [x] Respon Cepat LCD/OLED & Audio Buzzer (Nama Siswa, Jam Masuk, Status Hadir/Terlambat)
  - [x] Modul Pairing / Registrasi Kartu RFID Siswa & Guru di Panel Admin
- [x] Presensi Guru & Staf via GPS Geolocation & Radius Geofencing Sekolah (Formula Haversine 100m) + Swafoto
- [x] Presensi AI Face Recognition (Client-side camera matching)
- [x] Overlay Siluet Wajah Manusia Ergonomis & Indikator Titik Tengah
- [~] Notifikasi Presensi WhatsApp ke Orang Tua (Masuk & Pulang) — **masih simulasi**:
      `AttendanceService::sendWhatsAppAttendanceAlert` hanya mencatat ke log (nomor dimasker),
      gateway sesungguhnya (Fonnte/Wablas/Baileys) dijadwalkan di Fase 7
- [x] Pengajuan Izin / Sakit dengan Lampiran Berkas (E-Leave Approval Workflow)
- [x] Rekapitulasi Presensi & Ekspor Data (`AttendanceResource`) — ekspor XLSX/CSV lewat
      `AttendanceExporter`: aksi header (mengikuti filter tabel) dan aksi massal
      (baris terpilih). Query ekspor dibatasi tenant secara eksplisit dan nilai teks
      bebas dinetralkan terhadap CSV formula injection.

### [Fase 4] Keuangan Sekolah, Tabungan (E-Wallet) & E-Kantin Digital
- [x] Master Pos Biaya (SPP Bulanan, Uang Gedung, Seragam, Ujian) — `FinanceController` + `FeeCategoryResource`
- [x] Distribusi Tagihan Massal per Kelas / Angkatan — `generateBatchBills`
- [x] POS Kasir Pembayaran SPP Sekolah dengan Input Rupiah Dinamis + Terbilang — `FinanceService::terbilang`
- [x] Verifikasi Transfer Bank Manual (Approval/Rejection Bukti Bayar) — `FinanceService::verifyBankTransfer`
      dengan aksi Setujui/Tolak serta pratinjau bukti di `PaymentResource`. Unggahan membuat
      pembayaran berstatus `pending` dan tagihan baru terkredit setelah disetujui kasir.
  - [x] Bukti transfer disimpan di disk privat `secure` dan disajikan lewat rute bergerbang
        `finance.proof` (peran keuangan atau pemilik tagihan saja)
  - [ ] Halaman Vue untuk unggah bukti dari sisi orang tua (endpoint & otorisasi sudah siap)
- [x] Cetak Kwitansi Transaksi & Riwayat Pembayaran Siswa — `finance.receipt`
- [ ] Pencatatan Arus Kas (Income & Expense) & Laporan Neraca Keuangan
- [x] Tabungan Siswa & Dompet Digital (E-Wallet): Setor, Tarik, Mutasi Buku Tabungan
- [x] Keamanan Transaksi Finansial: PIN 6-Digit & Database Pessimistic Locking (`lockForUpdate`)
  - [x] Mutasi saldo dibatasi peran kasir (`admin`, `bendahara`); PIN & dompet diturunkan dari akun yang login
  - [x] Limit belanja harian benar-benar terakumulasi antar transaksi (sebelumnya tidak pernah aktif)
- [ ] E-Kantin Portal Vendor:
  - [ ] Sakelar Status Stand Buka / Tutup Real-Time
  - [x] Manajemen Menu & Stok Harian — `CanteenMenuResource` (lewat panel admin, belum portal vendor mandiri)
  - [x] Kasir POS Vendor (Pencarian Siswa, Checkout) — `canteen.stand.pos`
  - [ ] Scan QR Kupon Makanan
  - [ ] Penarikan Saldo Vendor (Withdrawal) & Laporan Omzet Penjualan
- [ ] E-Kantin Portal Siswa (Mobile Marketplace & Tap RFID):
  - [x] Jelajah Menu per Stand & Pemesanan Makanan Online via Smartphone
  - [x] Pembayaran via Saldo E-Wallet Tabungan Siswa
  - [ ] Kasir Tap Kartu RFID Siswa (Pembayaran Cepat Kantin Tanpa HP)
  - [x] Pelacakan Status Pesanan (Menunggu, Diproses, Siap Diambil, Selesai) — `canteen.order.status`

### [Fase 5] Pembelajaran LMS & e-Raport Digital
- [ ] LMS: Struktur Bab, Topik Pembelajaran, Distribusi Bahan Ajar (PDF, Video, Link)
- [ ] LMS: Tugas Siswa, Batas Deadline, Submisi Berkas & Penilaian Guru
- [ ] LMS: Topik Kuis Interaktif & Integrasi Live Class Meeting Stream
- [ ] Gamifikasi Belajar: Perolehan XP, Lencana (Badges), Leaderboard, & Progress Bar
- [x] Transkrip Nilai Akademik Terpadu (Tugas, Harian, UTS, UAS) & Bulk Grading
      — kolom `assignment_score`/`quiz_score`/`midterm_score`/`final_score` pada
      `student_grades`, input massal lewat `GradeController::batchStoreOrUpdate`
- [x] Kalkulasi Predikat KKM & Catatan Akademik Wali Kelas
      — `StudentGrade::calculateScore()` (bobot 20/20/30/30, predikat A-D, status
      ketuntasan) dan `AcademicReportController::storeRemark`
- [x] Modul Cetak e-Raport Digital PDF — `academic.reports.pdf` (dompdf): kop sekolah,
      identitas siswa, rincian nilai per mapel (tugas/harian/UTS/UAS), nilai akhir,
      predikat, ketuntasan KKM, rekap ketidakhadiran, ekstrakurikuler, catatan wali
      kelas, keputusan kenaikan, dan ruang tanda tangan
  - [ ] Penyesuaian format resmi Kurikulum Merdeka / K13 (capaian pembelajaran per
        elemen & deskripsi otomatis) — struktur saat ini masih format nilai angka

### [Fase 6] Standalone CBT Engine (Server Ujian Terpisah & Android Exambro)
- [x] Modul Manajemen Ujian & Bank Soal di Server Utama — `CbtController`:
      bank soal, butir soal, sesi ujian, token ujian, sinkron nilai ke buku nilai
- [ ] Impor/Ekspor Bank Soal format Excel .xlsx
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
      — yang ada baru simulator pengerjaan dari sisi admin (`submitSimulation`),
      belum antarmuka ujian untuk siswa
- [~] Auto-Grading Pilihan Ganda & Koreksi Manual Essay
  - [x] Auto-grading pilihan ganda (`CbtController::submitSimulation`)
  - [ ] Antarmuka koreksi manual essay + catatan guru
- [ ] REST API Sinkronisasi Nilai Ujian kembali ke Server Utama (e-Raport)
- [ ] Dashboard Diagnostik Server CBT: RAM, CPU, PHP Memory, Concurrent Users Calculator, 1-Click Cache Toolkit

### [Fase 7] Layanan Pendukung (BK, PPDB, Perpustakaan, Profil Web, Maintenance)
- [~] Modul BK: Poin Pelanggaran, SP-1/2/3, Berita Acara (BAP) PDF
  - [x] Katalog tata tertib berbobot poin & pencatatan pelanggaran (`StudentViolationController`)
  - [x] Perhitungan otomatis level sanksi SP-1 (21-40) / SP-2 (41-74) / SP-3 (>=75)
  - [ ] Generator Berita Acara (BAP) format PDF
- [ ] Modul BK: Sesi Konseling (Individu, Kelompok, Karir) & Asesmen Minat Bakat
- [x] Portal Orang Tua: Dashboard Monitoring (Presensi, Nilai, SPP/Tunggakan, Jadwal)
      — `PortalController::parent` + `Portal/ParentDashboard.vue`
- [ ] Penerimaan Murid Baru (SPMB / PPDB Online) Multi-Step & Konversi Otomatis Akun Siswa
- [ ] Perpustakaan Digital: Katalog Buku Fisik, Sirkulasi Tap RFID & Built-in Mozilla PDF.js Reader
- [~] Manajemen Akademik: Jadwal Pelajaran KBM, Kurikulum & Ekstrakurikuler
  - [x] Jadwal Pelajaran KBM (`LessonSchedule`, `ScheduleController`, `LessonScheduleResource`)
  - [ ] Kurikulum & Ekstrakurikuler
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
