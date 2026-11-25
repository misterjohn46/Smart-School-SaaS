<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $isSchoolAdmin = $user ? $user->hasAnyRole([
            RoleName::Admin->value,
            RoleName::Headmaster->value,
            RoleName::Operator->value,
            RoleName::SuperAdmin->value,
            RoleName::Treasurer->value,
            RoleName::Staff->value,
        ]) : false;

        $isTeacher = $user ? (! $isSchoolAdmin && $user->hasRole(RoleName::Teacher->value)) : false;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), [
                    'roles' => $user->getRoleNames()->toArray(),
                    'isTeacher' => $isTeacher,
                    'isAdmin' => $isSchoolAdmin,
                    'isStudent' => $user->hasRole(RoleName::Student->value),
                ]) : null,
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
