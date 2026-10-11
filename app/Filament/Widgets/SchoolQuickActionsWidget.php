<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class SchoolQuickActionsWidget extends Widget
{
    protected string $view = 'filament.widgets.school-quick-actions-widget';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ! $user->hasRole('super_admin');
    }

    public function getQuickActions(): array
    {
        return [
            [
                'title' => 'Loket Kasir & SPP',
                'description' => 'Terima pembayaran SPP, cicilan biaya, cetak struk kwitansi kasir instan.',
                'url' => '/finance/pos-kasir',
                'btn_text' => 'Buka Loket Kasir',
                'icon' => 'banknotes',
                'color' => 'emerald',
                'badge' => 'Cashless & Tunai',
            ],
            [
                'title' => 'Cetak KTS Digital',
                'description' => 'Generate dan print kartu pelajar ISO CR-80 massal dengan QR Code & barcode.',
                'url' => '/kts/batch-print',
                'btn_text' => 'Cetak Kartu Siswa',
                'icon' => 'identification',
                'color' => 'indigo',
                'badge' => 'Standar ISO',
            ],
            [
                'title' => 'Presensi Face & GPS',
                'description' => 'Layanan presensi mandiri berbasis koordinat geofence sekolah & deteksi selfie.',
                'url' => '/attendance/gps-face',
                'btn_text' => 'Portal Presensi',
                'icon' => 'map-pin',
                'color' => 'rose',
                'badge' => 'Anti-Fake GPS',
            ],
            [
                'title' => 'Tabungan & Dompet Siswa',
                'description' => 'Top-up saldo, atur batas limit jajan harian, dan pantau mutasi tabungan siswa.',
                'url' => '/wallet',
                'btn_text' => 'Kelola Tabungan',
                'icon' => 'wallet',
                'color' => 'amber',
                'badge' => 'Keamanan PIN',
            ],
            [
                'title' => 'Katalog Menu & Stand Kantin',
                'description' => 'Manajemen merchant gerai kantin, daftar harga makanan, dan stok inventaris.',
                'url' => '/admin/canteen-menus',
                'btn_text' => 'Daftar Menu',
                'icon' => 'cake',
                'color' => 'cyan',
                'badge' => 'E-Kantin 4.0',
            ],
            [
                'title' => 'Kelas & Rombel Siswa',
                'description' => 'Distribusi peserta didik ke rombongan belajar, penugasan wali kelas, dan kuota.',
                'url' => '/admin/classrooms',
                'btn_text' => 'Kelola Rombel',
                'icon' => 'academic-cap',
                'color' => 'purple',
                'badge' => 'Akademik',
            ],
        ];
    }
}
