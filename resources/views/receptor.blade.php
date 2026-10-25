<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Receptor · NEO DOJO</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
  *{box-sizing:border-box}
  :root{--px:ui-monospace,Menlo,Consolas,"Courier New",monospace}
  body{margin:0;min-height:100vh;background:#0b0a2a radial-gradient(circle at 50% -20%,#1d3a5c,#0b0a2a 60%);color:#fff;font-family:var(--px);padding:24px 16px}
  .wrap{max-width:760px;margin:0 auto;display:grid;gap:16px}
  h1{margin:0;font-size:clamp(28px,6vw,48px);letter-spacing:.05em;text-shadow:4px 4px 0 #000,0 0 18px #29b6f6}
  .sub{margin:0;color:#7df9ff;letter-spacing:.12em;font-size:12px}
  h2{margin:0 0 8px;font-size:12px;letter-spacing:.2em;color:#ffd166}
  .box{background:#0a061ecc;border:4px solid #fff;box-shadow:0 0 0 4px #000,8px 8px 0 4px #000;padding:16px}
  #target-div{padding:20px;border:1px dashed #bbb;border-radius:12px}
  #log{margin:0;font-size:11px;line-height:1.6;white-space:pre-wrap;max-height:40vh;overflow:auto;color:#d8d0ff}
</style>
</head>
<body>
<div class="wrap">
  <div>
    <h1>📺 RECEPTOR</h1>
    <p class="sub">TRABAJO GUIADO · escucha los canales públicos en vivo · <span id="estado">conectando…</span></p>
  </div>

  <div class="box">
    <h2>CANAL "style-channel" · EVENTO .RemoteStyleUpdate</h2>
    <div id="target-div">Esperando mensaje...</div>
  </div>

  <div class="box">
    <h2>MONITOR DE EVENTOS (style-channel + arena)</h2>
    <pre id="log">Todavía no llegó nada. Mandá algo desde /emisor o terminá una pelea.</pre>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const targetDiv = document.getElementById('target-div');
    const logEl = document.getElementById('log');
    const lines = [];
    const log = (canal, evento, data) => {
      lines.unshift(`${new Date().toLocaleTimeString()}  [${canal}] ${evento}\n  ${JSON.stringify(data)}`);
      logEl.textContent = lines.slice(0, 20).join('\n');
    };

    // Suscripción al canal público y escucha del evento (igual que en el práctico)
    window.Echo.channel('style-channel')
      .listen('.RemoteStyleUpdate', (e) => {
        // El nombre con punto (.) escucha usando broadcastAs()
        targetDiv.innerText = e.newText ?? '';
        targetDiv.style.cssText = e.newStyle ?? '';
        log('style-channel', 'RemoteStyleUpdate', e);
      });

    // Canal público del lobby del juego
    window.Echo.channel('arena')
      .listen('.SalaActualizada', (e) => log('arena', 'SalaActualizada', e))
      .listen('.CombateFinalizado', (e) => log('arena', 'CombateFinalizado', e));

    window.Echo.connector.pusher.connection.bind('state_change', ({ current }) => {
      document.getElementById('estado').textContent = current === 'connected'
        ? `conectado · socket ${window.Echo.socketId()}`
        : current;
    });
  });
</script>
</body>
</html>
