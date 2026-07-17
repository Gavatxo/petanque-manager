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
        $this->authorize('update', $team->tournament);

        $team->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Équipe retirée.']);

        return back();
    }
}
