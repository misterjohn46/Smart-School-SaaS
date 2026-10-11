<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\AttendanceDevice;
use App\Models\CanteenOrder;
use App\Models\Payment;
use App\Models\StudentProfile;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SchoolAdminOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        // Tidak ditampilkan untuk Super Admin SaaS
        return ! $user->hasRole('super_admin');
    }

    protected function getStats(): array
    {
        $studentCount = StudentProfile::count();
        $todayAttendanceCount = Attendance::whereDate('date', today())->count();
        $totalPayments = Payment::where('status', 'verified')->sum('amount');
        $totalCanteen = CanteenOrder::where('payment_status', 'paid')->sum('total_amount');
        $rfidDeviceCount = AttendanceDevice::where('is_active', true)->count();

        return [
            Stat::make('Total Siswa Terdaftar', number_format($studentCount, 0, ',', '.'))
                ->description('Database peserta didik sekolah ini')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary')
                ->chart([20, 30, 45, 60, 80, 110, 150]),

            Stat::make('Presensi Hari Ini', number_format($todayAttendanceCount, 0, ',', '.') . ' Tap')
                ->description('Kehadiran RFID tap & GPS geofencing')
                ->descriptionIcon('heroicon-m-clock')
                ->color('success')
                ->chart([10, 25, 40, 55, 70, 90, 100]),

            Stat::make('Penerimaan Kas & SPP', 'Rp ' . number_format((float) $totalPayments, 0, ',', '.'))
                ->description('Total kas SPP & tagihan terverifikasi')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning')
                ->chart([5, 15, 25, 45, 65, 80, 95]),

            Stat::make('Omset E-Kantin Cashless', 'Rp ' . number_format((float) $totalCanteen, 0, ',', '.'))
                ->description('Transaksi dompet digital kartu KTS')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info')
                ->chart([5, 10, 20, 30, 45, 60, 75]),

            Stat::make('Gerbang RFID IoT Aktif', "{$rfidDeviceCount} Unit Online")
                ->description('Mesin tap kartu presensi gerbang sekolah')
                ->descriptionIcon('heroicon-m-wifi')
                ->color('gray'),
        ];
    }
}
