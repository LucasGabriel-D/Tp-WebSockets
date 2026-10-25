<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;

// Canal de presencia: solo los 2 jugadores asignados a la sala pueden entrar.
// El servidor valida acceso; el juego en sí viaja por "whispers" (cliente a cliente).
Broadcast::channel('fight.{code}', function ($user, string $code) {
    $room = Cache::get('room:' . strtoupper($code));
    $slot = $room ? array_search($user->id, $room['players']) : false;

    return $slot === false
        ? false
        : [
            'id' => $user->id,
            'name' => $user->name,
            'slot' => $slot,
            'char' => (int) ($room['chars'][$user->id] ?? 0),
        ];
});
