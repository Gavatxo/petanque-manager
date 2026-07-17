<?php

namespace App\Models;

use Database\Factories\RegistrationPlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Joueur déclaré sur une demande d'inscription (avant création de l'équipe officielle).
 *
 * @property int $id
 * @property int $registration_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $phone
 * @property string|null $license_number
 * @property bool $is_captain
 * @property-read Registration $registration
 */
class RegistrationPlayer extends Model
{
    /** @use HasFactory<RegistrationPlayerFactory> */
    use HasFactory;

    protected $fillable = [
        'registration_id',
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
     * @return BelongsTo<Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
