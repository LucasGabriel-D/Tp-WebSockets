<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no,viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Sala {{ $code }}</title>
<style>
  *{box-sizing:border-box;-webkit-tap-highlight-color:transparent}[hidden]{display:none!important}
  body{margin:0;overflow:hidden;background:#000;font-family:ui-monospace,Menlo,Consolas,"Courier New",monospace;color:#fff;user-select:none;-webkit-user-select:none;text-transform:uppercase;touch-action:none}
  #stage{position:fixed;inset:0}
  #stage canvas{position:absolute;inset:0;width:100%;height:100%;display:block}
  #stage canvas{image-rendering:pixelated;image-rendering:crisp-edges}

  /* ---------- HUD estilo arcade ---------- */
  #hud{position:fixed;top:max(8px,env(safe-area-inset-top));left:max(10px,env(safe-area-inset-left));right:max(10px,env(safe-area-inset-right));
    display:grid;grid-template-columns:1fr auto 1fr;gap:12px;align-items:start;pointer-events:none;text-shadow:2px 2px 0 #000}
  .top{display:flex;gap:8px;align-items:flex-end}
  .r .top{flex-direction:row-reverse}
  .info{flex:1;min-width:0}
  .pt{width:clamp(34px,6vw,52px);height:auto;aspect-ratio:1;image-rendering:pixelated;background:linear-gradient(#3a1d5c,#120a2e);border:3px solid #fff;box-shadow:0 0 0 3px #000;flex:none}
  .r .pt{transform:scaleX(-1)}
  .nm{display:flex;justify-content:space-between;align-items:center;font-weight:900;font-size:clamp(10px,2.2vw,16px);letter-spacing:.12em;margin-bottom:3px;gap:8px}
  .nm small{color:#ffd166;font-size:.75em;letter-spacing:.2em}
  .r .nm{flex-direction:row-reverse}
  .meter{position:relative;margin-top:6px;height:clamp(7px,1.4vh,10px);width:62%;background:#120a2e;border:2px solid #fff;box-shadow:0 0 0 2px #000}
  .r .meter{margin-left:auto}
  .meter i{position:absolute;top:0;bottom:0;left:0;width:0;background:linear-gradient(#7df9ff 0 45%,#2b7bff 45%);transition:width .15s}
  .r .meter i{left:auto;right:0}
  .meter span{position:absolute;top:calc(100% + 3px);left:0;font-size:9px;letter-spacing:.2em;color:#7df9ff}
  .r .meter span{left:auto;right:0}
  .meter.full i{background:linear-gradient(#fff3b0 0 45%,#ffd166 45%);animation:full .3s infinite steps(1)}
  .meter.full span{color:#ffd166}.meter.full span::after{content:' LISTO · P'}
  @keyframes full{50%{filter:brightness(1.6)}}
  .combo{position:absolute;top:calc(100% + 26px);left:0;opacity:0;font:900 clamp(13px,2.6vw,20px) ui-monospace,monospace;letter-spacing:.12em;color:#fff;text-shadow:3px 3px 0 #000}
  .combo b{font-size:2em;color:#ffd166;margin-right:4px}
  .r .combo{left:auto;right:0}
  .combo.show{animation:cb 1.2s steps(10) both}
  @keyframes cb{0%{transform:scale(1.8);opacity:0}12%{transform:scale(1);opacity:1}75%{opacity:1}100%{opacity:0}}
  .pl{position:relative}
  .pips{display:flex;gap:4px}.pips i{width:12px;height:12px;border:2px solid #fff;background:#0008;transform:rotate(45deg)}.pips i.on{background:#ffd166;box-shadow:0 0 8px #ffd166}
  .bar{position:relative;height:clamp(14px,3vh,22px);background:#1a1030;border:3px solid #fff;box-shadow:0 0 0 3px #000,0 0 14px #ff3d7188;transform:skewX(-14deg);overflow:hidden}
  .r .bar{transform:skewX(14deg)}
  .bar b,.bar i{position:absolute;top:0;bottom:0;left:0;width:100%}
  .r .bar b,.r .bar i{left:auto;right:0}
  .bar b{background:#e53935;transition:width .5s .45s}
  .bar i{background:linear-gradient(#ffe36b 0 45%,#ffb300 45%);transition:width .08s}
  #timer{min-width:64px;text-align:center;font:900 clamp(22px,5vw,40px) ui-monospace,monospace;border:4px solid #fff;background:#120a2ecc;padding:2px 10px;box-shadow:0 0 0 4px #000}
  #timer.low{color:#ff5a4f;animation:tick .5s infinite steps(1)}
  @keyframes tick{50%{transform:scale(1.12)}}

  #banner{position:fixed;inset:0;display:grid;place-items:center;pointer-events:none;opacity:0;text-align:center;
    font:900 clamp(40px,13vw,130px)/1 ui-monospace,monospace;letter-spacing:-.03em;
    background:linear-gradient(#fff 0 40%,#ffd166 40% 62%,#ff3d71 62%);-webkit-background-clip:text;background-clip:text;color:transparent;
    filter:drop-shadow(6px 6px 0 #000) drop-shadow(0 0 24px #ff3d71)}
  #banner.show{animation:bn 1.4s steps(14) both}
  #banner.small{font-size:clamp(30px,8vw,70px)}

  /* ---------- Anunciador (trabajo guiado: canal público style-channel) ---------- */
  #anuncio{position:fixed;top:calc(max(8px,env(safe-area-inset-top)) + clamp(70px,13vh,100px));left:50%;transform:translateX(-50%);width:min(560px,90vw);
    contain:layout paint;text-align:center;pointer-events:none;text-transform:none;z-index:5}
  #target-div{padding:10px 16px;background:#0a061ee6;border:3px solid #ffd166;box-shadow:0 0 0 3px #000;font:900 clamp(13px,2.6vw,20px) ui-monospace,monospace;letter-spacing:.06em}
  #anuncio small{display:block;margin-top:4px;font-size:9px;letter-spacing:.2em;color:#ffd166;text-shadow:1px 1px 0 #000}
  #anuncio.nuevo{animation:an .5s steps(5)}
  @keyframes an{0%{transform:translate(-50%,-30px);opacity:0}100%{transform:translate(-50%,0);opacity:1}}

  /* ---------- Panel de red (F2) ---------- */
  #tools{position:fixed;left:max(10px,env(safe-area-inset-left));bottom:max(10px,env(safe-area-inset-bottom));display:flex;gap:6px;z-index:30}
  #tools button{font:900 11px ui-monospace,monospace;letter-spacing:.12em;color:#fff;background:#0a061ecc;border:2px solid #fff9;padding:7px 10px;cursor:pointer;box-shadow:2px 2px 0 #000}
  #net{position:fixed;left:max(10px,env(safe-area-inset-left));bottom:calc(max(10px,env(safe-area-inset-bottom)) + 40px);z-index:30;width:min(460px,calc(100vw - 20px));
    background:#07041acc;border:3px solid #7df9ff;box-shadow:0 0 0 3px #000;padding:10px 12px;font-size:11px;line-height:1.5;text-transform:none;user-select:text;-webkit-user-select:text}
  #net h3{margin:0 0 6px;font-size:12px;letter-spacing:.2em;color:#7df9ff;display:flex;justify-content:space-between}
  #net h3 button{background:none;border:0;color:#fff;font:inherit;cursor:pointer}
  #net dl{display:grid;grid-template-columns:auto 1fr;gap:2px 10px;margin:0}
  #net dt{color:#ffd166}#net dd{margin:0;word-break:break-all}
  #net p{margin:6px 0;color:#bfb6e6;font-size:10px}
  #nlog{margin:6px 0 0;padding:6px;background:#0008;border:1px solid #fff3;font-size:10px;white-space:pre-wrap;max-height:9.5em;overflow:auto}
  @keyframes bn{0%{transform:scale(2.6);opacity:0}15%{transform:scale(1);opacity:1}80%{opacity:1}100%{transform:scale(.92);opacity:0}}

  #ov{position:fixed;inset:0;display:grid;place-content:center;text-align:center;gap:12px;background:#0b0a2aee;padding:16px}
  #ov h1{font:900 clamp(32px,9vw,80px) ui-monospace,monospace;margin:0;text-shadow:5px 5px 0 #000,0 0 24px #ff3d71}
  #ov p{margin:0;letter-spacing:.12em;color:#7df9ff;white-space:pre-line;line-height:1.6}
  .btn{font:900 15px ui-monospace,monospace;letter-spacing:.2em;padding:14px 24px;color:#fff;background:#e53935;border:3px solid #fff;cursor:pointer;box-shadow:4px 4px 0 #000;text-transform:uppercase;touch-action:manipulation}
  .btn:active{transform:translate(3px,3px);box-shadow:1px 1px 0 #000}
  a.btn{text-decoration:none;display:inline-block;text-align:center}
  .btn.ghost{background:#0a061e;border-color:#ffffffaa}
  .btn:focus-visible{outline:3px solid #ffd166;outline-offset:3px}
  .acts{display:flex;flex-wrap:wrap;gap:12px;justify-content:center}
  #pause{position:fixed;inset:0;z-index:40;display:grid;place-items:center;background:#05031299;padding:16px}
  .pbox{display:grid;gap:12px;width:min(380px,100%);background:#0a061ef2;border:4px solid #fff;box-shadow:0 0 0 4px #000,8px 8px 0 4px #000;padding:22px;text-align:center}
  .pbox h2{margin:0;font-size:28px;letter-spacing:.2em;text-shadow:3px 3px 0 #000,0 0 18px #ff3d71}
  .pbox p,.pbox small{margin:0;color:#7df9ff;font-size:11px;letter-spacing:.08em;text-transform:none;line-height:1.5}
  .pbox small{color:#ffd166}
  #help{position:fixed;bottom:8px;width:100%;text-align:center;font-size:11px;letter-spacing:.1em;opacity:.55;pointer-events:none;text-shadow:1px 1px 0 #000}

  /* ---------- Celular: mando táctil y HUD compacto (body.touch lo pone touch.js) ---------- */
  #touch{position:fixed;inset:0;pointer-events:none;z-index:20;touch-action:none;--ps:clamp(108px,34vh,160px);--bs:clamp(44px,13vh,62px);--g:7px}
  #touch>*{pointer-events:auto;touch-action:none}
  #pad{position:absolute;left:max(14px,env(safe-area-inset-left));bottom:max(12px,env(safe-area-inset-bottom));width:var(--ps);aspect-ratio:1;border-radius:50%;
    background:radial-gradient(#ffffff1f,#0a061e88);border:3px solid #ffffff55;box-shadow:0 0 0 3px #0008}
  #pad.on{border-color:#7df9ff}
  #pad .a{position:absolute;color:#ffffff88;font-style:normal;font-size:11px;line-height:1}
  .a.u{top:7%;left:50%;transform:translateX(-50%)}.a.d{bottom:7%;left:50%;transform:translateX(-50%)}.a.l{left:8%;top:50%;transform:translateY(-50%)}.a.r{right:8%;top:50%;transform:translateY(-50%)}
  #knob{position:absolute;left:50%;top:50%;width:40%;height:40%;margin:-20% 0 0 -20%;border-radius:50%;background:radial-gradient(#fff,#8fb3ff);opacity:.85}
  #btns{position:absolute;right:max(14px,env(safe-area-inset-right));bottom:max(12px,env(safe-area-inset-bottom));display:grid;gap:var(--g);
    grid-template-columns:repeat(4,var(--bs));grid-auto-rows:var(--bs);grid-template-areas:"up spin fire sup" "pun kick blk blk"}
  .b{border-radius:50%;border:3px solid var(--c,#fff);background:#0a061e99;color:#fff;display:grid;place-content:center;padding:0;text-align:center;
    font:900 clamp(8px,2vh,11px)/1.1 ui-monospace,monospace;letter-spacing:.02em;box-shadow:0 0 0 2px #0008,inset 0 0 10px #ffffff18;text-shadow:1px 1px 0 #000}
  .b b{color:var(--c,#fff)}
  .b.blk{border-radius:999px}
  .b.sup{opacity:.4}.b.sup.ready{opacity:1;background:#ffd16633;animation:full .3s infinite steps(1)}
  .b.on{transform:scale(.9);background:#ffffff40}
  #fsbtn{display:none}
  body.touch #fsbtn{display:inline-block}
  body.touch #help{display:none}

  body.touch #hud{top:max(4px,env(safe-area-inset-top));gap:8px}
  body.touch .top{gap:5px}
  body.touch .pt{width:28px;border-width:2px;box-shadow:0 0 0 2px #000}
  body.touch .nm{font-size:10px;margin-bottom:2px;gap:4px}
  body.touch .nm small{display:none}
  body.touch .pips{gap:3px}body.touch .pips i{width:8px;height:8px;border-width:1px}
  body.touch .bar{height:10px;border-width:2px;box-shadow:0 0 0 2px #000}
  body.touch #timer{min-width:40px;font-size:18px;padding:0 6px;border-width:3px;box-shadow:0 0 0 2px #000}
  body.touch .meter{height:6px;margin-top:4px;border-width:1px;box-shadow:0 0 0 1px #000}
  body.touch .meter span{display:none}
  body.touch .combo{top:calc(100% + 6px);font-size:11px}
  body.touch #tools{top:calc(max(4px,env(safe-area-inset-top)) + 42px);bottom:auto;left:50%;transform:translateX(-50%)}
  body.touch #tools button{padding:4px 9px;font-size:14px;box-shadow:1px 1px 0 #000}
  body.touch #tools .lbl{display:none}
  body.touch #anuncio{top:calc(max(4px,env(safe-area-inset-top)) + 78px);width:min(420px,72vw)}
  body.touch #target-div{font-size:12px;padding:6px 10px}
  body.touch #net{top:calc(max(4px,env(safe-area-inset-top)) + 78px);bottom:auto;left:50%;transform:translateX(-50%);max-height:55vh;overflow:auto}
  body.touch #banner{font-size:clamp(34px,11vw,90px)}
  body.touch #ov h1{font-size:clamp(28px,8vw,56px)}
  body.touch .btn{padding:10px 16px;font-size:13px}

  /* El juego es solo en horizontal: en vertical se pide girar el celular */
  #rotate{position:fixed;inset:0;z-index:60;display:none;place-content:center;text-align:center;gap:10px;background:#0b0a2af5;font-size:16px;letter-spacing:.15em;padding:24px;line-height:1.6}
  #rotate b{font-size:46px;display:block}
  @media (orientation:portrait){body.touch #rotate{display:grid}}
</style>
@vite('resources/js/game.js')
</head>
<body data-game="{{ json_encode($game) }}">
<div id="stage"></div>

<div id="hud">
  @foreach ([0, 1] as $i)
    @if ($i === 1)<div id="timer">60</div>@endif
    <div class="pl{{ $i ? ' r' : '' }}">
      <div class="top">
        <canvas class="pt" id="p{{ $i }}" width="40" height="40"></canvas>
        <div class="info">
          <div class="nm"><span><span id="n{{ $i }}">—</span> <small id="c{{ $i }}"></small></span><span class="pips" id="w{{ $i }}"><i></i><i></i></span></div>
          <div class="bar"><b id="l{{ $i }}"></b><i id="b{{ $i }}"></i></div>
        </div>
      </div>
      <div class="meter"><i id="m{{ $i }}"></i><span>SUPER</span></div>
      <div class="combo" id="k{{ $i }}"></div>
    </div>
  @endforeach
</div>

<div id="anuncio" hidden>
  <div id="target-div"></div>
  <small>📣 ANUNCIADOR · canal público style-channel</small>
</div>

<div id="banner"></div>
<div id="ov">
  <h1 id="t"></h1><p id="s"></p>
  <div class="acts">
    <button class="btn" id="btn" hidden>REVANCHA</button>
    <button class="btn" id="copy" hidden>COPIAR ENLACE</button>
    <a class="btn ghost" href="{{ route('lobby') }}">MENÚ PRINCIPAL</a>
  </div>
</div>

<div id="pause" hidden role="dialog" aria-modal="true" aria-labelledby="pause-t">
  <div class="pbox">
    <h2 id="pause-t">MENÚ</h2>
    <p>La pelea es online: no se detiene mientras estás acá.</p>
    <button class="btn" type="button" id="resume">VOLVER A LA PELEA</button>
    <a class="btn ghost" href="{{ route('lobby') }}" id="quit">SALIR AL MENÚ PRINCIPAL</a>
    <small id="quit-note">Si salís en plena pelea, tu rival gana por abandono.</small>
  </div>
</div>
<div id="help">A/D mover · W saltar · S agacharse · L bloquear · J puño · K patada · I uppercut · O giratoria · U energía · P SUPER</div>

<div id="rotate" role="alert"><b>↻</b>GIRÁ EL CELULAR<br>PARA JUGAR EN HORIZONTAL</div>

<div id="tools">
  <button type="button" id="menubtn" aria-label="Menú">☰<span class="lbl"> MENÚ · ESC</span></button>
  <button type="button" id="netbtn" aria-label="Panel de red">📡<span class="lbl"> RED · F2</span></button>
  <button type="button" id="mute" aria-label="Sonido">🔊</button>
  <button type="button" id="fsbtn" aria-label="Pantalla completa">⛶</button>
</div>
<div id="net" hidden>
  <h3>RED · REVERB + ECHO <button type="button" id="netx" aria-label="Cerrar">✕</button></h3>
  <dl>
    <dt>Conexión</dt><dd id="nstate">—</dd>
    <dt>Socket ID</dt><dd id="nsock">—</dd>
    <dt>WebSocket</dt><dd id="nurl">—</dd>
    <dt>Canal pelea</dt><dd id="nch">—</dd>
    <dt>Canal público</dt><dd>style-channel (mensajes de /emisor)</dd>
    <dt>Ping</dt><dd id="nping">—</dd>
    <dt>Whispers</dt><dd id="nrate">—</dd>
  </dl>
  <p>La pelea viaja por <b>whispers</b>: navegador → Reverb → el otro navegador, sin pasar por PHP. Laravel solo autoriza la entrada al canal de presencia (POST /broadcasting/auth) y recibe el resultado final por HTTP.</p>
  <pre id="nlog"></pre>
</div>
</body>
</html>
