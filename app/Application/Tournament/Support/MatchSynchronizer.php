<?php

declare(strict_types=1);

namespace App\Application\Tournament\Support;

use App\Application\Tournament\RecordMatchResult;
use App\Domain\Tournament\Enum\GameState;
use App\Domain\Tournament\Knockout\Enum\KnockoutGameState;
use App\Domain\Tournament\Knockout\KnockoutEngine;
use App\Domain\Tournament\TournamentEngine;
use App\Models\Matchup;
use App\Models\Tournament;

/**
 * Traduit l'état des moteurs (domaine) en lignes de la table `matches`.
 *
 * Sens unique : le moteur est la source de vérité, Eloquent n'est qu'un miroir.
 * Les résultats (score / séquence) sont posés par {@see RecordMatchResult} ;
 * ce service ne gère que la matérialisation des parties et l'avancement des états.
 */
final class MatchSynchronizer
{
    /**
     * Matérialise les parties de qualification du moteur Swiss : crée les
     * nouvelles, met à jour l'état et le terrain des parties non terminées.
     */
    public function syncQualification(Tournament $tournament, TournamentEngine $engine): void
    {
        $existing = $tournament->matches()
            ->where('phase', 'qualification')
            ->get()
            ->keyBy('engine_game_id');

        foreach ($engine->games() as $game) {
            $status = match ($game->state()) {
                GameState::Pending => 'pending',
                GameState::Playing => 'playing',
                GameState::Finished => 'finished',
            };

            $courtId = $game->courtId() !== null ? (int) $game->courtId()->value : null;

            /** @var Matchup|null $row */
            $row = $existing->get($game->id->value);

            if ($row === null) {
                Matchup::create([
                    'tournament_id' => $tournament->id,
                    'phase' => 'qualification',
                    'engine_game_id' => $game->id->value,
                    'round' => $game->round,
                    'team_a_id' => (int) $game->teamA->value,
                    'team_b_id' => (int) $game->teamB->value,
                    'court_id' => $courtId,
                    'status' => $status,
                ]);

                continue;
            }

            if ($row->status !== 'finished') {
                $row->update(['status' => $status, 'court_id' => $courtId]);
            }
        }
    }

    /**
     * Réplique l'intégralité d'un tableau à élimination directe (division) :
     * crée les parties manquantes et met à jour emplacements, états et vainqueurs
     * au gré de la propagation.
     */
    public function syncKnockout(Tournament $tournament, string $division, KnockoutEngine $engine): void
    {
        $existing = $tournament->matches()
            ->where('phase', 'knockout')
            ->where('division', $division)
            ->get()
            ->keyBy('engine_game_id');

        foreach ($engine->games() as $game) {
            $status = match ($game->state()) {
                KnockoutGameState::Awaiting => 'pending',
                KnockoutGameState::Ready => 'ready',
                KnockoutGameState::Finished => $game->isWalkover() ? 'bye' : 'finished',
            };

            $attributes = [
                'team_a_id' => $game->slotA() !== null ? (int) $game->slotA()->value : null,
                'team_b_id' => $game->slotB() !== null ? (int) $game->slotB()->value : null,
                'winner_team_id' => $game->winner() !== null ? (int) $game->winner()->value : null,
                'score_a' => $game->scoreA(),
                'score_b' => $game->scoreB(),
                'status' => $status,
                'is_walkover' => $game->isWalkover(),
            ];

            /** @var Matchup|null $row */
            $row = $existing->get($game->id);

            if ($row === null) {
                Matchup::create([
                    ...$attributes,
                    'tournament_id' => $tournament->id,
                    'phase' => 'knockout',
                    'engine_game_id' => $game->id,
                    'round' => $game->round,
                    'bracket_index' => $game->index,
                    'division' => $division,
                ]);

                continue;
            }

            $row->update($attributes);
        }
    }
}
