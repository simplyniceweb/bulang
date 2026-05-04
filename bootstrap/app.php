<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\GameMasterMiddleware;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\TellerMiddleware;
use App\Http\Middleware\TrackUserSession;
use App\Providers\EventServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            AuthenticateSession::class,
            TrackUserSession::class,
        ]);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'teller' => TellerMiddleware::class,
            'supervisor' => TellerMiddleware::class,
            'game_master' => GameMasterMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withProviders([
        EventServiceProvider::class,
    ])
    ->create();
