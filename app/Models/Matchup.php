<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une rencontre persistée (qualification ou phase finale).
 *
 * Modèle purement passif : aucun calcul d'appariement ici — c'est le rôle des
 * moteurs du domaine, pilotés par les services applicatifs.
 *
 * @property int $id
 * @property int $tournament_id
 * @property string $phase
 * @property string $engine_game_id
 * @property int $round
 * @property string|null $division
 * @property int|null $bracket_index
 * @property int|null $team_a_id
 * @property int|null $team_b_id
 * @property int|null $court_id
 * @property int|null $score_a
 * @property int|null $score_b
 * @property int|null $winner_team_id
 * @property string $status
 * @property bool $is_walkover
 * @property bool $is_forfeit
 * @property int|null $result_sequence
 * @property-read Tournament $tournament
 * @property-read Team|null $teamA
 * @property-read Team|null $teamB
 * @property-read Team|null $winner
 * @property-read Court|null $court
 */
class Matchup extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'tournament_id',
        'phase',
        'engine_game_id',
        'round',
        'division',
        'bracket_index',
        'team_a_id',
        'team_b_id',
        'court_id',
        'score_a',
        'score_b',
        'winner_team_id',
        'status',
        'is_walkover',
        'is_forfeit',
        'result_sequence',
    ];

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'bracket_index' => 'integer',
            'score_a' => 'integer',
            'score_b' => 'integer',
            'is_walkover' => 'boolean',
            'is_forfeit' => 'boolean',
            'result_sequence' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function teamA(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_a_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function teamB(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_b_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }

    /**
     * @return BelongsTo<Court, $this>
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
