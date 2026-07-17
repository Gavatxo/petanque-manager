<?php

namespace App\Models;

use App\Enums\TeamFormat;
use App\Enums\TournamentStatus;
use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property string|null $location
 * @property Carbon|null $scheduled_at
 * @property TeamFormat $team_format
 * @property int $qualifying_rounds
 * @property int $tableaux_count
 * @property int $points_target
 * @property int|null $max_teams
 * @property TournamentStatus $status
 * @property string|null $current_phase
 * @property string $registration_token
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $courts_count
 * @property-read User $user
 * @property-read Collection<int, Court> $courts
 */
class Tournament extends Model
{
    /** @use HasFactory<TournamentFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'location',
        'scheduled_at',
        'team_format',
        'qualifying_rounds',
        'tableaux_count',
        'points_target',
        'max_teams',
        'status',
        'current_phase',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'team_format' => TeamFormat::class,
            'status' => TournamentStatus::class,
            'qualifying_rounds' => 'integer',
            'tableaux_count' => 'integer',
            'points_target' => 'integer',
            'max_teams' => 'integer',
            'settings' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Tournament $tournament): void {
            if (empty($tournament->registration_token)) {
                $tournament->registration_token = (string) Str::ulid();
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Court, $this>
     */
    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * @return HasMany<Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * @return HasMany<Matchup, $this>
     */
    public function matches(): HasMany
    {
        return $this->hasMany(Matchup::class);
    }

    public function isArchived(): bool
    {
        return $this->status === TournamentStatus::Archived;
    }

    /**
     * Public URL a player reaches by scanning the inscription QR code.
     * The public route itself is delivered in Phase 2.
     */
    public function registrationUrl(): string
    {
        return url('/i/'.$this->registration_token);
    }

    /**
     * @param  Builder<Tournament>  $query
     * @return Builder<Tournament>
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * @param  Builder<Tournament>  $query
     * @return Builder<Tournament>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', TournamentStatus::Archived->value);
    }
}
