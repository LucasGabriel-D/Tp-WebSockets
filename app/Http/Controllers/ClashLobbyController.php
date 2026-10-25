<?php

namespace App\Http\Controllers;

use App\Models\ClashMatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClashLobbyController extends Controller
{
    /**
     * Muestra el lobby con la lista de salas disponibles.
     * GET /clash
     */
    public function index(): View
    {
        $salas = ClashMatch::query()
            ->whereIn('estado', ['esperando', 'en_curso'])
            ->latest()
            ->take(10)
            ->get();

        return view('clash.lobby', compact('salas'));
    }

    /**
     * Crea una sala nueva y redirige al creador como bando rojo.
     * POST /clash/salas
     */
    public function crearSala(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre_sala' => ['required', 'string', 'min:3', 'max:30', 'alpha_dash'],
        ]);

        $sala = strtolower($validated['nombre_sala']);

        // Si la sala ya existe y está activa, no crea otra
        $existe = ClashMatch::where('sala', $sala)
            ->whereIn('estado', ['esperando', 'en_curso'])
            ->exists();

        if ($existe) {
            return back()->withErrors(['nombre_sala' => 'Ya existe una sala activa con ese nombre.'])->withInput();
        }

        return redirect()->route('clash.jugar', ['sala' => $sala, 'bando' => 'rojo']);
    }

    /**
     * Une al jugador a una sala existente como bando azul.
     * POST /clash/salas/{sala}/unirse
     */
    public function unirse(string $sala): RedirectResponse
    {
        $partida = ClashMatch::where('sala', $sala)
            ->whereIn('estado', ['esperando', 'en_curso'])
            ->firstOrFail();

        // Si el bando azul ya está ocupado, redirige como espectador-rojo
        $bando = is_null($partida->azul_user_id) ? 'azul' : 'rojo';

        return redirect()->route('clash.jugar', ['sala' => $sala, 'bando' => $bando]);
    }
}
