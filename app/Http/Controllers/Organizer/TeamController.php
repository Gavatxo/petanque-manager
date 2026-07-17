<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TeamController extends Controller
{
    use AuthorizesRequests;

    public function destroy(Team $team): RedirectResponse
    {
        $tournament = $team->tournament;
        $this->authorize('update', $tournament);

        // Une fois le concours lancé, l'équipe est engagée dans les appariements :
        // on ne peut plus la supprimer sans corrompre le déroulé.
        if ($tournament->current_phase !== null) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Impossible de retirer une équipe une fois le concours lancé.',
            ]);

            return back();
        }

        $team->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Équipe retirée.']);

        return back();
    }
}
