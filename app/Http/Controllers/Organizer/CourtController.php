<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\CourtStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\StoreCourtRequest;
use App\Models\Court;
use App\Models\Tournament;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CourtController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreCourtRequest $request, Tournament $tournament): RedirectResponse
    {
        $this->authorize('update', $tournament);

        $tournament->courts()->create([
            'label' => $request->validated('label'),
            'status' => CourtStatus::Available,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Terrain ajouté.']);

        return back();
    }

    public function generate(Request $request, Tournament $tournament): RedirectResponse
    {
        $this->authorize('update', $tournament);

        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $existing = $tournament->courts()->pluck('label')->all();
        $highest = collect($existing)
            ->map(fn (string $label) => (int) $label)
            ->filter(fn (int $n) => $n > 0)
            ->max() ?? 0;

        $created = 0;
        $number = $highest;

        while ($created < $validated['count']) {
            $number++;
            $label = (string) $number;

            if (in_array($label, $existing, true)) {
                continue;
            }

            $tournament->courts()->create(['label' => $label, 'status' => CourtStatus::Available]);
            $existing[] = $label;
            $created++;
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $created.' terrain(s) ajouté(s).',
        ]);

        return back();
    }

    public function update(Request $request, Court $court): RedirectResponse
    {
        $this->authorize('update', $court->tournament);

        $validated = $request->validate([
            'label' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('courts', 'label')
                    ->where('tournament_id', $court->tournament_id)
                    ->ignore($court->id),
            ],
            'status' => ['sometimes', 'required', Rule::enum(CourtStatus::class)],
        ]);

        $court->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Terrain mis à jour.']);

        return back();
    }

    public function destroy(Court $court): RedirectResponse
    {
        $this->authorize('update', $court->tournament);

        $court->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Terrain supprimé.']);

        return back();
    }
}
