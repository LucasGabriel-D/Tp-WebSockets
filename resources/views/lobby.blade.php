<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>NEO DOJO</title>
<style>
  *{box-sizing:border-box}[hidden]{display:none!important}
  :root{--px:ui-monospace,Menlo,Consolas,"Courier New",monospace;--gold:#ffd166;--pink:#ff3d71;--cyan:#7df9ff;--red:#e53935;--ink:#0a061e;--deep:#120a2e}
  html,body{height:100%}
  body{margin:0;overflow:hidden;background:#0b0a2a;color:#fff;font-family:var(--px);text-transform:uppercase;user-select:none}
  #stage,#stage canvas{position:fixed;inset:0;width:100%;height:100%;display:block}
  #stage canvas{image-rendering:pixelated;image-rendering:crisp-edges}
  .shade{position:fixed;inset:0;pointer-events:none;background:linear-gradient(90deg,#0b0a2ad9,#0b0a2a33 40%,#0b0a2a33 60%,#0b0a2ad9),linear-gradient(#0b0a2a88,#0b0a2a00 25%,#0b0a2a00 70%,#0b0a2aee)}
  button,a,input{font-family:var(--px)}
  :focus-visible{outline:3px solid var(--gold);outline-offset:2px}
  @keyframes blink{50%{opacity:0}}

  /* ---------- Portada ---------- */
  #title{position:fixed;inset:0;display:grid;place-content:center;text-align:center;gap:12px;cursor:pointer;padding:16px}
  body[data-view=menu] #title,body[data-view=title] #menu{display:none}
  .logo{margin:0;font:900 clamp(52px,15vw,160px)/.88 var(--px);letter-spacing:-.05em}
  .logo span{display:block;background:linear-gradient(#fff 0 38%,var(--gold) 38% 62%,var(--pink) 62%);-webkit-background-clip:text;background-clip:text;color:transparent;
    filter:drop-shadow(5px 5px 0 #000) drop-shadow(0 0 22px #ff3d71aa)}
  .logo span+span{padding-left:.45em;animation:flick 5s infinite steps(1)}
  @keyframes flick{0%,92%,96%,100%{opacity:1}94%,98%{opacity:.45}}
  .tag{margin:0;letter-spacing:.35em;font-size:clamp(11px,2.4vw,18px);color:var(--cyan);text-shadow:2px 2px 0 #000}
  .start{margin-top:18px;font:900 clamp(16px,3.4vw,26px) var(--px);text-shadow:3px 3px 0 #000;animation:blink 1s infinite steps(1)}

  /* ---------- Menú ---------- */
  #menu{position:fixed;inset:0;display:grid;grid-template-rows:auto 1fr;overflow:auto;
    padding:max(12px,env(safe-area-inset-top)) max(16px,env(safe-area-inset-right)) 70px max(16px,env(safe-area-inset-left))}
  .topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
  .mini{font:900 22px/1 var(--px);letter-spacing:-.02em;color:var(--gold);text-shadow:3px 3px 0 #000,0 0 14px #ff3d7188}
  .mini b{color:var(--pink)}
  .who{display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:12px;letter-spacing:.12em}
  .who form{display:contents}
  .chip{padding:6px 10px;background:#0a061ecc;border:2px solid #fff9;box-shadow:2px 2px 0 #000}
  .chip b{color:var(--gold)}
  .small{font:900 11px var(--px);letter-spacing:.12em;padding:7px 10px;color:#fff;background:#0a061ecc;border:2px solid #fff9;cursor:pointer;box-shadow:2px 2px 0 #000;text-decoration:none;text-transform:uppercase}
  .small:hover{border-color:var(--cyan)}

  .cols{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,1fr);gap:20px;align-items:center;padding-block:16px;max-width:1180px;width:100%;margin:0 auto}
  .panel{background:#0a061ee0;border:4px solid #fff;box-shadow:0 0 0 4px #000,8px 8px 0 4px #000,0 0 30px #ff3d7144;padding:clamp(14px,2.2vw,22px);display:grid;gap:14px;align-content:start}
  .panel h2{margin:0;font-size:13px;letter-spacing:.3em;color:var(--gold);text-shadow:2px 2px 0 #000}

  /* selección de luchador */
  .roster{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
  .card{position:relative;display:grid;gap:6px;padding:6px 6px 8px;background:var(--deep);border:3px solid #ffffff55;cursor:pointer;color:#fff;text-align:center;box-shadow:3px 3px 0 #000}
  .card canvas{width:100%;aspect-ratio:1;image-rendering:pixelated;background:linear-gradient(#3a1d5c,#120a2e)}
  .card span{font:900 12px var(--px);letter-spacing:.1em}
  .card:hover{border-color:#fff}
  .card[aria-pressed=true]{border-color:var(--gold);box-shadow:0 0 0 2px #000,0 0 18px #ffd16688;transform:translateY(-3px)}
  .card[aria-pressed=true]::after{content:'P1';position:absolute;top:-10px;left:-8px;font:900 11px var(--px);background:var(--red);border:2px solid #fff;padding:2px 5px;animation:blink 1s infinite steps(1)}
  .hero{display:grid;grid-template-columns:auto 1fr;gap:16px;align-items:center}
  .hero .name{font:900 clamp(30px,5vw,48px)/1 var(--px);letter-spacing:.06em;text-shadow:3px 3px 0 #000,0 0 14px #29b6f6}
  .hero small{display:block;color:var(--gold);letter-spacing:.3em;margin-top:6px}
  .arrows{display:flex;gap:8px}
  .arrow{background:var(--red);color:#fff;border:3px solid #fff;width:42px;height:42px;font:900 18px var(--px);cursor:pointer;box-shadow:3px 3px 0 #000}
  .arrow:active{transform:translate(2px,2px);box-shadow:1px 1px 0 #000}
  .bio{margin:0;font-size:12px;line-height:1.6;letter-spacing:.06em;color:#d8d0ff;text-transform:none}
  .st{display:grid;grid-template-columns:96px 1fr;gap:10px;align-items:center;font-size:11px;letter-spacing:.15em}
  .st i{display:block;height:10px;background:#1a1030;border:2px solid #fff}
  .st b{display:block;height:100%;background:linear-gradient(#ffe36b 0 45%,#ffb300 45%);transition:width .25s steps(5)}

  /* pestañas */
  .tabs{display:grid;grid-template-columns:1fr 1fr;gap:0;border:3px solid #fff;box-shadow:3px 3px 0 #000}
  .tabs button{font:900 12px var(--px);letter-spacing:.15em;padding:11px 8px;background:var(--deep);color:#ffffffaa;border:0;cursor:pointer;text-transform:uppercase}
  .tabs button[aria-selected=true]{background:var(--red);color:#fff;text-shadow:2px 2px 0 #000}
  form{display:grid;gap:8px}
  input[type=text]{font:700 16px var(--px);text-transform:uppercase;padding:12px;background:var(--deep);color:#fff;border:3px solid #fff;outline:none;letter-spacing:.2em;text-align:center;width:100%}
  input[type=text]:focus{border-color:var(--cyan);box-shadow:0 0 14px #7df9ff88}
  .btn{font:900 15px var(--px);letter-spacing:.2em;text-transform:uppercase;padding:14px;color:#fff;background:var(--red);border:3px solid #fff;cursor:pointer;box-shadow:4px 4px 0 #000;text-shadow:2px 2px 0 #000;width:100%}
  .btn.big{background:linear-gradient(#ff5a4f,#d7263d);font-size:18px}
  .btn:hover{filter:brightness(1.15)}.btn:active{transform:translate(3px,3px);box-shadow:1px 1px 0 #000}
  .join{display:grid;grid-template-columns:1fr auto;gap:8px}
  .join .btn{width:auto;padding-inline:16px}
  .or{text-align:center;color:#ffffff88;font-size:11px;letter-spacing:.3em}
  .err{color:#ff8a80;font-size:12px;margin:0}
  .hint{margin:0;font-size:11px;line-height:1.6;letter-spacing:.08em;color:#bfb6e6;text-transform:none}

  .rooms h3{margin:2px 0 6px;font-size:11px;letter-spacing:.25em;color:var(--cyan);display:flex;justify-content:space-between}
  .live{color:var(--pink);animation:blink 1s infinite steps(1)}
  #salas{list-style:none;margin:0;padding:0;display:grid;gap:6px;max-height:160px;overflow:auto}
  #salas li{display:grid;grid-template-columns:auto 1fr auto;gap:10px;align-items:center;padding:6px 8px;background:var(--deep);border:2px solid #fff6;font-size:12px;letter-spacing:.1em}
  #salas li.nueva{animation:pop .6s steps(6)}
  @keyframes pop{0%{background:var(--gold);color:#000}100%{background:var(--deep)}}
  #salas li b{color:var(--gold);font-size:14px}
  #salas li small{color:#ffffff99}
  #salas li.empty{display:block;color:#ffffff88;text-align:center}
  #salas form{display:block}
  #salas button{font:900 11px var(--px);letter-spacing:.12em;padding:6px 10px;color:#fff;background:var(--red);border:2px solid #fff;cursor:pointer;box-shadow:2px 2px 0 #000}
  #salas button:disabled{background:#444;cursor:not-allowed}

  /* cómo se juega */
  .keys{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px 14px;font-size:11px;letter-spacing:.08em}
  .keys div{display:flex;align-items:center;gap:8px}
  kbd{display:inline-grid;place-items:center;min-width:26px;height:26px;padding:0 5px;font:900 11px var(--px);color:#000;background:#f4f1e8;border:2px solid #000;box-shadow:0 3px 0 #9a93ad;flex:none}
  kbd.sp{background:var(--gold)}
  .rules{margin:0;padding-left:18px;display:grid;gap:6px;font-size:11px;line-height:1.5;letter-spacing:.06em;color:#d8d0ff;text-transform:none}
  .rules b{color:var(--gold)}

  /* ---------- Anunciador (canal público style-channel) ---------- */
  #anuncio{position:fixed;top:calc(max(12px,env(safe-area-inset-top)) + 48px);left:50%;transform:translateX(-50%);width:min(560px,calc(100vw - 32px));z-index:5;contain:layout paint;text-align:center;text-transform:none;pointer-events:none}
  #target-div{padding:10px 16px;background:#0a061ee6;border:3px solid var(--gold);box-shadow:0 0 0 3px #000;font:900 clamp(13px,2.6vw,20px) var(--px)}
  #anuncio small{display:block;margin-top:4px;font-size:9px;letter-spacing:.2em;color:var(--gold);text-shadow:1px 1px 0 #000}
  #anuncio.nuevo{animation:an .5s steps(5)}
  @keyframes an{0%{transform:translate(-50%,-30px);opacity:0}100%{transform:translate(-50%,0);opacity:1}}

  /* ---------- Ticker de últimos combates ---------- */
  #ticker{position:fixed;left:0;right:0;bottom:0;z-index:4;display:flex;align-items:center;gap:12px;padding:6px max(16px,env(safe-area-inset-right)) calc(6px + env(safe-area-inset-bottom)) max(16px,env(safe-area-inset-left));background:#07041ae6;border-top:3px solid #fff;font-size:11px;letter-spacing:.12em;overflow:hidden;white-space:nowrap}
  #ticker .tag2{flex:none;color:#000;background:var(--gold);padding:2px 8px;font-weight:900}
  #results{display:flex;gap:28px;animation:scroll 28s linear infinite}
  #results.static{animation:none}
  #results span b{color:var(--gold)}#results span i{font-style:normal;color:var(--pink)}
  @keyframes scroll{from{transform:translateX(30%)}to{transform:translateX(-100%)}}

  @media (max-width:900px){
    .cols{grid-template-columns:1fr;align-items:start}
    .shade{background:#0b0a2acc}
  }
  @media (max-width:520px){
    .roster{gap:6px}.card span{font-size:10px}
    .keys{grid-template-columns:1fr}
  }
  /* celular */
  @media (max-width:700px){
    .lbl{display:none}
    .who{gap:6px}
    .small,.chip{padding:6px 8px}
    .hero .name{font-size:30px}
    .panel{box-shadow:0 0 0 3px #000,4px 4px 0 3px #000}
    .arrow{width:36px;height:36px;font-size:14px}
  }
  @media (max-height:520px) and (orientation:landscape){
    .lbl{display:none}
    #menu{padding-bottom:44px}
    .cols{grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:12px;padding-block:8px;align-items:start}
    .panel{padding:10px;gap:8px;box-shadow:0 0 0 3px #000,4px 4px 0 3px #000}
    .panel h2{font-size:10px}
    .card{padding:3px 3px 4px;gap:2px}.card span{font-size:9px}
    .card canvas{width:min(100%,64px);margin:0 auto}
    .hero .name{font-size:22px}.hero small{font-size:9px;margin-top:2px}
    .arrow{width:32px;height:32px;font-size:13px}
    .bio{display:none}
    .st{grid-template-columns:76px 1fr;font-size:9px;gap:6px}.st i{height:7px}
    .btn{padding:9px;font-size:13px}.btn.big{font-size:14px}
    input[type=text]{padding:8px;font-size:14px}
    .hint,.or{display:none}
    #salas{max-height:90px}
    .tabs button{padding:7px 4px;font-size:10px}
    .keys{font-size:9px}kbd{min-width:20px;height:20px;font-size:9px}
    .rules{font-size:10px}
    #ticker{padding-block:4px;font-size:9px}
    .mini{font-size:16px}
  }
  @media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
</style>
@vite('resources/js/lobby.js')
</head>
<body data-view="title" data-auth="{{ auth()->check() ? 1 : 0 }}" data-salas='@json($salas)' data-resultados='@json($resultados)' data-join="{{ route('rooms.join') }}">
<div id="stage"></div>
<div class="shade"></div>

<section id="title" aria-label="Portada">
  <h1 class="logo"><span>NEO</span><span>DOJO</span></h1>
  <p class="tag">PELEA ONLINE 1 VS 1</p>
  <div class="start">▶ TOCÁ O PRESIONÁ UNA TECLA</div>
</section>

<section id="menu" aria-label="Menú principal">
  <header class="topbar">
    <div class="mini">NEO <b>DOJO</b></div>
    <div class="who">
      @auth
        <span class="chip"><span class="lbl">JUGADOR: </span><b>{{ auth()->user()->name }}</b></span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="small" type="submit" aria-label="Cambiar nombre">✎<span class="lbl"> CAMBIAR NOMBRE</span></button></form>
      @endauth
      <a class="small" href="{{ route('emisor') }}" target="_blank" aria-label="Emisor">📣<span class="lbl"> EMISOR</span></a>
      <a class="small" href="{{ route('receptor') }}" target="_blank" aria-label="Receptor">📺<span class="lbl"> RECEPTOR</span></a>
      <button class="small" type="button" id="mute" aria-label="Sonido">🔊</button>
      <button class="small" type="button" id="back" aria-label="Volver a la portada">⏏<span class="lbl"> PORTADA</span></button>
    </div>
  </header>

  <div class="cols">
    <div class="panel">
      <h2>ELEGÍ TU LUCHADOR</h2>
      <div class="roster" id="roster" role="group" aria-label="Luchadores"></div>
      <div class="hero">
        <div><span class="name" id="cn">—</span><small id="ct"></small></div>
        <div class="arrows" style="justify-content:flex-end">
          <button type="button" class="arrow" id="prev" aria-label="Luchador anterior">◀</button>
          <button type="button" class="arrow" id="next" aria-label="Luchador siguiente">▶</button>
        </div>
      </div>
      <p class="bio" id="bio"></p>
      <div class="st"><span>PODER</span><i><b id="s0"></b></i></div>
      <div class="st"><span>VELOCIDAD</span><i><b id="s1"></b></i></div>
      <div class="st"><span>DEFENSA</span><i><b id="s2"></b></i></div>
    </div>

    <div class="panel">
      <div class="tabs" role="tablist">
        <button type="button" role="tab" id="tab-play" aria-selected="true" aria-controls="pane-play">JUGAR ONLINE</button>
        <button type="button" role="tab" id="tab-help" aria-selected="false" aria-controls="pane-help">CÓMO SE JUEGA</button>
      </div>

      <div id="pane-play" role="tabpanel" aria-labelledby="tab-play" style="display:grid;gap:12px">
        @auth
          <form method="POST" action="{{ route('rooms.create') }}">@csrf
            <input type="hidden" name="char" value="0">
            <button class="btn big">⚔ CREAR SALA</button>
          </form>
          <p class="hint">Te da un enlace para pasarle a tu rival. También aparece abajo en la lista de todos.</p>
          <div class="or">— O UNITE CON UN CÓDIGO —</div>
          <form method="POST" action="{{ route('rooms.join') }}" class="join">@csrf
            <input type="hidden" name="char" value="0">
            <input type="text" id="code" name="code" placeholder="CÓDIGO" maxlength="5" required autocomplete="off" aria-label="Código de sala">
            <button class="btn">UNIRSE</button>
          </form>
        @else
          <h2>PONÉ TU NOMBRE PARA JUGAR</h2>
          <form method="POST" action="{{ route('enter') }}">@csrf
            <input type="text" id="name" name="name" placeholder="TU NOMBRE" maxlength="16" required autocomplete="off" aria-label="Tu nombre">
            <button class="btn big">▶ ENTRAR</button>
          </form>
        @endauth
        @error('code')<p class="err">{{ $message }}</p>@enderror
        @error('name')<p class="err">{{ $message }}</p>@enderror

        <div class="rooms">
          <h3>SALAS ESPERANDO RIVAL <span class="live">● EN VIVO</span></h3>
          <ul id="salas"></ul>
        </div>
      </div>

      <div id="pane-help" role="tabpanel" aria-labelledby="tab-help" hidden style="display:grid;gap:14px">
        <h2>CONTROLES</h2>
        <div class="keys">
          <div><kbd>A</kbd><kbd>D</kbd> MOVERSE</div>
          <div><kbd>W</kbd> SALTAR</div>
          <div><kbd>S</kbd> AGACHARSE</div>
          <div><kbd>L</kbd> BLOQUEAR</div>
          <div><kbd>J</kbd> PUÑO</div>
          <div><kbd>K</kbd> PATADA</div>
          <div><kbd>I</kbd> UPPERCUT</div>
          <div><kbd>O</kbd> PATADA GIRATORIA</div>
          <div><kbd>U</kbd> BOLA DE ENERGÍA</div>
          <div><kbd class="sp">P</kbd> SUPER</div>
          <div><kbd>S</kbd>+<kbd>J</kbd> GOLPE BAJO</div>
          <div><kbd>S</kbd>+<kbd>K</kbd> BARRIDA</div>
          <div><kbd>ESC</kbd> MENÚ</div>
          <div><kbd>F2</kbd> PANEL DE RED</div>
        </div>
        <h2>REGLAS</h2>
        <ul class="rules">
          <li>Gana quien se lleva <b>2 rondas</b>. Cada ronda dura <b>60 segundos</b>; si se acaba el tiempo, gana quien tenga más vida.</li>
          <li>Pegando y recibiendo golpes se carga la barra <b>SUPER</b>. Llena, apretá <b>P</b> para lanzar una bola gigante.</li>
          <li>Bloqueando de pie frenás golpes altos; para la barrida y el golpe bajo tenés que bloquear <b>agachado</b> (S + L).</li>
          <li>Golpes seguidos suman <b>combo</b>. Ganar una ronda sin recibir daño da <b>PERFECT</b>.</li>
          <li>Para probar solo: abrí la sala en otro navegador o en una ventana de incógnito.</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<div id="anuncio" hidden>
  <div id="target-div"></div>
  <small>📣 ANUNCIADOR · canal público style-channel</small>
</div>

<div id="ticker">
  <span class="tag2">ÚLTIMOS COMBATES</span>
  <div id="results"></div>
</div>
</body>
</html>
