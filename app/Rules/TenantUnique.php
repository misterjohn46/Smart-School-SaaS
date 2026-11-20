<?php

declare(strict_types=1);

namespace App\Rules;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Aturan validasi `unique` yang dibatasi tenant aktif.
 *
 * Diperlukan karena aturan `unique` bawaan Laravel mengeksekusi query lewat query
 * builder, bukan Eloquent, sehingga global scope TenantScope tidak ikut berlaku.
 * Tanpa pembatas ini, sekolah B akan ditolak saat memakai kode / NISN / username
 * yang sudah dipakai sekolah A.
 */
final class TenantUnique
{
    public static function make(string $table, string $column = 'NULL', int|string|null $ignoreId = null): Unique
    {
        $tenantKey = tenancy()->initialized ? tenant()->getTenantKey() : null;

        $rule = Rule::unique($table, $column)->where(
            static fn (Builder $query): Builder => $tenantKey === null
                ? $query->whereNull('tenant_id')
                : $query->where('tenant_id', $tenantKey)
        );

        return $ignoreId !== null ? $rule->ignore($ignoreId) : $rule;
    }

    /**
     * Pembatas tenant untuk validasi keunikan data per sekolah.
     */
    public static function constrain(Unique $rule): Unique
    {
        $tenantKey = tenancy()->initialized ? tenant()->getTenantKey() : null;

        return $rule->where(
            static fn (Builder $query): Builder => $tenantKey === null
                ? $query->whereNull('tenant_id')
                : $query->where('tenant_id', $tenantKey)
        );
    }
}
