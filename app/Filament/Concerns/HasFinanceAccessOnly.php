<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

/**
 * Pembatasan akses modul keuangan & tabungan siswa.
 *
 * Dipisahkan dari HasSchoolAccessOnly karena saldo dompet, tagihan, dan pembayaran
 * tidak boleh dapat diubah seluruh pemegang akses panel (mis. operator atau staf TU).
 */
trait HasFinanceAccessOnly
{
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        // Super Admin SaaS tidak mengelola keuangan operasional sekolah.
        if ($user->hasRole('super_admin')) {
            return false;
        }

        return $user->hasAnyRole([
            'admin',
            'kepala_sekolah',
            'bendahara',
        ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
