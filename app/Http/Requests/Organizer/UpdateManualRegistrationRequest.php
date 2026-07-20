<?php

namespace App\Http\Requests\Organizer;

use App\Models\Registration;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Modification d'une inscription (nom d'équipe, joueurs) par l'organisateur —
 * correction d'une saisie. Autorisation vérifiée dans le contrôleur.
 */
class UpdateManualRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $registration = $this->route('registration');
        $teamSize = $registration instanceof Registration
            ? $registration->tournament->team_format->teamSize()
            : 1;

        return [
            'team_name' => ['nullable', 'string', 'max:255'],
            'players' => ['required', 'array', 'size:'.$teamSize],
            'players.*.first_name' => ['required', 'string', 'max:100'],
            'players.*.last_name' => ['required', 'string', 'max:100'],
            'players.*.phone' => ['nullable', 'string', 'max:30'],
            'players.*.license_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function attributes(): array
    {
        return [
            'team_name' => 'nom de l’équipe',
            'players.*.first_name' => 'prénom',
            'players.*.last_name' => 'nom',
        ];
    }
}
