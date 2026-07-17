<?php

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tournament_id
 * @property string $name
 * @property int $seed
 * @property string|null $division
 * @property int|null $division_seed
 * @property int|null $final_rank
 * @property-read Tournament $tournament
 */
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'name',
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

    /**
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
}
