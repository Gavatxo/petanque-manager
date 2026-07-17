<?php

use App\Http\Controllers\Organizer\CourtController;
use App\Http\Controllers\Organizer\TeamController;
use App\Http\Controllers\Organizer\TournamentController;
use App\Http\Controllers\Public\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

// Inscription publique (accès par QR / token, sans authentification).
Route::get('/i/{tournament:registration_token}', [RegistrationController::class, 'show'])
    ->name('registration.show');
Route::post('/i/{tournament:registration_token}', [RegistrationController::class, 'store'])
    ->name('registration.store');
Route::get('/inscription/confirmee/{team:follow_token}', [RegistrationController::class, 'confirmed'])
    ->name('registration.confirmed');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::prefix('organizer')->name('organizer.')->group(function () {
        Route::resource('tournaments', TournamentController::class);
        Route::get('tournaments/{tournament}/qr', [TournamentController::class, 'qr'])
            ->name('tournaments.qr');

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

        Route::delete('teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
    });
});

require __DIR__.'/settings.php';
