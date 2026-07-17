<?php

declare(strict_types=1);

use App\Domain\Tournament\Entity\Team;
use App\Domain\Tournament\Enum\Division;
use App\Domain\Tournament\Enum\TeamState;
use App\Domain\Tournament\Exception\InvalidTournamentStateException;
use App\Domain\Tournament\ValueObject\GameId;
use App\Domain\Tournament\ValueObject\TeamId;

function makeTeam(string $id = '1', int $seed = 0): Team
{
    return new Team(TeamId::of($id), "Équipe {$id}", $seed);
}

test('une équipe mémorise ses adversaires et son bilan', function () {
    $team = makeTeam('1');
    $opponent = TeamId::of('2');

    expect($team->hasPlayed($opponent))->toBeFalse();

    $team->applyResult(won: true, opponent: $opponent);

    expect($team->hasPlayed($opponent))->toBeTrue()
        ->and($team->wins())->toBe(1)
        ->and($team->losses())->toBe(0)
        ->and($team->gamesPlayed())->toBe(1)
        ->and($team->opponentHistory())->toBe(['2']);
});

test('une défaite incrémente les défaites et l’historique', function () {
    $team = makeTeam('1');
    $team->applyResult(won: false, opponent: TeamId::of('9'));

    expect($team->wins())->toBe(0)
        ->and($team->losses())->toBe(1)
        ->and($team->hasPlayed(TeamId::of('9')))->toBeTrue();
});

test('un exempt compte comme une victoire sans adversaire', function () {
    $team = makeTeam('1');
    $team->awardBye();

    expect($team->wins())->toBe(1)
        ->and($team->byes())->toBe(1)
        ->and($team->gamesPlayed())->toBe(1)
        ->and($team->opponentHistory())->toBe([])
        ->and($team->state())->toBe(TeamState::Waiting);
});

test('les transitions d’état suivent le déroulé', function () {
    $team = makeTeam('1');
    expect($team->state())->toBe(TeamState::Idle);

    $team->markAvailable();
    expect($team->isAvailable())->toBeTrue();

    $team->assignToGame(GameId::of('g1'));
    expect($team->isCovered())->toBeTrue();

    $team->startPlaying();
    expect($team->isPlaying())->toBeTrue();

    $team->qualify(Division::A);
    expect($team->isQualified())->toBeTrue()
        ->and($team->division())->toBe(Division::A);
});

test('jouer sans être couverte est interdit', function () {
    $team = makeTeam('1');
    $team->markAvailable();

    expect(fn () => $team->startPlaying())
        ->toThrow(InvalidTournamentStateException::class);
});
