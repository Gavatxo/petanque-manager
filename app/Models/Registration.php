<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Demande d'inscription publique à un concours. Distincte de l'équipe officielle :
 * l'équipe ({@see Team}) n'est créée qu'après validation de la présence.
 *
 * @property int $id
 * @property int $tournament_id
 * @property string|null $team_name
 * @property int|null $number
 * @property string $follow_token
 * @property RegistrationStatus $status
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $checked_in_at
 * @property Carbon|null $cancelled_at
 * @property-read Tournament $tournament
 * @property-read Collection<int, RegistrationPlayer> $players
 * @property-read Team|null $team
 */
class Registration extends Model
{
    /** @use HasFactory<RegistrationFactory> */
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'team_name',
        'number',
        'follow_token',
        'status',
        'confirmed_at',
        'checked_in_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'number' => 'integer',
            'confirmed_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Registration $registration): void {
            if (empty($registration->follow_token)) {
                $registration->follow_token = (string) Str::ulid();
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
     * @return HasMany<RegistrationPlayer, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(RegistrationPlayer::class);
    }

    /**
     * @return HasOne<Team, $this>
     */
    public function team(): HasOne
    {
        return $this->hasOne(Team::class);
    }

    /**
     * @param  Builder<Registration>  $query
     * @return Builder<Registration>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', RegistrationStatus::Cancelled->value);
    }
}
