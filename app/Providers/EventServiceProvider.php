<?php

namespace App\Providers;

use Illuminate\Auth\Events\Logout;
use App\Listeners\ClearUserSession;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Logout::class => [
            ClearUserSession::class,
        ],
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
