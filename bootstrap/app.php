<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {

        /*
        |--------------------------------------------------------------------------
        | Role Middleware
        |--------------------------------------------------------------------------
        */

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Mode Maintenance (diatur dari menu Settings)
        |--------------------------------------------------------------------------
        */

        $middleware->web(append: [
            \App\Http\Middleware\SiteMaintenance::class,
            \App\Http\Middleware\ExpireUnpaidBookings::class,
            \App\Http\Middleware\AdminSecurity::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Redirect Guest
        |--------------------------------------------------------------------------
        */

        $middleware->redirectGuestsTo(
            fn (Request $request) => route('login')
        );

        /*
        |--------------------------------------------------------------------------
        | Redirect Authenticated User
        |--------------------------------------------------------------------------
        */

        $middleware->redirectUsersTo(function (Request $request) {

            $user = $request->user();

            if ($user && $user->role === 'admin') {
                return route('admin.dashboard');
            }

            return route('dashboard');
        });
    })

    ->withExceptions(function (Exceptions $exceptions): void {

        /*
        |--------------------------------------------------------------------------
        | JSON Response
        |--------------------------------------------------------------------------
        */

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) =>
                $request->is('api/*') ||
                $request->expectsJson(),
        );

        /*
        |--------------------------------------------------------------------------
        | Session Expired / CSRF
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            TokenMismatchException $e,
            Request $request
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sesi Anda telah berakhir. Silakan login kembali.',
                ], 419);
            }

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Sesi Anda telah berakhir. Silakan login kembali.'
                );
        });
    })

    ->create();