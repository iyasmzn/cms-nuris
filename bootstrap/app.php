<?php

use App\Http\Middleware\EnsureFeatureEnabled;
use App\Support\MetaPixel;
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
        $middleware->alias([
            'feature' => EnsureFeatureEnabled::class,
        ]);

        // Meta's own cookies are set by the pixel script, never by Laravel, so
        // decrypting them would only blank them out before they can be read.
        $middleware->encryptCookies(except: array_values(MetaPixel::FORWARDED_COOKIES));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
