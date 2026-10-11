<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

trait HasSchoolAccessOnly
{
    /**
     * Membatasi akses menu operasional sekolah agar tidak tampil untuk Super Admin SaaS.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        // Super Administrator SaaS fokus pada platform & tenant, tidak mengelola operasional harian sekolah
        if ($user->hasRole('super_admin')) {
            return false;
        }

        return $user->hasAnyRole([
            'admin',
            'kepala_sekolah',
            'bendahara',
            'staff',
            'operator',
            'guru',
            'pustakawan',
            'kantin',
        ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
