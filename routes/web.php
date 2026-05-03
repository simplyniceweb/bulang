<?php

use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\GameMaster\DashboardController as GameMasterDashboardController;
use App\Http\Controllers\GameMaster\RoundController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\Teller\BetController;
use App\Http\Controllers\Teller\DashboardController as TellerDashboardController;
use App\Http\Controllers\Teller\TransactionsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomepageController::class, 'index'])->name('home');

Route::get('/unauthorized', function () {
    return Inertia::render('Errors/UnauthorizedTeller');
})->name('teller.unauthorized');

Route::middleware(['auth', 'verified', 'teller'])
    ->prefix('teller')
    ->name('teller.')
    ->group(function () {
        Route::get('/', [TellerDashboardController::class, 'index'])
            ->name('dashboard');

        Route::post('/bet/{event}', [BetController::class, 'bet'])->name('bet.index');
        Route::get('/bet/{code}/verify', [BetController::class, 'verify'])->name('bet.verify');
        Route::post('/bet/{code}/claim', [BetController::class, 'claim'])->name('bet.claim');
        Route::post('/bet/{code}/refund', [BetController::class, 'refund'])->name('bet.refund');
        Route::get('/bet/tickets/{ticket}', [BetController::class, 'reprint'])->name('bet.reprint');

        Route::post('/transactions/adjust-wallet', [TransactionsController::class, 'adjustWallet'])->name('transactions.adjust-wallet');
});

Route::middleware(['auth', 'verified', 'game_master'])
    ->prefix('game-master')
    ->name('game_master.')
    ->group(function () {
        Route::get('/', [GameMasterDashboardController::class, 'index'])
            ->name('dashboard');

        Route::post('/event/halt', [GameMasterDashboardController::class, 'haltEvent'])
            ->name('event.halt');

        Route::post('/round/open', [RoundController::class, 'open'])->name('round.open');

        Route::post('/round/{round}/declare', [RoundController::class, 'declareWinner'])
            ->name('round.declare');

        Route::post('/round/{round}/cancel', [RoundController::class, 'cancel'])
            ->name('round.cancel');

        Route::post('/round/{round}/close-side', [RoundController::class, 'closeSide'])
        ->name('round.closeSide');

        Route::post('/round/{round}/close-global-betting', [RoundController::class, 'closeGlobalBetting'])
        ->name('round.closeGlobalBetting');
});

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('dashboard');

        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('events', EventController::class);
        Route::get('events/teller-audit/{event}', [EventController::class, 'eventTellerAudit'])->name('events.teller.audit');
        Route::get('events/teller/{round}/breakdown', [EventController::class, 'breakdown']);
        Route::get('users/{user}/qr-code', [UserController::class, 'getQrCode'])->name('users.qr-code');
        Route::get('events/{event}/ledger', [EventController::class, 'supervisorLedger'])->name('events.ledger');
        Route::get('events/{event}/teller', [EventController::class, 'tellerLedger'])->name('events.tellers');
        Route::get('events/{event}/teller-ledger/{teller}', [EventController::class, 'tellerLedgerDetails'])->name('teller.ledger.details');
});

require __DIR__.'/settings.php';
