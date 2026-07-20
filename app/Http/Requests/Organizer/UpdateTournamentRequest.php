<?php

namespace App\Http\Requests\Organizer;

use App\Enums\TeamFormat;
use App\Enums\TournamentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateTournamentRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'team_format' => ['required', new Enum(TeamFormat::class)],
            // Le format (parties qualif., tableaux, points) est suggéré selon le
            // nombre d'équipes puis confirmé au tirage — pas modifiable ici.
            'max_teams' => ['nullable', 'integer', 'min:2', 'max:512'],
            'status' => [
                'required',
                Rule::in(array_map(fn (TournamentStatus $s) => $s->value, TournamentStatus::organizerSelectable())),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'location' => 'lieu',
            'scheduled_at' => 'date',
            'team_format' => 'format d’équipe',
            'max_teams' => 'nombre maximum d’équipes',
            'status' => 'statut',
        ];
    }
}
