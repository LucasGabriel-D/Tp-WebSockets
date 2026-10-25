<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>⚔️ Reverb Clash — Lobby de Combate</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --rojo: #f43f5e;
            --azul: #3b82f6;
            --oro:  #f59e0b;
            --verde: #10b981;
            --fondo: #0a0a14;
            --panel: rgba(18, 18, 35, 0.85);
            --borde: rgba(255, 255, 255, 0.08);
            --texto: #f8fafc;
        }

        body {
            background-color: var(--fondo);
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(244, 63, 94, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 85% 85%, rgba(59, 130, 246, 0.12) 0%, transparent 40%);
            color: var(--texto);
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
        }

        /* ── HEADER ── */
        .header {
            background: rgba(15, 15, 28, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--borde);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .logo-icono {
            font-size: 1.6rem;
            filter: drop-shadow(0 0 10px rgba(244, 63, 94, 0.5));
        }
        .logo-titulo {
            font-size: 1.35rem;
            font-weight: 900;
            letter-spacing: -0.03em;
            color: #fff;
            text-transform: uppercase;
        }
        .logo-titulo span {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.04);
            padding: 6px 14px 6px 8px;
            border-radius: 999px;
            border: 1px solid var(--borde);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--rojo), var(--azul));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 800;
            color: white;
            box-shadow: 0 0 12px rgba(244, 63, 94, 0.4);
        }

        .user-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: #e2e8f0;
        }

        .btn-logout {
            background: rgba(244, 63, 94, 0.1);
            border: 1px solid rgba(244, 63, 94, 0.2);
            color: #fb7185;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }
        .btn-logout:hover {
            background: var(--rojo);
            color: white;
        }

        /* ── MAIN ── */
        .main {
            flex: 1;
            padding: 28px 16px 40px;
            max-width: 680px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* ── HERO ── */
        .hero {
            text-align: center;
            padding: 8px 0;
        }
        .hero h1 {
            font-size: clamp(1.8rem, 6vw, 2.5rem);
            font-weight: 900;
            letter-spacing: -0.03em;
            line-height: 1.15;
            margin-bottom: 8px;
        }
        .hero p {
            color: #94a3b8;
            font-size: 0.95rem;
        }

        /* ── CARDS ── */
        .card {
            background: var(--panel);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--borde);
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.3);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }
        .card-titulo {
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-titulo::before {
            content: '';
            width: 4px;
            height: 16px;
            background: var(--oro);
            border-radius: 2px;
        }

        /* ── FORM CREAR SALA ── */
        .form-crear {
            display: flex;
            gap: 12px;
        }
        .input-sala {
            flex: 1;
            background: rgba(10, 10, 20, 0.7);
            border: 2px solid var(--borde);
            border-radius: 14px;
            padding: 14px 16px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            outline: none;
            transition: all 0.2s;
            font-family: inherit;
        }
        .input-sala:focus {
            border-color: var(--oro);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }
        .input-sala::placeholder {
            color: #475569;
        }

        .btn-crear {
            background: linear-gradient(135deg, #f43f5e, #e11d48);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 14px 24px;
            font-size: 0.95rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 16px rgba(244, 63, 94, 0.35);
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
            white-space: nowrap;
        }
        .btn-crear:hover {
            transform: translateY(-1px);
            filter: brightness(1.08);
        }

        .error-msg {
            color: #fb7185;
            font-size: 0.8rem;
            margin-top: 8px;
            font-weight: 600;
        }

        /* ── LISTA DE SALAS ── */
        .salas-lista {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .sala-item {
            background: rgba(22, 22, 42, 0.6);
            border: 1px solid var(--borde);
            border-radius: 16px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .sala-item:hover {
            border-color: rgba(255, 255, 255, 0.18);
            background: rgba(28, 28, 54, 0.8);
            transform: translateY(-2px);
        }

        .sala-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .sala-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .sala-dot.esperando {
            background: var(--oro);
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.7);
            animation: pulseDot 1.5s infinite;
        }
        .sala-dot.en_curso {
            background: var(--verde);
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.7);
        }
        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        .sala-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 0;
        }

        .sala-nombre {
            font-size: 1.1rem;
            font-weight: 800;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sala-badges {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .badge {
            font-size: 0.68rem;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .badge.rojo { background: rgba(244, 63, 94, 0.15); color: #fb7185; border: 1px solid rgba(244, 63, 94, 0.3); }
        .badge.azul { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .badge.libre { background: rgba(255, 255, 255, 0.05); color: #64748b; border: 1px dashed rgba(255, 255, 255, 0.15); }

        .btn-unirse {
            background: linear-gradient(135deg, var(--azul), #2563eb);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 18px;
            font-size: 0.85rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            font-family: inherit;
            white-space: nowrap;
        }
        .btn-unirse:hover {
            transform: scale(1.04);
            filter: brightness(1.1);
        }

        .btn-ver {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--borde);
            color: #94a3b8;
            border-radius: 12px;
            padding: 10px 18px;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-ver:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: #64748b;
        }
        .empty-icon {
            font-size: 3rem;
            display: block;
            margin-bottom: 12px;
            opacity: 0.8;
        }
        .empty-state h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 4px;
        }
        .empty-state p {
            font-size: 0.85rem;
        }

        /* Banner informativo */
        .info-banner {
            background: rgba(245, 158, 11, 0.08);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 16px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.82rem;
            color: #fde68a;
        }
        .info-banner .icon {
            font-size: 1.3rem;
            flex-shrink: 0;
        }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <header class="header">
        <a href="{{ route('clash.lobby') }}" class="logo">
            <span class="logo-icono">⚔️</span>
            <div class="logo-titulo">Reverb <span>Clash</span></div>
        </a>

        <div class="user-profile">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <span class="user-name">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout.rapido') }}">
                @csrf
                <button type="submit" class="btn-logout" title="Cambiar de apodo">Salir</button>
            </form>
        </div>
    </header>

    {{-- MAIN --}}
    <main class="main">

        <div class="hero">
            <h1>Lobby de <span style="background: linear-gradient(135deg, #f59e0b, #fbbf24); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Batalla</span></h1>
            <p>Crea una arena o únete a una pelea en curso</p>
        </div>

        <div class="info-banner">
            <span class="icon">💡</span>
            <div>El creador pelea como <strong>🔴 Rojo</strong> y el rival como <strong>🔵 Azul</strong>. ¡La batalla inicia en cuanto ambos se conectan!</div>
        </div>

        {{-- CREAR SALA --}}
        <div class="card">
            <div class="card-header">
                <span class="card-titulo">Crear Nueva Arena</span>
            </div>
            <form method="POST" action="{{ route('clash.salas.crear') }}" class="form-crear">
                @csrf
                <input
                    type="text"
                    name="nombre_sala"
                    class="input-sala"
                    placeholder="Nombre de la sala (ej: arena-1)"
                    value="{{ old('nombre_sala') }}"
                    maxlength="30"
                    autocomplete="off"
                    spellcheck="false"
                    required
                />
                <button type="submit" class="btn-crear">
                    <span>Crear</span>
                    <span>⚔️</span>
                </button>
            </form>
            @error('nombre_sala')
                <div class="error-msg">⚠️ {{ $message }}</div>
            @enderror
        </div>

        {{-- LISTA DE SALAS --}}
        <div class="card">
            <div class="card-header">
                <span class="card-titulo">Salas Activas</span>
                <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;">Auto-refresco 5s</span>
            </div>

            @if($salas->isEmpty())
                <div class="empty-state">
                    <span class="empty-icon">🏜️</span>
                    <h3>No hay salas activas</h3>
                    <p>Sé el primero en crear una y desafía a tus compañeros.</p>
                </div>
            @else
                <div class="salas-lista">
                    @foreach($salas as $sala)
                        @php
                            $tieneRojo = ! is_null($sala->rojo_user_id);
                            $tieneAzul = ! is_null($sala->azul_user_id);
                            $puedeUnirse = $sala->estado === 'esperando' && (! $tieneRojo || ! $tieneAzul);
                        @endphp
                        <div class="sala-item">
                            <div class="sala-left">
                                <span class="sala-dot {{ $sala->estado }}" title="{{ $sala->estado === 'esperando' ? 'Esperando rival' : 'En combate' }}"></span>
                                <div class="sala-info">
                                    <div class="sala-nombre">{{ $sala->sala }}</div>
                                    <div class="sala-badges">
                                        <span class="badge {{ $tieneRojo ? 'rojo' : 'libre' }}">{{ $tieneRojo ? '🔴 Rojo ocupado' : '🔴 Rojo libre' }}</span>
                                        <span class="badge {{ $tieneAzul ? 'azul' : 'libre' }}">{{ $tieneAzul ? '🔵 Azul ocupado' : '🔵 Azul libre' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                @if($puedeUnirse)
                                    <form method="POST" action="{{ route('clash.salas.unirse', $sala->sala) }}">
                                        @csrf
                                        <button type="submit" class="btn-unirse">Unirse ⚔️</button>
                                    </form>
                                @else
                                    <a href="{{ route('clash.jugar', ['sala' => $sala->sala, 'bando' => 'rojo']) }}" class="btn-ver">Ver Arena</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </main>

    {{-- Auto-refresh silencioso cada 5s --}}
    <script>
        setInterval(() => {
            fetch(window.location.href, { headers: { 'X-Requested-With': 'no-refresh' } })
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const nuevaLista = doc.querySelector('.salas-lista, .empty-state');
                    const listaActual = document.querySelector('.salas-lista, .empty-state');
                    if (nuevaLista && listaActual && nuevaLista.outerHTML !== listaActual.outerHTML) {
                        listaActual.outerHTML = nuevaLista.outerHTML;
                    }
                })
                .catch(() => {});
        }, 5000);
    </script>

</body>
</html>
