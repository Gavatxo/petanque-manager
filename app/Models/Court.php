<?php

namespace App\Models;

use App\Enums\CourtStatus;
use Database\Factories\CourtFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tournament_id
 * @property string $label
 * @property CourtStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Tournament $tournament
 */
class Court extends Model
{
    /** @use HasFactory<CourtFactory> */
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'label',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => CourtStatus::class,
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
