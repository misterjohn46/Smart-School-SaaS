<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter (SD & SMP)',
                'slug' => 'starter',
                'description' => 'Solusi terjangkau untuk sekolah dasar & menengah pertama yang membutuhkan digitalisasi administrasi dasar.',
                'price_monthly' => 499000,
                'price_yearly' => 4990000,
                'max_students' => 250,
                'max_teachers' => 30,
                'max_storage_mb' => 10240,
                'features' => [
                    'SIAKAD & Master Data Akademik',
                    'Presensi Siswa via Scan QR Code',
                    'Tagihan SPP & Kasir Pembayaran Sekolah',
                    'Buku Tamu Digital (Front Office Kiosk)',
                    'Website Resmi Sekolah (CMS & Berita SEO)',
                    'Notifikasi Presensi Otomatis via WhatsApp',
                    'Portal Orang Tua & Siswa',
                ],
                'is_active' => true,
                'is_popular' => false,
            ],
            [
                'name' => 'Pro (SMA & SMK)',
                'slug' => 'pro',
                'description' => 'Paket terlengkap untuk sekolah menengah & kejuruan dengan integrasi CBT terpisah, IoT RFID, dan e-Raport.',
                'price_monthly' => 999000,
                'price_yearly' => 9990000,
                'max_students' => 750,
                'max_teachers' => 75,
                'max_storage_mb' => 30720,
                'features' => [
                    'Semua Fitur Paket Starter',
                    'Ujian Online CBT (Online Cloud & Semi-Offline ANBK)',
                    'Aplikasi Android Exambrowser (Exambro APK Anti-Curang)',
                    'Presensi Mandiri IoT RFID (ESP32 via WiFi Gerbang)',
                    'Presensi Biometrik AI Face Recognition',
                    'e-Raport Kurikulum Merdeka / K13 PDF Ready',
                    'LMS Pembelajaran, Tugas & Gamifikasi XP',
                    'Bimbingan Konseling (BK) & Generator BAP Pelanggaran',
                    'Manajemen Rapat, Notulen & Tanda Tangan Digital Guru',
                    'Smart Timetable Generator (Jadwal Anti-Bentrok)',
                ],
                'is_active' => true,
                'is_popular' => true,
            ],
            [
                'name' => 'Enterprise (Yayasan & Sekolah Unggulan)',
                'slug' => 'enterprise',
                'description' => 'Solusi tanpa batas untuk yayasan besar, sekolah unggulan, dan institusi dengan custom domain & E-Kantin.',
                'price_monthly' => 1999000,
                'price_yearly' => 19990000,
                'max_students' => 3000,
                'max_teachers' => 200,
                'max_storage_mb' => 102400,
                'features' => [
                    'Semua Fitur Paket Pro',
                    'Dukungan Custom Domain Sekolah (contoh: portal.sman1.sch.id)',
                    'Penerbitan SSL HTTPS Otomatis Tanpa Batas',
                    'E-Kantin Digital (Kasir Stand, Saldo E-Wallet & Tap RFID)',
                    'Buku Tabungan Siswa Digital & Cetak Rekap Mutasi',
                    'Prioritas Server CBT Dedicated (Tahan Ribuan Siswa)',
                    'Penerimaan Murid Baru (PPDB Online) Multi-Step',
                    'Perpustakaan Digital (Katalog & Mozilla PDF.js)',
                    'Dedicated Account Manager & Prioritas Bantuan 24/7',
                ],
                'is_active' => true,
                'is_popular' => false,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
