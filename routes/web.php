<?php

use App\Http\Controllers\Organizer\CourtController;
use App\Http\Controllers\Organizer\TournamentController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::prefix('organizer')->name('organizer.')->group(function () {
        Route::resource('tournaments', TournamentController::class);

        Route::patch('tournaments/{tournament}/archive', [TournamentController::class, 'archive'])
            ->name('tournaments.archive');
        Route::patch('tournaments/{tournament}/unarchive', [TournamentController::class, 'unarchive'])
            ->name('tournaments.unarchive');

        Route::post('tournaments/{tournament}/courts', [CourtController::class, 'store'])
            ->name('tournaments.courts.store');
        Route::post('tournaments/{tournament}/courts/generate', [CourtController::class, 'generate'])
            ->name('tournaments.courts.generate');
        Route::patch('courts/{court}', [CourtController::class, 'update'])->name('courts.update');
        Route::delete('courts/{court}', [CourtController::class, 'destroy'])->name('courts.destroy');
    });
});

require __DIR__.'/settings.php';
