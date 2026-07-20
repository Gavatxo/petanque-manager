<?php

declare(strict_types=1);

namespace App\Domain\Tournament;

use App\Domain\Tournament\Configuration\TournamentConfiguration;
use App\Domain\Tournament\Entity\Court;
use App\Domain\Tournament\Entity\Game;
use App\Domain\Tournament\Entity\Team;
use App\Domain\Tournament\Enum\Division;
use App\Domain\Tournament\Enum\TeamState;
use App\Domain\Tournament\Enum\TournamentPhase;
use App\Domain\Tournament\Exception\InvalidTournamentStateException;
use App\Domain\Tournament\Exception\PairingDeadlockException;
use App\Domain\Tournament\Pairing\Matchmaker;
use App\Domain\Tournament\ValueObject\CourtId;
use App\Domain\Tournament\ValueObject\GameId;
use App\Domain\Tournament\ValueObject\TeamId;

/**
 * Moteur d'un concours libre de pétanque, indépendant de toute infrastructure.
 *
 * Appariement progressif « au fil de l'eau » : dès qu'une partie se termine, les
 * deux équipes sont réappariées avec d'autres équipes de même bilan
 * (victoires/défaites) jamais rencontrées — gagnants contre gagnants, perdants
 * contre perdants — sans attendre la fin de la ronde. Une équipe sans adversaire
 * de même bilan patiente ; en cas de blocage (plus aucune partie en cours), on
 * apparie au plus proche bilan, quitte à exempter (jamais de revanche). Les
 * terrains sont une ressource rare attribuée dynamiquement : une paire sans
 * terrain reste « couverte » jusqu'à en obtenir un.
 */
final class TournamentEngine
{
    private TournamentPhase $phase = TournamentPhase::Setup;

    private int $currentRound = 0;

    private int $gameSequence = 0;

    /** @var array<string, Team> */
    private array $teams = [];

    /** @var array<string, Court> */
    private array $courts = [];

    /** @var array<string, Game> */
    private array $games = [];

    public function __construct(
        private readonly TournamentConfiguration $config,
        private readonly Matchmaker $matchmaker = new Matchmaker,
    ) {}

    // ---------------------------------------------------------------------
    // Configuration (avant démarrage)
    // ---------------------------------------------------------------------

    public function registerTeam(string $id, string $name, ?int $seed = null): Team
    {
        $this->assertSetup('inscrire une équipe');

        if (isset($this->teams[$id])) {
            throw InvalidTournamentStateException::because("L'équipe {$id} est déjà inscrite.");
        }

        $team = new Team(TeamId::of($id), $name, $seed ?? count($this->teams));
        $this->teams[$id] = $team;

        return $team;
    }

    public function addCourt(string $id, string $label): Court
    {
        $this->assertSetup('ajouter un terrain');

        if (isset($this->courts[$id])) {
            throw InvalidTournamentStateException::because("Le terrain {$id} existe déjà.");
        }

        $court = new Court(CourtId::of($id), $label);
        $this->courts[$id] = $court;

        return $court;
    }

    // ---------------------------------------------------------------------
    // Déroulé
    // ---------------------------------------------------------------------

    public function start(): void
    {
        $this->assertSetup('démarrer le concours');

        if (count($this->teams) < 2) {
            throw InvalidTournamentStateException::because('Au moins deux équipes sont nécessaires.');
        }

        // Les terrains sont optionnels : sans terrain numéroté, les parties se
        // jouent sans emplacement attribué (voir assignCourts()).

        $this->phase = TournamentPhase::Qualification;
        $this->currentRound = 1;

        foreach ($this->teams as $team) {
            $team->markAvailable();
        }

        $this->pairCurrentRound();
    }

    /**
     * Saisit le résultat d'une partie en cours (score gagnant = {@see TournamentConfiguration::$pointsTarget}).
     * Libère le terrain, met à jour les bilans, puis fait progresser le concours.
     */
    public function recordResult(string $gameId, int $scoreA, int $scoreB): void
    {
        $game = $this->games[$gameId]
            ?? throw InvalidTournamentStateException::because("Partie inconnue : {$gameId}.");

        if (! $game->isPlaying()) {
            throw InvalidTournamentStateException::because("La partie {$gameId} n'est pas en cours.");
        }

        $this->assertValidScore($scoreA, $scoreB);

        $game->recordScore($scoreA, $scoreB);

        $teamA = $this->team($game->teamA);
        $teamB = $this->team($game->teamB);
        $winner = $game->winner();

        $teamA->applyResult($winner !== null && $winner->equals($teamA->id), $teamB->id);
        $teamB->applyResult($winner !== null && $winner->equals($teamB->id), $teamA->id);

        $courtId = $game->courtId();
        if ($courtId !== null) {
            $this->courts[$courtId->value]->release();
        }

        // Un terrain vient de se libérer : on couvre une partie en attente.
        $this->assignCourts();

        $this->advance();
    }

    // ---------------------------------------------------------------------
    // Appariement & terrains (automatique)
    // ---------------------------------------------------------------------

    private function pairCurrentRound(): void
    {
        $available = array_values(array_filter(
            $this->teams,
            static fn (Team $team): bool => $team->isAvailable(),
        ));

        if ($available === []) {
            return;
        }

        $result = $this->matchmaker->pair($available);

        if ($result->byeTeam !== null) {
            $this->team($result->byeTeam)->awardBye();
        }

        foreach ($result->pairs as $pair) {
            $id = 'g'.(++$this->gameSequence);
            $game = new Game(GameId::of($id), $this->currentRound, $pair->teamA, $pair->teamB);
            $this->games[$id] = $game;

            $this->team($pair->teamA)->assignToGame($game->id);
            $this->team($pair->teamB)->assignToGame($game->id);
        }

        $this->assignCourts();
    }

    /**
     * Attribue les terrains libres aux parties couvertes (ordre de création),
     * dans la limite des terrains disponibles.
     */
    private function assignCourts(): void
    {
        // Concours sans terrain numéroté : toute partie couverte démarre
        // immédiatement, sans emplacement (parties en parallèle, tous tours
        // confondus — l'appariement est progressif).
        if ($this->courts === []) {
            foreach ($this->games as $game) {
                if ($game->isPending()) {
                    $game->startWithoutCourt();
                    $this->team($game->teamA)->startPlaying();
                    $this->team($game->teamB)->startPlaying();
                }
            }

            return;
        }

        $freeCourts = array_values(array_filter(
            $this->courts,
            static fn (Court $court): bool => $court->isAvailable(),
        ));

        if ($freeCourts === []) {
            return;
        }

        foreach ($this->games as $game) {
            if (! $game->isPending()) {
                continue;
            }

            $court = array_shift($freeCourts);
            if ($court === null) {
                return;
            }

            $game->assignCourt($court->id);
            $court->occupy($game->id);
            $this->team($game->teamA)->startPlaying();
            $this->team($game->teamB)->startPlaying();
        }
    }

    /**
     * Fait progresser le concours après chaque résultat, sans attendre la fin de
     * la ronde : les équipes qui viennent de finir sont réappariées « au fil de
     * l'eau » avec une autre équipe de même bilan (victoires/défaites) jamais
     * rencontrée — gagnants contre gagnants, perdants contre perdants. Faute
     * d'adversaire de même bilan disponible, une équipe patiente. En cas de
     * blocage (plus aucune partie en cours mais des équipes doivent encore
     * jouer), on apparie au plus proche bilan, quitte à exempter.
     */
    private function advance(): void
    {
        $guard = 0;

        while (true) {
            if (++$guard > 1_000_000) {
                throw InvalidTournamentStateException::because('Progression emballée : arrêt de sécurité.');
            }

            $this->qualifyCompletedTeams();

            if ($this->pairSameRecordAvailable()) {
                $this->assignCourts();

                continue;
            }

            // Des parties tournent encore : on attend les prochains résultats.
            if ($this->hasActiveGames()) {
                break;
            }

            // Blocage : plus rien ne tourne mais des équipes doivent jouer.
            if (! $this->pairRemaining()) {
                break;
            }

            $this->assignCourts();
        }

        if ($this->allTeamsQualified()) {
            $this->phase = TournamentPhase::Completed;
        }
    }

    /** Fige le tableau des équipes ayant joué toutes leurs parties. */
    private function qualifyCompletedTeams(): void
    {
        foreach ($this->teams as $team) {
            if ($team->isQualified()) {
                continue;
            }

            if (($team->isAvailable() || $team->isWaiting())
                && $team->gamesPlayed() >= $this->config->qualifyingRounds) {
                $team->qualify($this->config->divisionRule->divisionFor(
                    $team->wins(),
                    $this->config->qualifyingRounds,
                    $this->config->divisionCount,
                ));
            }
        }
    }

    /**
     * Apparie les équipes disponibles de même bilan, sans revanche.
     *
     * @return bool vrai si au moins une paire a été créée
     */
    private function pairSameRecordAvailable(): bool
    {
        /** @var array<string, list<Team>> $groups */
        $groups = [];

        foreach ($this->teams as $team) {
            if ($team->isAvailable() && $team->gamesPlayed() < $this->config->qualifyingRounds) {
                $groups[$team->wins().'-'.$team->losses()][] = $team;
            }
        }

        // Ordre déterministe (bilan décroissant) : identifiants de partie stables.
        uksort($groups, static function (string $a, string $b): int {
            $winsA = (int) explode('-', $a)[0];
            $winsB = (int) explode('-', $b)[0];

            return $winsB <=> $winsA ?: strcmp($a, $b);
        });

        $made = false;

        foreach ($groups as $group) {
            if (count($group) < 2) {
                continue;
            }

            foreach ($this->matchmaker->progressivePairs($group) as $pair) {
                $this->createGame($pair->teamA, $pair->teamB);
                $made = true;
            }
        }

        return $made;
    }

    /**
     * Débloque un concours à l'arrêt : apparie au plus proche bilan (Swiss
     * classique, sans revanche) les équipes qui doivent encore jouer, exemptant
     * si nécessaire.
     *
     * @return bool vrai si une paire a été créée ou une équipe exemptée
     */
    private function pairRemaining(): bool
    {
        $teams = array_values(array_filter(
            $this->teams,
            fn (Team $team): bool => ($team->isAvailable() || $team->isWaiting())
                && $team->gamesPlayed() < $this->config->qualifyingRounds,
        ));

        if ($teams === []) {
            return false;
        }

        try {
            $result = $this->matchmaker->pair($teams);
        } catch (PairingDeadlockException) {
            // Aucun appariement sans revanche : on exempte la plus faible.
            $this->weakest($teams)->awardBye();

            return true;
        }

        if ($result->byeTeam !== null) {
            $this->team($result->byeTeam)->awardBye();
        }

        foreach ($result->pairs as $pair) {
            $this->createGame($pair->teamA, $pair->teamB);
        }

        return $result->pairs !== [] || $result->byeTeam !== null;
    }

    private function createGame(TeamId $a, TeamId $b): void
    {
        $round = max($this->team($a)->gamesPlayed(), $this->team($b)->gamesPlayed()) + 1;
        $this->currentRound = max($this->currentRound, $round);

        $id = 'g'.(++$this->gameSequence);
        $game = new Game(GameId::of($id), $round, $a, $b);
        $this->games[$id] = $game;

        $this->team($a)->assignToGame($game->id);
        $this->team($b)->assignToGame($game->id);
    }

    /**
     * @param  list<Team>  $teams
     */
    private function weakest(array $teams): Team
    {
        $ranked = $teams;

        usort(
            $ranked,
            static fn (Team $a, Team $b): int => $a->byes() <=> $b->byes()
                ?: $a->wins() <=> $b->wins()
                ?: $b->seed <=> $a->seed,
        );

        return $ranked[0];
    }

    private function hasActiveGames(): bool
    {
        foreach ($this->games as $game) {
            if ($game->isPending() || $game->isPlaying()) {
                return true;
            }
        }

        return false;
    }

    private function allTeamsQualified(): bool
    {
        foreach ($this->teams as $team) {
            if (! $team->isQualified()) {
                return false;
            }
        }

        return true;
    }

    // ---------------------------------------------------------------------
    // Consultation
    // ---------------------------------------------------------------------

    public function configuration(): TournamentConfiguration
    {
        return $this->config;
    }

    public function phase(): TournamentPhase
    {
        return $this->phase;
    }

    public function currentRound(): int
    {
        return $this->currentRound;
    }

    public function isCompleted(): bool
    {
        return $this->phase === TournamentPhase::Completed;
    }

    public function team(TeamId $id): Team
    {
        return $this->teams[$id->value]
            ?? throw InvalidTournamentStateException::because("Équipe inconnue : {$id->value}.");
    }

    /** @return list<Team> */
    public function teams(): array
    {
        return array_values($this->teams);
    }

    /** @return list<Court> */
    public function courts(): array
    {
        return array_values($this->courts);
    }

    /** @return list<Game> */
    public function games(): array
    {
        return array_values($this->games);
    }

    /**
     * Équipes disponibles (ont fini leur partie, prêtes pour la ronde suivante).
     *
     * @return list<Team>
     */
    public function availableTeams(): array
    {
        return $this->teamsInState(TeamState::Available);
    }

    /**
     * Équipes en attente (exemptées : aucun adversaire ce tour).
     *
     * @return list<Team>
     */
    public function waitingTeams(): array
    {
        return $this->teamsInState(TeamState::Waiting);
    }

    /**
     * Équipes couvertes (appariées, en attente d'un terrain).
     *
     * @return list<Team>
     */
    public function coveredTeams(): array
    {
        return $this->teamsInState(TeamState::Covered);
    }

    /**
     * Équipes en jeu (sur un terrain).
     *
     * @return list<Team>
     */
    public function playingTeams(): array
    {
        return $this->teamsInState(TeamState::Playing);
    }

    /** @return list<Court> Terrains disponibles. */
    public function availableCourts(): array
    {
        return array_values(array_filter(
            $this->courts,
            static fn (Court $court): bool => $court->isAvailable(),
        ));
    }

    /** @return list<Game> Parties en cours (sur un terrain). */
    public function playingGames(): array
    {
        return array_values(array_filter(
            $this->games,
            static fn (Game $game): bool => $game->isPlaying(),
        ));
    }

    /** @return list<Game> Parties couvertes en attente d'un terrain. */
    public function pendingGames(): array
    {
        return array_values(array_filter(
            $this->games,
            static fn (Game $game): bool => $game->isPending(),
        ));
    }

    /**
     * Équipes réparties par tableau, une fois les qualifications terminées.
     *
     * @return array<string, list<Team>>
     */
    public function divisions(): array
    {
        $divisions = [];

        foreach (Division::cases() as $division) {
            if ($division->index() < $this->config->divisionCount) {
                $divisions[$division->value] = [];
            }
        }

        foreach ($this->teams as $team) {
            $division = $team->division();
            if ($division !== null) {
                $divisions[$division->value][] = $team;
            }
        }

        return $divisions;
    }

    /**
     * Classement : bilan décroissant (victoires, puis moins de défaites, puis seed).
     *
     * @return list<Team>
     */
    public function standings(): array
    {
        $teams = array_values($this->teams);

        usort(
            $teams,
            static fn (Team $a, Team $b): int => $b->wins() <=> $a->wins()
                ?: $a->losses() <=> $b->losses()
                ?: $a->seed <=> $b->seed,
        );

        return $teams;
    }

    // ---------------------------------------------------------------------
    // Interne
    // ---------------------------------------------------------------------

    /** @return list<Team> */
    private function teamsInState(TeamState $state): array
    {
        return array_values(array_filter(
            $this->teams,
            static fn (Team $team): bool => $team->state() === $state,
        ));
    }

    private function assertSetup(string $action): void
    {
        if ($this->phase !== TournamentPhase::Setup) {
            throw InvalidTournamentStateException::because(
                "Impossible de {$action} : le concours est déjà lancé.",
            );
        }
    }

    private function assertValidScore(int $scoreA, int $scoreB): void
    {
        if ($scoreA === $scoreB) {
            throw InvalidTournamentStateException::because('Une partie ne peut se terminer sur une égalité.');
        }

        if ($scoreA < 0 || $scoreB < 0) {
            throw InvalidTournamentStateException::because('Les scores ne peuvent pas être négatifs.');
        }

        if (max($scoreA, $scoreB) !== $this->config->pointsTarget) {
            throw InvalidTournamentStateException::because(
                "Le vainqueur doit atteindre {$this->config->pointsTarget} points.",
            );
        }
    }
}
