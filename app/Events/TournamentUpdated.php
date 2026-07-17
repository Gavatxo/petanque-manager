<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Signal temps réel : l'état d'un concours a changé (résultat saisi, terrain
 * libéré, nouvelle partie, phase, inscription…). Diffusé sur un canal public ;
 * les écrans rechargent alors leurs données via leurs routes habituelles
 * (aucune donnée sensible dans la charge utile).
 */
class TournamentUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public int $tournamentId) {}

    public function broadcastOn(): Channel
    {
        return new Channel("tournament.{$this->tournamentId}");
    }

    public function broadcastAs(): string
    {
        return 'updated';
    }

    /**
     * @return array<string, int>
     */
    public function broadcastWith(): array
    {
        return ['tournament_id' => $this->tournamentId];
    }
}
