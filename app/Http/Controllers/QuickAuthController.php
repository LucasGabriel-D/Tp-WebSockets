<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class QuickAuthController extends Controller
{
    /**
     * Muestra la pantalla sencilla para ingresar el nombre de jugador.
     */
    public function show(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended(route('clash.lobby'));
        }

        return view('auth.quick-login');
    }

    /**
     * Inicia sesión o registra al jugador solo con su nombre.
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'min:2', 'max:25'],
        ], [
            'nombre.required' => 'Por favor escribe tu nombre de jugador.',
            'nombre.min' => 'El nombre debe tener al menos 2 letras.',
            'nombre.max' => 'El nombre no puede tener más de 25 letras.',
        ]);

        $nombre = trim($validated['nombre']);
        $slug = Str::slug($nombre);
        if (empty($slug)) {
            $slug = 'jugador-'.Str::random(5);
        }

        // Correo sintético único para cumplir esquema DB
        $email = $slug.'@clash.local';

        // Busca o crea el usuario
        $usuario = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $nombre,
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
            ]
        );

        // Si el usuario ya existía con otro nombre visible, actualízalo
        if ($usuario->name !== $nombre) {
            $usuario->update(['name' => $nombre]);
        }

        Auth::login($usuario, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('clash.lobby'));
    }

    /**
     * Cierra la sesión del jugador.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.rapido');
    }
}
