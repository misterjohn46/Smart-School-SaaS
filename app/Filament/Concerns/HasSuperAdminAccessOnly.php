<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

trait HasSuperAdminAccessOnly
{
    /**
     * Membatasi akses menu khusus Super Administrator SaaS (Platform Level).
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
