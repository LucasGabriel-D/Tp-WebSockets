<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento del trabajo guiado (emisor → receptor).
 *
 * Implementa ShouldBroadcast: Laravel lo pone en la cola configurada y, al procesarse,
 * lo publica en Reverb. Con QUEUE_CONNECTION=sync se envía dentro del mismo POST /emitir.
 */
class RemoteStyleUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Las propiedades públicas son el contenido que recibe el navegador en "e".
     */
    public function __construct(
        public string $newText,
        public string $newStyle,
    ) {}

    /**
     * Canal PÚBLICO: cualquiera puede suscribirse con Echo.channel('style-channel').
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('style-channel')];
    }

    /**
     * Nombre del evento en el front: se escucha con .listen('.RemoteStyleUpdate').
     */
    public function broadcastAs(): string
    {
        return 'RemoteStyleUpdate';
    }
}
