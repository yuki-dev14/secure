<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnforcePasswordChange;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Inertia SSR / share middleware
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Redirect unauthenticated users to staff login (fixes "Route [login] not defined" error)
        $middleware->redirectGuestsTo(fn () => route('staff.login'));

        // Named middleware aliases
        $middleware->alias([
            'role'                   => CheckRole::class,
            'enforce.password.change'=> EnforcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
