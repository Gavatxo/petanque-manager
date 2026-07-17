<?php

declare(strict_types=1);

namespace App\Application\Tournament;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

/**
 * Transforme les inscriptions dont la présence est validée (checked_in) en
 * équipes officielles ({@see Team}), copiant les joueurs déclarés.
 *
 * Idempotent : une inscription déjà transformée est ignorée.
 */
final class ConvertRegistrationsToTeams
{
    /**
     * @return int nombre d'équipes créées
     */
    public function handle(Tournament $tournament): int
    {
        return DB::transaction(function () use ($tournament): int {
            $registrations = $tournament->registrations()
                ->where('status', RegistrationStatus::CheckedIn->value)
                ->whereDoesntHave('team')
                ->with('players')
                ->orderBy('checked_in_at')
                ->orderBy('id')
                ->get();

            $seed = (int) $tournament->teams()->max('seed');
            $created = 0;

            foreach ($registrations as $registration) {
                /** @var Registration $registration */
                $seed++;

                $team = $tournament->teams()->create([
                    'registration_id' => $registration->id,
                    'name' => $registration->team_name ?: "Équipe {$seed}",
                    'seed' => $seed,
                ]);

                foreach ($registration->players as $player) {
                    $team->players()->create([
                        'first_name' => $player->first_name,
                        'last_name' => $player->last_name,
                        'phone' => $player->phone,
                        'license_number' => $player->license_number,
                        'is_captain' => $player->is_captain,
                    ]);
                }

                $created++;
            }

            return $created;
        });
    }
}
