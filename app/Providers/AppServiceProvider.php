<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Commands\Seed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Netralkan override db:seed dari Stancl Tenancy agar perintah db:seed standar Laravel berjalan normal
        $this->app->bind(Seed::class, function ($app) {
            return new SeedCommand($app['db']);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api-login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('api-register', function (Request $request): Limit {
            return Limit::perMinute(3)->by($request->ip());
        });

        // Endpoint IoT bersifat publik (perangkat ESP32 tidak bisa login).
        // Batasi per token perangkat + IP: satu gerbang sekolah wajar <60 tap/menit,
        // sedangkan percobaan brute force token dari satu IP langsung tertahan.
        RateLimiter::for('iot-device', function (Request $request): Limit {
            $token = (string) $request->header('X-Device-Token', '');

            return Limit::perMinute(60)->by(sha1($token).'|'.$request->ip());
        });

        // Aksi dompet siswa memakai PIN 6 digit; tanpa throttle PIN dapat di-brute force.
        RateLimiter::for('wallet-pin', function (Request $request): Limit {
            return Limit::perMinute(5)->by(($request->user()?->id ?? $request->ip()).'|wallet-pin');
        });

        Vite::prefetch(concurrency: 3);

        // Pulihkan perintah db:seed asli Laravel dari override Stancl Tenancy
        if ($this->app->runningInConsole()) {
            $this->commands([
                SeedCommand::class,
            ]);
        }
    }
}
