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

        // Daftar ini sengaja identik dengan User::canAccessPanel() dikurangi
        // super_admin. Sebelumnya trait ini mengizinkan guru, pustakawan, dan
        // kantin yang justru tidak boleh masuk panel sama sekali - selisih dua
        // daftar itu membuat satu perubahan kecil pada canAccessPanel() langsung
        // membuka seluruh modul operasional.
        return $user->hasAnyRole([
            'admin',
            'kepala_sekolah',
            'bendahara',
            'staff',
            'operator',
        ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
