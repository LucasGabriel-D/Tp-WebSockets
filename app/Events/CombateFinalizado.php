<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Resultado de un combate, para el "ticker" de últimos combates del lobby.
 */
class CombateFinalizado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array{ganador: string, perdedor: string, charGanador: int, charPerdedor: int, marcador: string, perfect: bool}  $resultado
     */
    public function __construct(public array $resultado) {}

    /**
     * Canal público del lobby.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('arena')];
    }

    public function broadcastAs(): string
    {
        return 'CombateFinalizado';
    }

    /**
     * Datos que viajan al navegador.
     *
     * @return array{ganador: string, perdedor: string, charGanador: int, charPerdedor: int, marcador: string, perfect: bool}
     */
    public function broadcastWith(): array
    {
        return $this->resultado;
    }
}
