<?php

namespace App\Http\Middleware;

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
            \App\Enums\RoleName::Admin->value,
            \App\Enums\RoleName::Headmaster->value,
            \App\Enums\RoleName::Operator->value,
            \App\Enums\RoleName::SuperAdmin->value,
            \App\Enums\RoleName::Treasurer->value,
            \App\Enums\RoleName::Staff->value,
        ]) : false;

        $isTeacher = $user ? (! $isSchoolAdmin && $user->hasRole(\App\Enums\RoleName::Teacher->value)) : false;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), [
                    'roles' => $user->getRoleNames()->toArray(),
                    'isTeacher' => $isTeacher,
                    'isAdmin' => $isSchoolAdmin,
                    'isStudent' => $user->hasRole(\App\Enums\RoleName::Student->value),
                ]) : null,
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
