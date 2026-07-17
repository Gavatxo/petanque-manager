<?php

declare(strict_types=1);

use App\Application\Tournament\StartQualification;
use App\Enums\RegistrationStatus;
use App\Enums\TeamFormat;
use App\Enums\TournamentStatus;
use App\Models\Court;
use App\Models\Registration;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function doublette(string $name): array
{
    return [
        'team_name' => $name,
        'players' => [
            ['first_name' => 'Prénom', 'last_name' => 'Un'],
            ['first_name' => 'Prénom', 'last_name' => 'Deux'],
        ],
    ];
}

test('scénario complet : concours → inscriptions → présence → équipes → lancement', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create([
        'status' => TournamentStatus::Draft,
        'team_format' => TeamFormat::Doublette,
        'qualifying_rounds' => 3,
        'tableaux_count' => 2,
        'points_target' => 13,
    ]);
    Court::create(['tournament_id' => $tournament->id, 'label' => '1']);
    Court::create(['tournament_id' => $tournament->id, 'label' => '2']);

    // 1. Ouvrir les inscriptions.
    $this->actingAs($user)
        ->patch("/organizer/tournaments/{$tournament->id}/registrations/open")
        ->assertRedirect();
    expect($tournament->fresh()->status)->toBe(TournamentStatus::RegistrationOpen);

    // 2. Inscriptions publiques (sans compte).
    $this->post("/i/{$tournament->registration_token}", doublette('Équipe A'));
    $this->post("/i/{$tournament->registration_token}", doublette('Équipe B'));

    expect($tournament->registrations()->count())->toBe(2)
        ->and(Team::count())->toBe(0);

    // 3. Valider la présence (confirmation puis check-in).
    foreach ($tournament->registrations as $registration) {
        $this->actingAs($user)->patch("/organizer/registrations/{$registration->id}/confirm");
        $this->actingAs($user)->patch("/organizer/registrations/{$registration->id}/check-in");
    }

    expect($tournament->registrations()->where('status', RegistrationStatus::CheckedIn->value)->count())->toBe(2);

    // 4. Créer les équipes officielles.
    $this->actingAs($user)
        ->post("/organizer/tournaments/{$tournament->id}/registrations/create-teams")
        ->assertRedirect();

    expect(Team::count())->toBe(2);
    $team = Team::first();
    expect($team->registration_id)->not->toBeNull()
        ->and($team->players()->count())->toBe(2);

    // 5. Lancer le concours.
    app(StartQualification::class)->handle($tournament->fresh());

    $tournament->refresh();
    expect($tournament->current_phase)->toBe('qualification')
        ->and($tournament->matches()->where('phase', 'qualification')->count())->toBe(1);
});

test('un organisateur confirme, valide la présence et annule une inscription', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create();

    $registration = Registration::factory()->for($tournament)->create();

    $this->actingAs($user)->patch("/organizer/registrations/{$registration->id}/confirm");
    expect($registration->fresh()->status)->toBe(RegistrationStatus::Confirmed);

    $this->actingAs($user)->patch("/organizer/registrations/{$registration->id}/check-in");
    expect($registration->fresh()->status)->toBe(RegistrationStatus::CheckedIn);

    $other = Registration::factory()->for($tournament)->create();
    $this->actingAs($user)->patch("/organizer/registrations/{$other->id}/cancel");
    expect($other->fresh()->status)->toBe(RegistrationStatus::Cancelled);
});

test('la création des équipes est idempotente', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create();
    $registration = Registration::factory()->for($tournament)->checkedIn()->create();
    $registration->players()->create(['first_name' => 'A', 'last_name' => 'B', 'is_captain' => true]);

    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/registrations/create-teams");
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/registrations/create-teams");

    expect(Team::count())->toBe(1);
});

test('seules les présences validées deviennent des équipes', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create();
    Registration::factory()->for($tournament)->create();            // pending
    Registration::factory()->for($tournament)->confirmed()->create(); // confirmed
    Registration::factory()->for($tournament)->checkedIn()->create(); // checked_in -> équipe

    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/registrations/create-teams");

    expect(Team::count())->toBe(1);
});

test('un organisateur ne gère pas les inscriptions d’un autre organisateur', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $tournament = Tournament::factory()->for($owner)->create();
    $registration = Registration::factory()->for($tournament)->create();

    $this->actingAs($other)
        ->patch("/organizer/registrations/{$registration->id}/confirm")
        ->assertForbidden();
});

test('le token public ne donne aucun accès à l’administration', function () {
    $tournament = Tournament::factory()->create(['status' => TournamentStatus::RegistrationOpen]);
    $registration = Registration::factory()->for($tournament)->create();

    // Un visiteur non authentifié est redirigé vers la connexion sur les routes admin.
    $this->patch("/organizer/registrations/{$registration->id}/confirm")->assertRedirect('/login');
    $this->post("/organizer/tournaments/{$tournament->id}/registrations/create-teams")->assertRedirect('/login');
    $this->get("/organizer/tournaments/{$tournament->id}/registrations")->assertRedirect('/login');
});
