<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

/**
 * Inisialisasi tenant berdasarkan domain, kecuali untuk central domain.
 *
 * Aplikasi memakai satu database bersama (single-database multi-tenancy):
 *  - Pada central domain (lihat CENTRAL_DOMAINS) tenancy TIDAK diinisialisasi,
 *    sehingga landing page SaaS, pendaftaran sekolah, dan panel Super Admin
 *    melihat seluruh data platform.
 *  - Pada domain sekolah, tenant diinisialisasi sehingga global scope
 *    Stancl\Tenancy\Database\TenantScope membatasi setiap query ke sekolah itu.
 *
 * Middleware ini harus berjalan SEBELUM autentikasi diselesaikan, karena model
 * User juga tenant-scoped: pengguna sekolah lain tidak boleh dapat diambil
 * dari dalam konteks sekolah ini.
 */
class InitializeTenancyUnlessCentralDomain extends InitializeTenancyByDomain
{
    public function handle($request, Closure $next)
    {
        if ($this->isCentralDomain($request)) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    protected function isCentralDomain(Request $request): bool
    {
        $centralDomains = array_map(
            static fn ($domain): string => mb_strtolower((string) $domain),
            (array) config('tenancy.central_domains', [])
        );

        return in_array(mb_strtolower($request->getHost()), $centralDomains, true);
    }
}
