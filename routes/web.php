<?php

use App\Http\Controllers\Organizer\CourtController;
use App\Http\Controllers\Organizer\LiveController;
use App\Http\Controllers\Organizer\RegistrationController as OrganizerRegistrationController;
use App\Http\Controllers\Organizer\TeamController;
use App\Http\Controllers\Organizer\TournamentController;
use App\Http\Controllers\Public\RegistrationController;
use App\Http\Controllers\Public\TeamStatusController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

// Inscription publique (accès par QR / token, sans authentification ni accès admin).
Route::get('/i/{tournament:registration_token}', [RegistrationController::class, 'show'])
    ->name('registration.show');
Route::post('/i/{tournament:registration_token}', [RegistrationController::class, 'store'])
    ->name('registration.store');
Route::get('/inscription/confirmee/{registration:follow_token}', [RegistrationController::class, 'confirmed'])
    ->name('registration.confirmed');
Route::get('/suivi/{registration:follow_token}', [TeamStatusController::class, 'show'])
    ->name('registration.status');

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

        // Ouverture / fermeture des inscriptions.
        Route::patch('tournaments/{tournament}/registrations/open', [TournamentController::class, 'openRegistrations'])
            ->name('tournaments.registrations.open');
        Route::patch('tournaments/{tournament}/registrations/close', [TournamentController::class, 'closeRegistrations'])
            ->name('tournaments.registrations.close');

        // Gestion des inscriptions côté organisateur.
        Route::get('tournaments/{tournament}/registrations', [OrganizerRegistrationController::class, 'index'])
            ->name('tournaments.registrations.index');
        Route::post('tournaments/{tournament}/registrations', [OrganizerRegistrationController::class, 'store'])
            ->name('tournaments.registrations.store');
        Route::post('tournaments/{tournament}/registrations/create-teams', [OrganizerRegistrationController::class, 'createTeams'])
            ->name('tournaments.registrations.create-teams');
        Route::patch('registrations/{registration}/confirm', [OrganizerRegistrationController::class, 'confirm'])
            ->name('registrations.confirm');
        Route::patch('registrations/{registration}/check-in', [OrganizerRegistrationController::class, 'checkIn'])
            ->name('registrations.check-in');
        Route::patch('registrations/{registration}/cancel', [OrganizerRegistrationController::class, 'cancel'])
            ->name('registrations.cancel');

        Route::post('tournaments/{tournament}/courts', [CourtController::class, 'store'])
            ->name('tournaments.courts.store');
        Route::post('tournaments/{tournament}/courts/generate', [CourtController::class, 'generate'])
            ->name('tournaments.courts.generate');
        Route::patch('courts/{court}', [CourtController::class, 'update'])->name('courts.update');
        Route::delete('courts/{court}', [CourtController::class, 'destroy'])->name('courts.destroy');

        Route::delete('teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');

        // Pilotage du déroulé (lancement, résultats, finales).
        Route::get('tournaments/{tournament}/live', [LiveController::class, 'show'])
            ->name('tournaments.live');
        Route::post('tournaments/{tournament}/qualification/start', [LiveController::class, 'startQualification'])
            ->name('tournaments.qualification.start');
        Route::post('tournaments/{tournament}/finals/start', [LiveController::class, 'startFinals'])
            ->name('tournaments.finals.start');
        Route::post('matches/{matchup}/result', [LiveController::class, 'recordResult'])
            ->name('matches.result');
        Route::patch('matches/{matchup}/result', [LiveController::class, 'correctResult'])
            ->name('matches.correct');
    });
});

require __DIR__.'/settings.php';
