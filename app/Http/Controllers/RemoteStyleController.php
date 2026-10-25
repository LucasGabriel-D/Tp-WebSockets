<?php

namespace App\Http\Controllers;

use App\Events\RemoteStyleUpdate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Trabajo guiado: el emisor manda un texto + CSS y todos los receptores lo ven al instante.
 * En el juego, los receptores son el lobby, cada pelea en curso y /receptor.
 */
class RemoteStyleController extends Controller
{
    public function emisor(): View
    {
        return view('emisor');
    }

    public function receptor(): View
    {
        return view('receptor');
    }

    public function emitir(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'newText' => ['required', 'string', 'max:120'],
            'newStyle' => ['nullable', 'string', 'max:600'],
        ]);

        broadcast(new RemoteStyleUpdate(
            $data['newText'],
            $data['newStyle'] ?? ''
        ));

        return back()->with('ok', 'Mensaje emitido correctamente.');
    }
}
