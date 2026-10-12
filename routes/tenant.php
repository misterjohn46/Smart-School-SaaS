<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Hanya memuat halaman akar domain sekolah. Seluruh rute aplikasi lain berada
| di routes/web.php dan sudah tenant-aware karena grup middleware `web`
| memasang App\Http\Middleware\InitializeTenancyUnlessCentralDomain.
|
| Rute `/` di routes/web.php dibatasi Route::domain(central_domains), sehingga
| akar domain sekolah ditangani di sini.
|
*/

Route::middleware(['web', PreventAccessFromCentralDomains::class])->group(function () {
    Route::get('/', function () {
        return Auth::check()
            ? redirect()->route('dashboard')
            : redirect()->route('login');
    })->name('tenant.home');
});
