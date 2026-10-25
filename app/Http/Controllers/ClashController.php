<?php

namespace App\Http\Controllers;

use App\Events\GolpeRecibido;
use App\Models\ClashMatch;
use App\Models\ClashMatchLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ClashController extends Controller
{
    /** HP máximo de cada bando. */
    private const int HP_MAXIMO = 1000;

    /** Daño por tipo de golpe. */
    private const array DANIO_POR_GOLPE = [
        'punio' => 5,
        'patada' => 10,
        'especial' => 20,
    ];

    /**
     * Muestra la vista del juego para una sala y bando específicos.
     * GET /jugar/{sala}/{bando}
     */
    public function jugar(Request $request, string $sala, string $bando): View
    {
        abort_unless(in_array($bando, ['rojo', 'azul'], true), 404);

        /** @var User $usuario */
        $usuario = $request->user();

        // Busca o crea la partida en DB
        $partida = ClashMatch::firstOrCreate(
            ['sala' => $sala],
            [
                'rojo_hp' => self::HP_MAXIMO,
                'azul_hp' => self::HP_MAXIMO,
                'estado' => 'esperando',
            ],
        );

        // Si el bando está vacío o el usuario entra como este bando, asigna al jugador
        $columnaUsuario = $bando.'_user_id';
        if (is_null($partida->$columnaUsuario) || $partida->$columnaUsuario === $usuario->id) {
            $partida->update([$columnaUsuario => $usuario->id]);
        } elseif ($bando === 'azul' && $partida->rojo_user_id !== $usuario->id) {
            $partida->update(['azul_user_id' => $usuario->id]);
        } elseif ($bando === 'rojo' && $partida->azul_user_id !== $usuario->id) {
            $partida->update(['rojo_user_id' => $usuario->id]);
        }

        // Si ambos jugadores están presentes, actualiza estado a 'en_curso'
        if ($partida->rojo_user_id && $partida->azul_user_id && $partida->estado === 'esperando') {
            $partida->update(['estado' => 'en_curso']);
        }

        // Carga o inicializa el estado en caché
        $estadoCache = Cache::remember(
            ClashMatch::cacheKey($sala),
            now()->addHours(4),
            fn () => [
                'rojo_hp' => self::HP_MAXIMO,
                'azul_hp' => self::HP_MAXIMO,
                'ganador' => null,
            ],
        );

        return view('clash.jugar', [
            'sala' => $sala,
            'bando' => $bando,
            'partida' => $partida,
            'estadoCache' => $estadoCache,
        ]);
    }

    /**
     * Aplica un golpe al bando contrario y emite el evento de broadcast.
     * POST /api/golpear
     */
    public function golpear(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sala' => ['required', 'string', 'max:50'],
            'tipo_golpe' => ['required', 'string', 'in:punio,patada,especial'],
        ]);

        /** @var User $usuario */
        $usuario = $request->user();
        $sala = $validated['sala'];
        $tipoGolpe = $validated['tipo_golpe'];
        $danio = self::DANIO_POR_GOLPE[$tipoGolpe];

        $partida = ClashMatch::where('sala', $sala)->first();
        abort_unless($partida, 404, 'Sala no encontrada.');
        abort_if($partida->haFinalizado(), 422, 'La partida ya finalizó.');

        // Determina el bando del atacante
        $bandoAtacante = match (true) {
            $partida->rojo_user_id === $usuario->id => 'rojo',
            $partida->azul_user_id === $usuario->id => 'azul',
            default => null,
        };
        abort_unless($bandoAtacante, 403, 'No eres parte de esta partida.');

        $bandoDefensor = $bandoAtacante === 'rojo' ? 'azul' : 'rojo';

        // --- Actualiza HP en Caché (operación atómica con lock) ---
        $cacheKey = ClashMatch::cacheKey($sala);

        $estado = Cache::get($cacheKey, [
            'rojo_hp' => self::HP_MAXIMO,
            'azul_hp' => self::HP_MAXIMO,
            'ganador' => null,
        ]);

        if ($estado['ganador'] !== null) {
            return response()->json(['message' => 'La partida ya finalizó.'], 422);
        }

        $hpDefensorKey = $bandoDefensor.'_hp';
        $nuevoHp = max(0, $estado[$hpDefensorKey] - $danio);
        $estado[$hpDefensorKey] = $nuevoHp;

        $ganador = null;
        if ($nuevoHp === 0) {
            $ganador = $bandoAtacante;
            $estado['ganador'] = $ganador;
        }

        Cache::put($cacheKey, $estado, now()->addHours(4));

        $rojoHp = $estado['rojo_hp'];
        $azulHp = $estado['azul_hp'];

        // --- Persiste el log en DB ---
        ClashMatchLog::create([
            'clash_match_id' => $partida->id,
            'atacante_user_id' => $usuario->id,
            'bando_atacante' => $bandoAtacante,
            'tipo_golpe' => $tipoGolpe,
            'danio' => $danio,
            'rojo_hp_restante' => $rojoHp,
            'azul_hp_restante' => $azulHp,
        ]);

        // --- Si alguien ganó, actualiza la partida en DB ---
        if ($ganador !== null) {
            $partida->update([
                'rojo_hp' => $rojoHp,
                'azul_hp' => $azulHp,
                'estado' => 'finalizado',
                'ganador' => $ganador,
            ]);
        }

        // --- Emite el evento de broadcast ---
        GolpeRecibido::dispatch(
            sala: $sala,
            bandoAtacante: $bandoAtacante,
            tipoGolpe: $tipoGolpe,
            danio: $danio,
            rojoHp: $rojoHp,
            azulHp: $azulHp,
            ganador: $ganador,
            atacanteNombre: $usuario->name,
        );

        return response()->json([
            'rojo_hp' => $rojoHp,
            'azul_hp' => $azulHp,
            'ganador' => $ganador,
            'danio' => $danio,
            'bando_atacante' => $bandoAtacante,
        ]);
    }
}
