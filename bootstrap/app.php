<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Alias middleware spatie/laravel-permission dipakai untuk menjaga
        // seluruh rute modul di routes/web.php berdasarkan peran pengguna.
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $apiError = fn (string $message, int $status): JsonResponse => response()->json([
            'success' => false,
            'data' => null,
            'message' => $message,
        ], $status);

        $exceptions->render(fn (AuthenticationException $exception, Request $request) => $request->is('api/*') ? $apiError('Unauthenticated', 401) : null);
        $exceptions->render(fn (AuthorizationException $exception, Request $request) => $request->is('api/*') ? $apiError('Forbidden', 403) : null);
        $exceptions->render(fn (NotFoundHttpException $exception, Request $request) => $request->is('api/*') ? $apiError('Resource not found', 404) : null);
        $exceptions->render(fn (MethodNotAllowedHttpException $exception, Request $request) => $request->is('api/*') ? $apiError('Method not allowed', 405) : null);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
