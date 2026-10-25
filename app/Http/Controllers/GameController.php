<?php

namespace App\Http\Controllers;

use App\Events\CombateFinalizado;
use App\Events\SalaActualizada;
use App\Models\User;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GameController extends Controller
{
    private const TTL_HOURS = 2;

    /** Salas esperando rival que se listan en el lobby (las más viejas se descartan). */
    private const SALAS_KEY = 'arena:salas';

    private const SALA_VISIBLE_MINUTES = 30;

    /** Últimos combates terminados, para el ticker del lobby. */
    private const RESULTADOS_KEY = 'arena:resultados';

    private const MAX_RESULTADOS = 6;

    public function lobby(): View
    {
        return view('lobby', [
            'salas' => $this->openRooms(),
            'resultados' => Cache::get(self::RESULTADOS_KEY, []),
        ]);
    }

    // Login "invitado": solo pide un nombre (suficiente para autorizar el canal de presencia).
    public function enter(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:16']);

        $user = User::create([
            'name' => $data['name'],
            'email' => Str::uuid().'@guest.local',
            'password' => Str::random(32),
        ]);
        Auth::login($user);

        return redirect()->intended(route('lobby'));
    }

    public function create(Request $request): RedirectResponse
    {
        $code = strtoupper(Str::random(5));
        $char = $this->char($request);
        $host = Auth::user()->name;

        Cache::put("room:$code", [
            'host' => $host,
            'players' => [Auth::id()],
            'chars' => [Auth::id() => $char],
        ], now()->addHours(self::TTL_HOURS));

        $salas = Cache::get(self::SALAS_KEY, []);
        $salas[$code] = ['code' => $code, 'host' => $host, 'char' => $char, 'at' => now()->timestamp];
        Cache::put(self::SALAS_KEY, $salas, now()->addHours(self::TTL_HOURS));

        // Igual que el controlador del emisor: se emite un evento y todos los lobbies abiertos lo reciben
        $this->emit(new SalaActualizada($code, $host, $char, 'abierta'));

        return redirect()->route('room', $code);
    }

    public function join(Request $request): RedirectResponse
    {
        $code = strtoupper($request->validate(['code' => 'required|string|size:5'])['code']);

        if (! Cache::has("room:$code")) {
            return back()->withErrors(['code' => 'Esa sala no existe']);
        }

        session(['char' => $this->char($request)]); // el personaje elegido se guarda al entrar a la sala

        return redirect()->route('room', $code);
    }

    public function room(string $code): View
    {
        $code = strtoupper($code);
        $room = Cache::get("room:$code");
        abort_unless($room, 404, 'Sala inexistente');

        $players = $room['players'];
        $chars = $room['chars'] ?? [];
        if (! in_array(Auth::id(), $players)) {
            abort_if(count($players) >= 2, 403, 'Sala llena');
            $players[] = Auth::id();
            $chars[Auth::id()] = (int) session('char', 1);
            Cache::put("room:$code", [...$room, 'players' => $players, 'chars' => $chars], now()->addHours(self::TTL_HOURS));

            if (count($players) === 2) {
                $this->closeRoom($code, $room['host'] ?? '', (int) ($chars[$players[0]] ?? 0));
            }
        }

        return view('game', [
            'code' => $code,
            'game' => [
                'code' => $code,
                'slot' => array_search(Auth::id(), $players),
                'chars' => array_map(fn ($id) => (int) ($chars[$id] ?? 0), $players),
                'me' => ['id' => Auth::id(), 'name' => Auth::user()->name],
            ],
        ]);
    }

    /**
     * Los dos clientes informan el resultado al terminar; el primero que llega se guarda y se emite
     * por el canal público "arena". El segundo (mismo número de combate) se ignora.
     */
    public function result(Request $request, string $code): JsonResponse
    {
        $code = strtoupper($code);
        $room = Cache::get("room:$code");
        abort_unless($room, 404, 'Sala inexistente');

        $players = $room['players'];
        abort_unless(in_array(Auth::id(), $players) && count($players) === 2, 403);

        $data = $request->validate([
            'match' => ['required', 'integer', 'min:1'],
            'winner' => ['required', 'integer', 'in:0,1'],
            'wins' => ['required', 'array', 'size:2'],
            'wins.*' => ['integer', 'between:0,9'],
            'perfect' => ['boolean'],
        ]);

        if (! Cache::add("room:$code:resultado:{$data['match']}", true, now()->addHours(self::TTL_HOURS))) {
            return response()->json(['emitido' => false]);
        }

        $winner = $data['winner'];
        $loser = 1 - $winner;
        $names = User::whereIn('id', $players)->pluck('name', 'id');
        $resultado = [
            'ganador' => $names[$players[$winner]] ?? '?',
            'perdedor' => $names[$players[$loser]] ?? '?',
            'charGanador' => (int) ($room['chars'][$players[$winner]] ?? 0),
            'charPerdedor' => (int) ($room['chars'][$players[$loser]] ?? 0),
            'marcador' => $data['wins'][$winner].'-'.$data['wins'][$loser],
            'perfect' => (bool) ($data['perfect'] ?? false),
        ];

        $resultados = array_slice([$resultado, ...Cache::get(self::RESULTADOS_KEY, [])], 0, self::MAX_RESULTADOS);
        Cache::put(self::RESULTADOS_KEY, $resultados, now()->addDay());

        $this->emit(new CombateFinalizado($resultado));

        return response()->json(['emitido' => true], 202);
    }

    /**
     * Saca la sala de la lista del lobby y avisa que ya no acepta jugadores.
     */
    private function closeRoom(string $code, string $host, int $char): void
    {
        $salas = Cache::get(self::SALAS_KEY, []);
        unset($salas[$code]);
        Cache::put(self::SALAS_KEY, $salas, now()->addHours(self::TTL_HOURS));

        $this->emit(new SalaActualizada($code, $host, $char, 'llena'));
    }

    /**
     * Emite por Reverb sin cortar el juego si el servidor WebSocket no responde:
     * el lobby en vivo es un extra, crear o terminar una pelea tiene que funcionar igual.
     */
    private function emit(object $event): void
    {
        try {
            broadcast($event);
        } catch (BroadcastException|GuzzleException $e) {
            report($e);
        }
    }

    /**
     * @return list<array{code: string, host: string, char: int, at: int}>
     */
    private function openRooms(): array
    {
        $desde = now()->subMinutes(self::SALA_VISIBLE_MINUTES)->timestamp;

        return collect(Cache::get(self::SALAS_KEY, []))
            ->filter(fn (array $sala) => $sala['at'] >= $desde)
            ->sortByDesc('at')
            ->values()
            ->all();
    }

    private function char(Request $request): int
    {
        return (int) ($request->validate(['char' => 'nullable|integer|between:0,3'])['char'] ?? 0);
    }
}
