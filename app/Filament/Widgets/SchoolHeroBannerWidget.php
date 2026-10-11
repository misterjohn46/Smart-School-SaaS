<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\AcademicYear;
use App\Models\Tenant;
use Filament\Widgets\Widget;

class SchoolHeroBannerWidget extends Widget
{
    protected string $view = 'filament.widgets.school-hero-banner-widget';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ! $user->hasRole('super_admin');
    }

    public function getSchoolData(): array
    {
        $user = auth()->user();
        $tenant = Tenant::where('admin_email', $user?->email)->first();
        $activeYear = AcademicYear::where('is_active', true)->first();

        return [
            'school_name' => $tenant?->school_name ?? 'MTs Zainul Hasan 2',
            'npsn' => $tenant?->npsn ?? '69853367',
            'admin_name' => $user?->name ?? 'Administrator',
            'academic_year' => $activeYear ? "{$activeYear->name} ({$activeYear->semester})" : '2026/2027 Ganjil',
            'today_formatted' => now()->translatedFormat('l, d F Y'),
        ];
    }
}
