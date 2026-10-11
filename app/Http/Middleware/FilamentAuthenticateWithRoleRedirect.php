<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\RoleName;
use Closure;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class FilamentAuthenticateWithRoleRedirect extends Middleware
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            return redirect()->guest(Filament::getLoginUrl() ?? route('login'));
        }

        $user = $guard->user();
        $panel = Filament::getCurrentOrDefaultPanel();

        // Jika user tidak memiliki hak akses ke panel admin, jangan tampilkan 403 kasar,
        // alihkan secara anggun ke portal yang sesuai dengan perannya.
        if ($user instanceof FilamentUser && ! $user->canAccessPanel($panel)) {
            if (method_exists($user, 'hasRole')) {
                if ($user->hasRole(RoleName::Student->value)) {
                    return redirect()->route('portal.student');
                }
                if ($user->hasRole(RoleName::Parent->value)) {
                    return redirect()->route('portal.parent');
                }
            }

            return redirect()->route('dashboard');
        }

        return parent::handle($request, $next, ...$guards);
    }
}
