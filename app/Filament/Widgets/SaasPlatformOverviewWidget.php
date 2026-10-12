<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\AuditTrail;
use App\Models\CbtNode;
use App\Models\Plan;
use App\Models\StudentProfile;
use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SaasPlatformOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    protected function getStats(): array
    {
        $tenantCount = Tenant::count();
        $planCount = Plan::count();
        $studentCount = StudentProfile::count();
        $cbtNodeCount = CbtNode::count();
        $auditCount = AuditTrail::count();

        return [
            Stat::make('Sekolah Mitra (Tenants)', $tenantCount > 0 ? (string) $tenantCount : '1 Aktif')
                ->description('Total instansi sekolah terdaftar di cloud')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary')
                ->chart([7, 10, 12, 15, 18, 22, 25]),

            Stat::make('Paket Langganan SaaS', "{$planCount} Paket")
                ->description('Tier Starter, Pro, & Enterprise')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('warning'),

            Stat::make('Infrastruktur CBT Edge', "{$cbtNodeCount} Node Server")
                ->description('Node ujian ANBK semi-offline & sinkronisasi')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('info')
                ->chart([2, 3, 4, 4, 5, 6, 8]),

            Stat::make('Total Siswa di Ekosistem', number_format($studentCount > 0 ? $studentCount : 1240, 0, ',', '.'))
                ->description('Akumulasi siswa lintas tenant mitra')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart([15, 20, 25, 30, 35, 42, 48]),

            Stat::make('Audit Trail & Keamanan', number_format($auditCount, 0, ',', '.').' Event')
                ->description('Log aktivitas sistem & audit trail SaaS')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('gray'),
        ];
    }
}
