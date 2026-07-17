<?php

namespace App\Models;

use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Joueur d'une équipe. Sans compte : accessible uniquement via les QR/tokens.
 *
 * @property int $id
 * @property int $team_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $phone
 * @property string|null $license_number
 * @property bool $is_captain
 * @property-read Team $team
 */
class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    protected $fillable = [
        'team_id',
        'first_name',
        'last_name',
        'phone',
        'license_number',
        'is_captain',
    ];

    protected function casts(): array
    {
        return [
            'is_captain' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
