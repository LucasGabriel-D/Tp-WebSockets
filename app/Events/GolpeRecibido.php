<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GolpeRecibido implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  string  $sala  Identificador de sala.
     * @param  string  $bandoAtacante  Bando que atacó ('rojo' | 'azul').
     * @param  string  $tipoGolpe  Tipo de golpe ('punio' | 'patada' | 'especial').
     * @param  int  $danio  Puntos de daño aplicados.
     * @param  int  $rojoHp  HP actual del bando rojo.
     * @param  int  $azulHp  HP actual del bando azul.
     * @param  string|null  $ganador  Bando ganador, o null si la pelea sigue.
     * @param  string  $atacanteNombre  Nombre de usuario del atacante.
     */
    public function __construct(
        public readonly string $sala,
        public readonly string $bandoAtacante,
        public readonly string $tipoGolpe,
        public readonly int $danio,
        public readonly int $rojoHp,
        public readonly int $azulHp,
        public readonly ?string $ganador,
        public readonly string $atacanteNombre,
    ) {}

    /**
     * Canales en los que se transmite el evento.
     *
     * @return list<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('clash.'.$this->sala),
        ];
    }

    /**
     * Nombre del evento en el cliente JS.
     */
    public function broadcastAs(): string
    {
        return 'golpe.recibido';
    }

    /**
     * Datos que se envían al cliente.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'bando_atacante' => $this->bandoAtacante,
            'tipo_golpe' => $this->tipoGolpe,
            'danio' => $this->danio,
            'rojo_hp' => $this->rojoHp,
            'azul_hp' => $this->azulHp,
            'ganador' => $this->ganador,
            'atacante_nombre' => $this->atacanteNombre,
        ];
    }
}
