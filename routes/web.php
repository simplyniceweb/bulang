<?php

use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\GameMaster\RoundController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified', 'teller'])
    ->prefix('teller')
    ->name('teller.')
    ->group(function () {
        Route::get('/', function () {
            return Inertia::render('Teller/Dashboard');
        })->name('dashboard');
});

Route::middleware(['auth', 'verified', 'game_master'])
    ->prefix('game-master')
    ->name('game_master.')
    ->group(function () {
        Route::get('/', function () {
            return Inertia::render('GameMaster/Dashboard');
        })->name('dashboard');

        Route::post('/round/open', [RoundController::class, 'open'])->name('round.open');

        Route::post('/round/{round}/close-bet', [RoundController::class, 'closeBet'])
            ->name('round.closeBet');

        Route::post('/round/{round}/declare', [RoundController::class, 'declareWinner'])
            ->name('round.declare');

        Route::post('/round/{round}/cancel', [RoundController::class, 'cancel'])
            ->name('round.cancel');
});

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('dashboard');

        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('events', EventController::class)->except(['show']);
});

require __DIR__.'/settings.php';
