<?php

namespace App\Http\Requests\Public;

use App\Enums\TournamentStatus;
use App\Models\Tournament;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class RegisterTeamRequest extends FormRequest
{
    /**
     * Autorisé uniquement quand les inscriptions sont ouvertes.
     */
    public function authorize(): bool
    {
        $tournament = $this->route('tournament');

        return $tournament instanceof Tournament
            && $tournament->status === TournamentStatus::RegistrationOpen;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tournament = $this->route('tournament');
        $teamSize = $tournament instanceof Tournament ? $tournament->team_format->teamSize() : 1;

        return [
            'team_name' => ['nullable', 'string', 'max:255'],
            'players' => ['required', 'array', 'size:'.$teamSize],
            'players.*.first_name' => ['required', 'string', 'max:100'],
            'players.*.last_name' => ['required', 'string', 'max:100'],
            'players.*.phone' => ['nullable', 'string', 'max:30'],
            'players.*.license_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tournament = $this->route('tournament');

            if (! $tournament instanceof Tournament) {
                return;
            }

            if ($tournament->max_teams !== null && $tournament->teams()->count() >= $tournament->max_teams) {
                $validator->errors()->add('players', 'Le concours affiche complet : plus de place disponible.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'team_name' => 'nom de l’équipe',
            'players.*.first_name' => 'prénom',
            'players.*.last_name' => 'nom',
            'players.*.phone' => 'téléphone',
        ];
    }
}
