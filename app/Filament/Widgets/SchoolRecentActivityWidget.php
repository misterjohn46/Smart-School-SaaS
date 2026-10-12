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

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ! $user->hasRole('super_admin');
    }

    public function getRecentActivities(): array
    {
        // Skema attendances memakai kolom date + time_in/time_out (tidak ada `tap_time`),
        // dan Payment hanya punya relasi studentProfile / user / verifier.
        $attendances = Attendance::with(['user', 'device'])
            ->orderByDesc('date')
            ->orderByDesc('time_in')
            ->limit(5)
            ->get();

        $payments = Payment::with(['studentProfile.user', 'user'])
            ->latest('payment_date')
            ->limit(5)
            ->get();

        return [
            'attendances' => $attendances,
            'payments' => $payments,
        ];
    }
}
