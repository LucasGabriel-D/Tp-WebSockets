<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>⚔️ Reverb Clash — Sala {{ $sala }}</title>
    
    <!-- Fuentes Arcade y Pro -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Black+Ops+One&family=Outfit:wght@600;800;900&display=swap" rel="stylesheet">
    
    <!-- Librerías de Audio y Animación para el Front -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.4/howler.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/canvas-confetti/1.9.3/confetti.browser.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>

    @vite(['resources/css/app.css'])

    <style>
        *, *::before, *::after { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            -webkit-tap-highlight-color: transparent;
            user-select: none;
            -webkit-user-select: none;
        }

        :root {
            --rojo: #ef4444;
            --rojo-brillo: #f87171;
            --azul: #3b82f6;
            --azul-brillo: #60a5fa;
            --oro: #f59e0b;
            --fondo: #0a0a14;
            --panel: #111122;
            --borde: rgba(255, 255, 255, 0.1);
            --hp-max: 1000;
        }

        body {
            background-color: var(--fondo);
            color: #f8fafc;
            font-family: 'Outfit', sans-serif;
            height: 100dvh;
            width: 100vw;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            touch-action: none;
        }

        /* ── TOP NAV ── */
        #top-bar {
            background: rgba(10, 10, 20, 0.95);
            border-bottom: 1px solid var(--borde);
            padding: 6px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.75rem;
            z-index: 30;
        }
        #top-bar a {
            color: #94a3b8;
            text-decoration: none;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.05);
        }

        /* ── CABECERA / BARRAS DE VIDA ESTILO FIGHTING GAME ── */
        #cabecera-vida {
            background: rgba(15, 15, 30, 0.9);
            padding: 10px 14px 6px;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 10px;
            align-items: center;
            border-bottom: 2px solid rgba(0, 0, 0, 0.5);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            z-index: 25;
        }

        .jugador-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .jugador-info.azul { align-items: flex-end; }

        .jugador-nombre {
            font-size: 0.8rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .jugador-nombre.rojo { color: var(--rojo-brillo); }
        .jugador-nombre.azul { color: var(--azul-brillo); }

        .barra-marco {
            width: 100%;
            height: 16px;
            background: #1e1e2f;
            border: 2px solid #000;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.8);
            transform: skew(-10deg);
        }

        .barra-vida {
            height: 100%;
            width: 100%;
            transition: width 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .barra-vida.rojo {
            background: linear-gradient(90deg, #b91c1c, var(--rojo));
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.8);
        }
        .barra-vida.azul {
            background: linear-gradient(90deg, var(--azul), #1d4ed8);
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.8);
            margin-left: auto;
        }

        .hp-txt {
            font-size: 0.75rem;
            font-weight: 800;
            color: #cbd5e1;
            font-variant-numeric: tabular-nums;
            margin-top: 2px;
        }

        .vs-logo {
            font-family: 'Black Ops One', cursive, sans-serif;
            font-size: 1.5rem;
            color: var(--oro);
            text-shadow: 0 0 12px rgba(245, 158, 11, 0.8), 2px 2px #000;
            padding: 0 4px;
        }

        /* ── ARENA CENTRAL DE PELEA ── */
        #arena {
            flex: 1;
            position: relative;
            overflow: hidden;
            background: 
                linear-gradient(to bottom, rgba(10, 10, 25, 0.8) 0%, rgba(18, 18, 40, 0.6) 70%, #15152a 100%),
                radial-gradient(ellipse at 50% 100%, rgba(244, 63, 94, 0.15), transparent 70%);
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            padding: 0 24px 28px;
        }

        /* Suelo / Stage Arcade */
        #arena::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 24px;
            background: linear-gradient(90deg, #2a1122 0%, #111a33 100%);
            border-top: 3px solid #ffcc00;
            box-shadow: 0 -4px 15px rgba(255, 204, 0, 0.3);
        }

        /* Luchadores con Animación Clásica Idle */
        .fighter-wrapper {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 10;
            transition: transform 0.15s ease-out;
        }

        .fighter {
            font-size: clamp(4.5rem, 18vw, 7rem);
            filter: drop-shadow(0 10px 15px rgba(0, 0, 0, 0.6));
            animation: idleBreathe 1.8s ease-in-out infinite alternate;
            transform-origin: bottom center;
            display: inline-block;
        }

        #fighter-azul .fighter {
            transform: scaleX(-1);
            animation: idleBreatheBlue 1.8s ease-in-out infinite alternate;
        }

        @keyframes idleBreathe {
            0%   { transform: translateY(0) scaleY(1) scaleX(1); }
            100% { transform: translateY(-8px) scaleY(1.04) scaleX(0.98); }
        }
        @keyframes idleBreatheBlue {
            0%   { transform: scaleX(-1) translateY(0) scaleY(1); }
            100% { transform: scaleX(-1) translateY(-8px) scaleY(1.04); }
        }

        .fighter-sombra {
            width: 70px;
            height: 12px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            filter: blur(4px);
            margin-top: -10px;
        }

        /* IMPACTOS Y EFECTOS ESTILO STREET FIGHTER */
        .impact-hit {
            position: absolute;
            font-family: 'Black Ops One', sans-serif;
            font-size: 2.2rem;
            color: #ffea00;
            text-shadow: 0 0 15px #ff0055, 2px 2px 0 #000;
            pointer-events: none;
            z-index: 50;
            animation: hitPopup 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes hitPopup {
            0%   { opacity: 1; transform: translate(-50%, -50%) scale(0.5) rotate(-10deg); }
            50%  { transform: translate(-50%, -100%) scale(1.4) rotate(8deg); }
            100% { opacity: 0; transform: translate(-50%, -150%) scale(0.8); }
        }

        .flash-screen {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.25);
            pointer-events: none;
            opacity: 0;
            z-index: 40;
        }

        /* NOTIFICACIONES Y LOG CENTRAL */
        #action-banner {
            position: absolute;
            top: 25%;
            left: 50%;
            transform: translateX(-50%);
            font-family: 'Black Ops One', sans-serif;
            font-size: 1.4rem;
            text-align: center;
            color: #fff;
            text-shadow: 0 0 15px rgba(245, 158, 11, 0.8), 2px 2px 0 #000;
            pointer-events: none;
            z-index: 20;
            opacity: 0;
            white-space: nowrap;
        }

        /* ── CONTROLES MOBILE GAMEPAD (ARCADE LAYOUT) ── */
        #arcade-gamepad {
            background: #0d0d1a;
            border-top: 2px solid #2d2d48;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            z-index: 30;
            box-shadow: 0 -10px 25px rgba(0, 0, 0, 0.6);
        }

        /* D-PAD / CRUCETA DE MOVIMIENTO */
        .dpad-container {
            width: 120px;
            height: 120px;
            position: relative;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            flex-shrink: 0;
        }

        .dpad-btn {
            background: linear-gradient(145deg, #222238, #161625);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #94a3b8;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.4);
            touch-action: manipulation;
        }
        .dpad-btn:active {
            background: #ffaa00;
            color: #000;
            transform: scale(0.92);
        }
        .dpad-btn.center {
            background: #11111c;
            border: none;
            box-shadow: none;
        }
        .dpad-up    { grid-column: 2; grid-row: 1; border-radius: 8px 8px 2px 2px; }
        .dpad-left  { grid-column: 1; grid-row: 2; border-radius: 8px 2px 2px 8px; }
        .dpad-right { grid-column: 3; grid-row: 2; border-radius: 2px 8px 8px 2px; }
        .dpad-down  { grid-column: 2; grid-row: 3; border-radius: 2px 2px 8px 8px; }

        /* BOTONES DE ACCIÓN CLÁSICOS (PUNCH, KICK, SPECIAL) */
        .action-buttons-cluster {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: flex-end;
            flex: 1;
        }

        .btn-arcade {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.2);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            box-shadow: 0 8px 0 rgba(0, 0, 0, 0.6), 0 10px 15px rgba(0, 0, 0, 0.4);
            transition: all 0.08s ease;
            touch-action: manipulation;
        }
        .btn-arcade:active {
            transform: translateY(6px);
            box-shadow: 0 2px 0 rgba(0, 0, 0, 0.6);
        }
        .btn-arcade:disabled {
            opacity: 0.35;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: 0 4px 0 rgba(0, 0, 0, 0.4) !important;
        }

        .btn-arcade .btn-label {
            font-family: 'Black Ops One', sans-serif;
            font-size: 0.8rem;
            color: #fff;
            letter-spacing: 0.05em;
        }
        .btn-arcade .btn-dmg {
            font-size: 0.6rem;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.8);
        }

        /* Colores arcade de los botones */
        .btn-arcade.punch {
            background: radial-gradient(circle at 35% 35%, #3b82f6, #1d4ed8);
            border-color: #60a5fa;
        }
        .btn-arcade.kick {
            background: radial-gradient(circle at 35% 35%, #10b981, #047857);
            border-color: #34d399;
        }
        .btn-arcade.special {
            width: 76px;
            height: 76px;
            background: radial-gradient(circle at 35% 35%, #f43f5e, #be123c);
            border-color: #fb7185;
            box-shadow: 0 8px 0 #881337, 0 0 15px rgba(244, 63, 94, 0.5);
        }

        /* OVERLAY DE ESPERA & VICTORIA */
        #modal-fin, #modal-espera {
            position: absolute;
            inset: 0;
            background: rgba(5, 5, 12, 0.92);
            backdrop-filter: blur(8px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            z-index: 100;
            text-align: center;
        }
        .hidden { display: none !important; }

        .modal-title {
            font-family: 'Black Ops One', sans-serif;
            font-size: 2.8rem;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
            text-shadow: 0 0 20px currentColor;
        }
    </style>
</head>
<body>

    <div class="flash-screen" id="flash-effect"></div>

    <!-- TOP BAR -->
    <div id="top-bar">
        <a href="{{ route('clash.lobby') }}">← Salir al Lobby</a>
        <div style="display:flex; align-items:center; gap:8px;">
            <span>SALA: <strong style="color:var(--oro);">{{ $sala }}</strong></span>
            <span id="dot-ws" style="width:8px; height:8px; border-radius:50%; background:#ef4444; display:inline-block;"></span>
            <span id="ws-texto" style="font-size:0.7rem; color:#94a3b8;">Conectando...</span>
        </div>
    </div>

    <!-- HEALTH BARS ESTILO FIGHTING GAME -->
    <header id="cabecera-vida">
        <!-- BANDO ROJO -->
        <div class="jugador-info rojo">
            <div class="jugador-nombre rojo">🔴 ROJO ({{ $partida->rojoBando?->name ?? 'Esperando...' }})</div>
            <div class="barra-marco">
                <div id="barra-rojo" class="barra-vida rojo" style="width: 100%;"></div>
            </div>
            <div class="hp-txt"><span id="hp-rojo-num">1000</span> / 1000 HP</div>
        </div>

        <div class="vs-logo">VS</div>

        <!-- BANDO AZUL -->
        <div class="jugador-info azul">
            <div class="jugador-nombre azul">🔵 AZUL ({{ $partida->azulBando?->name ?? 'Esperando...' }})</div>
            <div class="barra-marco">
                <div id="barra-azul" class="barra-vida azul" style="width: 100%;"></div>
            </div>
            <div class="hp-txt"><span id="hp-azul-num">1000</span> / 1000 HP</div>
        </div>
    </header>

    <!-- ARENA DE PELEA -->
    <main id="arena">
        <!-- NOTIFICACIONES CENTRALES -->
        <div id="action-banner">ROUND 1</div>

        <!-- LUCHADOR ROJO -->
        <div class="fighter-wrapper" id="fighter-rojo">
            <div class="fighter">🥷</div>
            <div class="fighter-sombra"></div>
        </div>

        <!-- LUCHADOR AZUL -->
        <div class="fighter-wrapper" id="fighter-azul">
            <div class="fighter">🥷</div>
            <div class="fighter-sombra"></div>
        </div>
    </main>

    <!-- GAMEPAD MOBILE RETRO ARCADE -->
    <footer id="arcade-gamepad">
        <!-- CRUCETA D-PAD (MORTAL KOMBAT STYLE) -->
        <div class="dpad-container">
            <button class="dpad-btn dpad-up" onclick="mover('arriba')">▲</button>
            <button class="dpad-btn dpad-left" onclick="mover('izquierda')">◀</button>
            <div class="dpad-btn center"></div>
            <button class="dpad-btn dpad-right" onclick="mover('derecha')">▶</button>
            <button class="dpad-btn dpad-down" onclick="mover('abajo')">▼</button>
        </div>

        <!-- BOTONES ARCADE DE GOLPE -->
        <div class="action-buttons-cluster">
            <button class="btn-arcade punch" onclick="ejecutarAtaque('punio')" disabled>
                <span class="btn-label">PUNCH</span>
                <span class="btn-dmg">-5</span>
            </button>

            <button class="btn-arcade kick" onclick="ejecutarAtaque('patada')" disabled>
                <span class="btn-label">KICK</span>
                <span class="btn-dmg">-10</span>
            </button>

            <button class="btn-arcade special" onclick="ejecutarAtaque('especial')" disabled>
                <span class="btn-label" style="color:#ffea00;">SPECIAL</span>
                <span class="btn-dmg" style="color:#ffea00;">-20</span>
            </button>
        </div>
    </footer>

    <!-- MODAL ESPERANDO RIVAL -->
    <div id="modal-espera">
        <div style="font-size:3.5rem; margin-bottom:12px; animation: idleBreathe 1s infinite alternate;">⏳</div>
        <h2 style="font-size:1.6rem; font-weight:900; margin-bottom:6px;">Esperando Oponente...</h2>
        <p style="color:#94a3b8; font-size:0.85rem; max-width:320px; margin-bottom:14px;">
            Comparte este enlace o pulsa el botón para iniciar la pelea de inmediato:
        </p>
        <div style="background:#16162a; border:1px solid rgba(255,255,255,0.1); padding:10px 16px; border-radius:10px; font-size:0.75rem; color:#cbd5e1; word-break:break-all; max-width:340px; margin-bottom:14px; cursor:pointer;" id="copy-link" onclick="copiarEnlace()" title="Toca para copiar">
            Cargando enlace...
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:center;">
            <button onclick="iniciarPelea()" style="background:linear-gradient(135deg, #f59e0b, #d97706); color:#000; border:none; padding:12px 22px; border-radius:12px; font-weight:900; font-size:0.95rem; cursor:pointer; font-family:inherit; box-shadow:0 4px 14px rgba(245,158,11,0.4);">
                ⚔️ ¡Comenzar Pelea Ya!
            </button>
            <button onclick="copiarEnlace()" style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); color:#fff; padding:12px 18px; border-radius:12px; font-weight:700; font-size:0.85rem; cursor:pointer; font-family:inherit;">
                📋 Copiar Link Rival
            </button>
        </div>
    </div>

    <!-- MODAL FIN DE PELEA -->
    <div id="modal-fin" class="hidden">
        <h2 id="fin-titulo" class="modal-title">K.O.</h2>
        <p id="fin-desc" style="color:#cbd5e1; font-size:1.1rem; margin-bottom:24px; font-weight:700;"></p>
        <a href="{{ route('clash.lobby') }}" style="background:linear-gradient(135deg, #f43f5e, #be123c); color:#fff; text-decoration:none; padding:14px 28px; border-radius:14px; font-weight:900; font-size:1rem; box-shadow:0 6px 20px rgba(244,63,94,0.4);">
            Volver al Lobby ⚔️
        </a>
    </div>

    <!-- AUDIO SINTETIZADO WEB AUDIO API (NO REQUIERE ARCHIVOS EXTERNOS) -->
    <script>
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        function playSfx(type) {
            try {
                if (audioCtx.state === 'suspended') audioCtx.resume();
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                const now = audioCtx.currentTime;

                if (type === 'punch') {
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(160, now);
                    osc.frequency.exponentialRampToValueAtTime(40, now + 0.12);
                    gain.gain.setValueAtTime(0.8, now);
                    gain.gain.linearRampToValueAtTime(0.01, now + 0.12);
                    osc.start(now);
                    osc.stop(now + 0.12);
                } else if (type === 'kick') {
                    osc.type = 'square';
                    osc.frequency.setValueAtTime(220, now);
                    osc.frequency.exponentialRampToValueAtTime(50, now + 0.18);
                    gain.gain.setValueAtTime(0.9, now);
                    gain.gain.linearRampToValueAtTime(0.01, now + 0.18);
                    osc.start(now);
                    osc.stop(now + 0.18);
                } else if (type === 'special') {
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(350, now);
                    osc.frequency.linearRampToValueAtTime(80, now + 0.35);
                    gain.gain.setValueAtTime(1, now);
                    gain.gain.linearRampToValueAtTime(0.01, now + 0.35);
                    osc.start(now);
                    osc.stop(now + 0.35);
                } else if (type === 'ko') {
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(120, now);
                    osc.frequency.exponentialRampToValueAtTime(30, now + 0.8);
                    gain.gain.setValueAtTime(1, now);
                    gain.gain.linearRampToValueAtTime(0.01, now + 0.8);
                    osc.start(now);
                    osc.stop(now + 0.8);
                }
            } catch(e) {}
        }
    </script>

    <!-- CONFIGURACIÓN Y LÓGICA DE WEBSOCKETS + GSAP -->
    <script>
        const CONFIG = {
            sala:         @json($sala),
            bando:        @json($bando),
            usuario:      @json(auth()->user()->name),
            reverbHost:   @json(config('reverb.apps.apps.0.options.host')),
            reverbPort:   @json(config('reverb.apps.apps.0.options.port')),
            reverbScheme: @json(config('reverb.apps.apps.0.options.scheme')),
            reverbAppKey: @json(env('REVERB_APP_KEY', '')),
            csrfToken:    document.querySelector('meta[name="csrf-token"]').content,
            golpearUrl:   @json(route('clash.golpear')),
            hpMaximo:     1000,
        };

        let estado = {
            rojoHp: {{ $estadoCache['rojo_hp'] }},
            azulHp: {{ $estadoCache['azul_hp'] }},
            ganador: @json($estadoCache['ganador']),
            enCooldown: false,
            juegoActivo: false,
        };

        const fighterRojo   = document.getElementById('fighter-rojo');
        const fighterAzul   = document.getElementById('fighter-azul');
        const barraRojo     = document.getElementById('barra-rojo');
        const barraAzul     = document.getElementById('barra-azul');
        const hpRojoNum     = document.getElementById('hp-rojo-num');
        const hpAzulNum     = document.getElementById('hp-azul-num');
        const modalEspera   = document.getElementById('modal-espera');
        const modalFin      = document.getElementById('modal-fin');
        const finTitulo     = document.getElementById('fin-titulo');
        const finDesc       = document.getElementById('fin-desc');
        const actionBanner  = document.getElementById('action-banner');
        const flashEffect   = document.getElementById('flash-effect');
        const dotWs         = document.getElementById('dot-ws');
        const wsTexto       = document.getElementById('ws-texto');
        const arcadeBtns    = document.querySelectorAll('.btn-arcade');

        // URL para invitar
        const bandoRival = CONFIG.bando === 'rojo' ? 'azul' : 'rojo';
        const linkCompleto = `${window.location.origin}/jugar/${CONFIG.sala}/${bandoRival}`;
        document.getElementById('copy-link').textContent = linkCompleto;

        function copiarEnlace() {
            navigator.clipboard.writeText(linkCompleto).then(() => {
                mostrarBanner('¡Enlace Copiado! 📋', '#10b981');
            }).catch(() => {});
        }

        // RENDER DE VIDA
        function actualizarBarras(rojoHp, azulHp) {
            const pRojo = Math.max(0, Math.min(100, (rojoHp / CONFIG.hpMaximo) * 100));
            const pAzul = Math.max(0, Math.min(100, (azulHp / CONFIG.hpMaximo) * 100));
            barraRojo.style.width = pRojo + '%';
            barraAzul.style.width = pAzul + '%';
            hpRojoNum.textContent = Math.max(0, rojoHp);
            hpAzulNum.textContent = Math.max(0, azulHp);
        }

        // ANIMACIÓN DE MOVIMIENTO D-PAD (STREET FIGHTER STEP)
        function mover(dir) {
            const miLuchador = CONFIG.bando === 'rojo' ? fighterRojo : fighterAzul;
            const factor = CONFIG.bando === 'rojo' ? 1 : -1;

            if (dir === 'derecha') {
                gsap.to(miLuchador, { x: `+=${25 * factor}`, duration: 0.15, yoyo: true, repeat: 1 });
            } else if (dir === 'izquierda') {
                gsap.to(miLuchador, { x: `-=${25 * factor}`, duration: 0.15, yoyo: true, repeat: 1 });
            } else if (dir === 'arriba') {
                // Salto Mortal Kombat
                gsap.to(miLuchador, { y: -45, duration: 0.22, yoyo: true, repeat: 1, ease: 'power1.out' });
            } else if (dir === 'abajo') {
                // Agacharse
                gsap.to(miLuchador, { scaleY: 0.75, duration: 0.12, yoyo: true, repeat: 1 });
            }
        }

        // IMPACTO VISUAL Y SONIDO
        function triggerImpactoVisual(bandoDefensor, danio, tipo) {
            playSfx(tipo === 'punio' ? 'punch' : (tipo === 'patada' ? 'kick' : 'special'));

            const targetWrapper = bandoDefensor === 'rojo' ? fighterRojo : fighterAzul;
            const rect = targetWrapper.getBoundingClientRect();

            // Texto Flotante HIT
            const hitText = document.createElement('div');
            hitText.className = 'impact-hit';
            hitText.textContent = `-${danio}`;
            hitText.style.left = `${rect.left + rect.width / 2}px`;
            hitText.style.top = `${rect.top + rect.height / 3}px`;
            document.body.appendChild(hitText);
            setTimeout(() => hitText.remove(), 800);

            // Shake de Pantalla y Luchador golpeado con GSAP
            gsap.fromTo(targetWrapper, 
                { x: bandoDefensor === 'rojo' ? -15 : 15, filter: 'brightness(2)' }, 
                { x: 0, filter: 'brightness(1)', duration: 0.25, ease: 'elastic.out(1, 0.3)' }
            );

            // Flash Screen si es especial
            if (tipo === 'especial') {
                gsap.fromTo(flashEffect, { opacity: 0.7 }, { opacity: 0, duration: 0.3 });
            }
        }

        // LOG DE EVENTO / BANNER
        function mostrarBanner(texto, color = '#f59e0b') {
            actionBanner.textContent = texto;
            actionBanner.style.color = color;
            gsap.fromTo(actionBanner, 
                { opacity: 0, scale: 0.6 }, 
                { opacity: 1, scale: 1.2, duration: 0.2, yoyo: true, repeat: 1, repeatDelay: 0.8 }
            );
        }

        function procesarGolpe(data) {
            const { bando_atacante, tipo_golpe, danio, rojo_hp, azul_hp, ganador, atacante_nombre } = data;
            const bandoDefensor = bando_atacante === 'rojo' ? 'azul' : 'rojo';

            estado.rojoHp = rojo_hp;
            estado.azulHp = azul_hp;
            estado.ganador = ganador;

            actualizarBarras(rojo_hp, azul_hp);

            // Ataque con avance rápido hacia adelante
            const atacanteWrapper = bando_atacante === 'rojo' ? fighterRojo : fighterAzul;
            const dirAtaque = bando_atacante === 'rojo' ? 35 : -35;
            gsap.to(atacanteWrapper, { 
                x: `+=${dirAtaque}`, 
                duration: 0.1, 
                yoyo: true, 
                repeat: 1,
                onComplete: () => triggerImpactoVisual(bandoDefensor, danio, tipo_golpe)
            });

            if (ganador) {
                terminarPartida(ganador);
            }
        }

        function terminarPartida(ganador) {
            estado.ganador = ganador;
            arcadeBtns.forEach(b => b.disabled = true);
            playSfx('ko');

            const ganoMiBando = ganador === CONFIG.bando;
            finTitulo.textContent = ganoMiBando ? '¡VICTORIA!' : 'K.O.';
            finTitulo.style.color = ganoMiBando ? '#10b981' : '#ef4444';
            finDesc.textContent = `El bando ${ganador.toUpperCase()} ha dominado la arena.`;
            modalFin.classList.remove('hidden');

            if (ganoMiBando && window.confetti) {
                confetti({ particleCount: 120, spread: 80, origin: { y: 0.6 } });
            }
        }

        async function ejecutarAtaque(tipo) {
            if (estado.enCooldown || estado.ganador || !estado.juegoActivo) return;

            estado.enCooldown = true;
            arcadeBtns.forEach(b => b.disabled = true);

            try {
                const res = await fetch(CONFIG.golpearUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CONFIG.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ sala: CONFIG.sala, tipo_golpe: tipo }),
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    mostrarBanner(err.message || 'Error', '#ef4444');
                }
            } catch (e) {
                mostrarBanner('Sin conexión', '#ef4444');
            } finally {
                setTimeout(() => {
                    estado.enCooldown = false;
                    if (!estado.ganador && estado.juegoActivo) {
                        arcadeBtns.forEach(b => b.disabled = false);
                    }
                }, 280);
            }
        }

        function iniciarPelea() {
            if (estado.ganador) return;
            estado.juegoActivo = true;
            modalEspera.classList.add('hidden');
            arcadeBtns.forEach(b => b.disabled = false);
            mostrarBanner('FIGHT! ⚔️', '#ffea00');
        }

        // CARGA DINÁMICA DE ECHO + REVERB
        (function initEcho() {
            const wsScheme    = (CONFIG.reverbScheme === 'https' || CONFIG.reverbScheme === 'wss') ? 'wss' : 'ws';
            const wsPort      = CONFIG.reverbPort || (wsScheme === 'wss' ? 443 : 8080);
            const wsHost      = CONFIG.reverbHost;
            const enabledTLS  = wsScheme === 'wss';

            const pusherScript = document.createElement('script');
            pusherScript.src = 'https://js.pusher.com/8.4.0/pusher.min.js';
            pusherScript.onload = function () {
                const echoScript = document.createElement('script');
                echoScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.15.3/echo.iife.min.js';
                echoScript.onload = function () {
                    window.Pusher = Pusher;
                    window.Echo = new Echo({
                        broadcaster: 'reverb',
                        key: CONFIG.reverbAppKey,
                        wsHost: wsHost,
                        wsPort: wsPort,
                        wssPort: wsPort,
                        forceTLS: enabledTLS,
                        enabledTransports: ['ws', 'wss'],
                        authEndpoint: '/broadcasting/auth',
                        auth: { headers: { 'X-CSRF-TOKEN': CONFIG.csrfToken } },
                    });

                    window.Echo.connector.pusher.connection.bind('connected', () => {
                        dotWs.style.background = '#10b981';
                        wsTexto.textContent = 'En línea';
                    });
                    window.Echo.connector.pusher.connection.bind('disconnected', () => {
                        dotWs.style.background = '#ef4444';
                        wsTexto.textContent = 'Desconectado';
                    });

                    window.Echo.join(`clash.${CONFIG.sala}`)
                        .here((members) => {
                            const hayOponenteRegistrado = Boolean(@json($partida->rojo_user_id && $partida->azul_user_id));
                            if (members.length >= 2 || hayOponenteRegistrado) {
                                iniciarPelea();
                            }
                        })
                        .joining((m) => {
                            mostrarBanner(`¡${m.name} entró!`, '#10b981');
                            iniciarPelea();
                        })
                        .leaving((m) => {
                            mostrarBanner(`${m.name} salió`, '#ef4444');
                        })
                        .listen('.golpe.recibido', (e) => {
                            procesarGolpe(e);
                        });

                    // Si la partida ya estaba en curso en BD, iniciar directo
                    if (@json($partida->estado === 'en_curso')) {
                        iniciarPelea();
                    }

                    actualizarBarras(estado.rojoHp, estado.azulHp);
                    if (estado.ganador) terminarPartida(estado.ganador);
                };
                document.head.appendChild(echoScript);
            };
            document.head.appendChild(pusherScript);
        })();
    </script>
</body>
</html>
