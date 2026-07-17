<?php

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $tournament_id
 * @property string $name
 * @property string|null $follow_token
 * @property int $seed
 * @property string|null $division
 * @property int|null $division_seed
 * @property int|null $final_rank
 * @property-read Tournament $tournament
 * @property-read Collection<int, Player> $players
 */
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'name',
        'follow_token',
        'seed',
        'division',
        'division_seed',
        'final_rank',
    ];

    protected function casts(): array
    {
        return [
            'seed' => 'integer',
            'division_seed' => 'integer',
            'final_rank' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Team $team): void {
            if (empty($team->follow_token)) {
                $team->follow_token = (string) Str::ulid();
            }
        });
    }

    /**
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * @return HasMany<Player, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }
}
