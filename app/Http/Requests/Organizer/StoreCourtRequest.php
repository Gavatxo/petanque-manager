<?php

namespace App\Http\Requests\Organizer;

use App\Models\Tournament;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourtRequest extends FormRequest
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
        $tournament = $this->route('tournament');
        $tournamentId = $tournament instanceof Tournament ? $tournament->id : null;

        return [
            'label' => [
                'required',
                'string',
                'max:50',
                Rule::unique('courts', 'label')->where('tournament_id', $tournamentId),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'nom du terrain',
        ];
    }

    public function messages(): array
    {
        return [
            'label.unique' => 'Un terrain porte déjà ce nom sur ce concours.',
        ];
    }
}
