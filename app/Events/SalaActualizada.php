<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Avisa al lobby que una sala se abrió (alguien espera rival) o se llenó.
 *
 * ShouldBroadcastNow: se publica en Reverb en el momento, sin pasar por la cola.
 */
class SalaActualizada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  string  $code  Código de la sala.
     * @param  string  $host  Nombre de quien la creó.
     * @param  int  $char  Personaje del creador (índice).
     * @param  string  $estado  'abierta' | 'llena'.
     */
    public function __construct(
        public string $code,
        public string $host,
        public int $char,
        public string $estado,
    ) {}

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
        return 'SalaActualizada';
    }
}
