<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>⚔️ Reverb Clash — Entrar a la Arena</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --rojo: #f43f5e;
            --rojo-glow: rgba(244, 63, 94, 0.4);
            --azul: #3b82f6;
            --azul-glow: rgba(59, 130, 246, 0.4);
            --oro:  #f59e0b;
            --oro-glow: rgba(245, 158, 11, 0.4);
            --fondo: #0a0a14;
            --panel: rgba(18, 18, 35, 0.85);
            --borde: rgba(255, 255, 255, 0.1);
            --texto: #f8fafc;
        }

        body {
            background-color: var(--fondo);
            background-image: 
                radial-gradient(circle at 15% 20%, rgba(244, 63, 94, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 85% 80%, rgba(59, 130, 246, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(245, 158, 11, 0.05) 0%, transparent 60%);
            color: var(--texto);
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Efecto de cuadrícula arcade retro tenue */
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
            z-index: 1;
        }

        .auth-container {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 10;
        }

        .auth-card {
            background: var(--panel);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--borde);
            border-radius: 28px;
            padding: 40px 32px;
            text-align: center;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.7),
                0 0 40px -10px rgba(244, 63, 94, 0.2);
            position: relative;
            overflow: hidden;
            animation: cardAppear 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes cardAppear {
            0% { opacity: 0; transform: translateY(20px) scale(0.96); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Barra decorativa superior */
        .auth-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--rojo), var(--oro), var(--azul));
        }

        /* Emblema / Icono Central */
        .badge-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 16px;
            background: linear-gradient(135deg, rgba(244, 63, 94, 0.2), rgba(59, 130, 246, 0.2));
            border: 2px solid rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            transform: rotate(-3deg);
            transition: transform 0.3s ease;
        }
        .badge-icon:hover {
            transform: rotate(3deg) scale(1.05);
        }

        h1 {
            font-size: 2.1rem;
            font-weight: 900;
            letter-spacing: -0.03em;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        h1 span {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 20px var(--oro-glow);
        }

        p.subtitulo {
            color: #94a3b8;
            font-size: 0.95rem;
            margin-bottom: 28px;
            font-weight: 500;
        }

        .input-group {
            margin-bottom: 24px;
            text-align: left;
        }

        label {
            display: block;
            font-size: 0.78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #cbd5e1;
            margin-bottom: 10px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-prefix-icon {
            position: absolute;
            left: 16px;
            font-size: 1.2rem;
            pointer-events: none;
            opacity: 0.8;
        }

        .input-nombre {
            width: 100%;
            background: rgba(10, 10, 20, 0.7);
            border: 2px solid var(--borde);
            border-radius: 16px;
            padding: 16px 16px 16px 50px;
            font-size: 1.15rem;
            font-weight: 700;
            color: #fff;
            outline: none;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            font-family: inherit;
        }

        .input-nombre::placeholder {
            color: #475569;
            font-weight: 500;
            font-size: 1rem;
        }

        .input-nombre:focus {
            border-color: var(--oro);
            background: rgba(15, 15, 30, 0.9);
            box-shadow: 
                0 0 0 4px rgba(245, 158, 11, 0.15),
                0 0 20px var(--oro-glow);
        }

        .btn-entrar {
            width: 100%;
            background: linear-gradient(135deg, #f43f5e, #e11d48);
            color: #fff;
            border: none;
            border-radius: 16px;
            padding: 16px;
            font-size: 1.1rem;
            font-weight: 900;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 10px 25px -5px var(--rojo-glow);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            touch-action: manipulation;
        }

        .btn-entrar:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -4px var(--rojo-glow);
            filter: brightness(1.08);
        }

        .btn-entrar:active {
            transform: translateY(1px) scale(0.98);
        }

        .error-msg {
            color: #fb7185;
            font-size: 0.82rem;
            margin-top: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Características o Badges inferiores */
        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .feature-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .feature-item span.icon {
            font-size: 1.2rem;
        }
        .feature-item span.text {
            font-size: 0.68rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <div class="auth-card">
            
            <div class="badge-icon">🥷</div>
            
            <h1>Reverb <span>Clash</span></h1>
            <p class="subtitulo">Minijuego Multijugador en Tiempo Real</p>

            <form method="POST" action="{{ route('login.rapido.post') }}">
                @csrf
                <div class="input-group">
                    <label for="nombre">Tu Apodo de Peleador</label>
                    <div class="input-wrapper">
                        <span class="input-prefix-icon">⚡</span>
                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            class="input-nombre"
                            placeholder="Ej: Goku, Scorpion, Alex..."
                            value="{{ old('nombre') }}"
                            required
                            autofocus
                            maxlength="25"
                            autocomplete="off"
                            spellcheck="false"
                        />
                    </div>
                    @error('nombre')
                        <div class="error-msg">⚠️ {{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn-entrar">
                    <span>¡Entrar a la Arena!</span>
                    <span>⚔️</span>
                </button>
            </form>

            <div class="features">
                <div class="feature-item">
                    <span class="icon">🚀</span>
                    <span class="text">Sin Contraseña</span>
                </div>
                <div class="feature-item">
                    <span class="icon">📡</span>
                    <span class="text">WebSockets</span>
                </div>
                <div class="feature-item">
                    <span class="icon">🏆</span>
                    <span class="text">Partidas 1v1</span>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
