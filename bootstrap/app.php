<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.resolve' => \App\Http\Middleware\ResolveTenant::class,
            'tenant.active' => \App\Http\Middleware\EnsureTenantActive::class,
            'user.active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
            'anypermission' => \App\Http\Middleware\EnsureUserHasAnyPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('admin/*')) {
                return redirect()->route('admin.login');
            }
            // For tenant routes, redirect to tenant login
            if ($request->is('app/*')) {
                return redirect()->route('tenant.auth.login');
            }

            // Fallback to generic login route
            return redirect()->route('login');
        });
    })->create();
