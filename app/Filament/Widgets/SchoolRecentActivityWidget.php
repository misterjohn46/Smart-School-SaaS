<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Payment;
use Filament\Widgets\Widget;

class SchoolRecentActivityWidget extends Widget
{
    protected string $view = 'filament.widgets.school-recent-activity-widget';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ! $user->hasRole('super_admin');
    }

    public function getRecentActivities(): array
    {
        $attendances = Attendance::with(['user', 'device'])
            ->latest('tap_time')
            ->limit(5)
            ->get();

        $payments = Payment::with(['student', 'collector'])
            ->latest()
            ->limit(5)
            ->get();

        return [
            'attendances' => $attendances,
            'payments' => $payments,
        ];
    }
}
