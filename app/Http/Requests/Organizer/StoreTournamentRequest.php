<?php

namespace App\Http\Requests\Organizer;

use App\Enums\TeamFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTournamentRequest extends FormRequest
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
            'qualifying_rounds' => ['required', 'integer', 'min:1', 'max:12'],
            'tableaux_count' => ['required', 'integer', 'min:1', 'max:4'],
            'points_target' => ['required', 'integer', 'min:1', 'max:21'],
            'max_teams' => ['nullable', 'integer', 'min:2', 'max:512'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'location' => 'lieu',
            'scheduled_at' => 'date',
            'team_format' => 'format d’équipe',
            'qualifying_rounds' => 'parties qualificatives',
            'tableaux_count' => 'nombre de tableaux',
            'points_target' => 'points à atteindre',
            'max_teams' => 'nombre maximum d’équipes',
        ];
    }
}
