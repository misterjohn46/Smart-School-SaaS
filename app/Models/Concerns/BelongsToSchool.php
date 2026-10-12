<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Stancl\Tenancy\Database\TenantScope;

/**
 * Menandai model sebagai milik satu sekolah (tenant) pada arsitektur
 * single-database multi-tenancy.
 *
 * Trait ini memakai BelongsToTenant milik stancl/tenancy yang menyediakan:
 *  - relasi `tenant()`
 *  - global scope TenantScope: menyaring `tenant_id` HANYA ketika tenancy sudah
 *    diinisialisasi. Pada konteks central (landing page, panel Super Admin,
 *    perintah artisan, dan test) tidak ada penyaringan sama sekali.
 *  - pengisian `tenant_id` otomatis saat record dibuat.
 *
 * Tambahan di sini memperketat dua celah yang tidak ditutup trait vendor:
 *  1. `tenant_id` dipaksa mengikuti tenant aktif saat create, sehingga nilai yang
 *     diselipkan lewat mass assignment tidak dapat menitipkan data ke sekolah lain.
 *  2. `tenant_id` tidak boleh berubah saat update dari dalam konteks tenant.
 */
trait BelongsToSchool
{
    use BelongsToTenant;

    /**
     * Query tanpa pembatasan tenant.
     *
     * TenantScope mendaftarkan makro `withoutTenancy()` pada query builder, tetapi
     * makro tidak terlihat oleh static analysis. Metode ini memberi tanda tangan
     * eksplisit sekaligus titik tunggal untuk ditinjau: setiap pemanggilannya
     * berarti sengaja menembus isolasi antar sekolah (mis. provisioning tenant
     * baru atau aksi Super Admin dari central domain).
     *
     * @return Builder<static>
     */
    public static function withoutTenancy(): Builder
    {
        return static::query()->withoutGlobalScope(TenantScope::class);
    }

    public static function bootBelongsToSchool(): void
    {
        static::creating(function ($model): void {
            if (tenancy()->initialized) {
                $model->setAttribute(BelongsToTenant::$tenantIdColumn, tenant()->getTenantKey());
            }
        });

        static::updating(function ($model): void {
            if (! tenancy()->initialized) {
                return;
            }

            if ($model->isDirty(BelongsToTenant::$tenantIdColumn)) {
                $model->setAttribute(
                    BelongsToTenant::$tenantIdColumn,
                    $model->getOriginal(BelongsToTenant::$tenantIdColumn)
                );
            }
        });
    }
}
