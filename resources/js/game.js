import './bootstrap'; // deja window.Echo listo (lo configura `php artisan install:broadcasting`)
import { WebGLRenderer, Scene, PerspectiveCamera, Color, Mesh, PlaneGeometry, MeshBasicMaterial, DoubleSide } from 'three';
import { createPost } from './arena/post.js';
import { createStage } from './arena/stage.js';
import { FX } from './arena/fx.js';
import { Fighter, S, HALF, MAX_HP, MAX_METER, ATK, ST } from './arena/fighter.js';
import { orbTexture } from './arena/pixel.js';
import { initTouch, toggleFullscreen } from './arena/touch.js';
import { sfx, announce } from './arena/sfx.js';
import { escucharAnunciador } from './arena/anunciador.js';

const cfg = JSON.parse(document.body.dataset.game); // { code, slot, me, chars } (lo arma el controlador)
const $ = (id) => document.getElementById(id);
const r2 = (v) => Math.round(v * 100) / 100;
const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const STEP = 1 / 60;      // simulación a 60 Hz fijos, sin importar los FPS
const SEND_MS = 50;       // red a 20 Hz máx
const PIXEL_H = 270;      // resolución interna (alto): más bajo = más pixelado y más liviano
const ROUND_TIME = 60, WINS = 2;
const NUM = ['CERO', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO'];

/* ============ Render ============ */
const renderer = new WebGLRenderer({ antialias: false, powerPreference: 'high-performance' });
$('stage').appendChild(renderer.domElement); // el CSS lo estira con image-rendering: pixelated
const scene = new Scene();
scene.background = new Color(0x0b0a2a);
const camera = new PerspectiveCamera(32, 16 / 9, .5, 200);
const post = createPost(renderer);
const stage = createStage(scene);
const fx = new FX(scene);
Fighter.fx = fx;

const F = [new Fighter(0, 0, scene), new Fighter(1, 1, scene)];
const me = F[cfg.slot], opp = F[1 - cfg.slot];
F.forEach((f) => { f.meter = 0; });
const chars = [cfg.chars?.[0] ?? null, cfg.chars?.[1] ?? null];
function applyChars() { // si los dos eligieron el mismo, el jugador 2 usa la paleta alternativa
  const a = chars[0] ?? 0, b = chars[1] ?? (a + 1) % 4;
  F[0].setChar(a, false); F[1].setChar(b, a === b);
  [0, 1].forEach((i) => {
    const c = $('p' + i), g = c.getContext('2d');
    g.imageSmoothingEnabled = false; g.clearRect(0, 0, c.width, c.height); g.drawImage(F[i].portrait, 0, 0, c.width, c.height);
    $('c' + i).textContent = F[i].def.id;
  });
}
applyChars();

/* ============ Input (teclado + mando táctil) ============ */
const mkKeys = () => ({ left: 0, right: 0, jump: 0, crouch: 0, block: 0, punchQ: false, kickQ: false, upperQ: false, spinQ: false, fireQ: false, superQ: false });
const keys = mkKeys(), NOKEYS = mkKeys();
const hold = (name, on) => { keys[name] = on ? 1 : 0; };
const tap = (name) => { if (phase === 'fight' && !paused) keys[name + 'Q'] = true; };
const HOLD = { KeyA: 'left', ArrowLeft: 'left', KeyD: 'right', ArrowRight: 'right', KeyW: 'jump', ArrowUp: 'jump', Space: 'jump', KeyS: 'crouch', ArrowDown: 'crouch', KeyL: 'block' };
const TAP = { KeyJ: 'punch', KeyK: 'kick', KeyI: 'upper', KeyO: 'spin', KeyU: 'fire', KeyP: 'super' };
addEventListener('keydown', (e) => {
  if (HOLD[e.code]) { hold(HOLD[e.code], true); e.preventDefault(); }
  if (!e.repeat && TAP[e.code]) tap(TAP[e.code]);
  if (!e.repeat && e.code === 'F2') { e.preventDefault(); toggleNet(); }
  if (!e.repeat && e.code === 'Escape') { e.preventDefault(); togglePause(); }
  if (!e.repeat && e.code === 'KeyM') muteBtn();
});
addEventListener('keyup', (e) => { if (HOLD[e.code]) hold(HOLD[e.code], false); });
addEventListener('blur', () => ['left', 'right', 'jump', 'crouch', 'block'].forEach((k) => hold(k, false)));
initTouch({ hold, tap });

/* ============ HUD ============ */
const shown = [-1, -1], shownM = [-1, -1]; let shownT = -1;
function hud() {
  for (let i = 0; i < 2; i++) {
    if (F[i].hp !== shown[i]) { shown[i] = F[i].hp; $('b' + i).style.width = F[i].hp + '%'; $('l' + i).style.width = F[i].hp + '%'; }
    const m = Math.round(F[i].meter ?? 0);
    if (m !== shownM[i]) {
      shownM[i] = m; $('m' + i).style.width = m + '%'; $('m' + i).parentElement.classList.toggle('full', m >= MAX_METER);
      if (i === cfg.slot) document.querySelector('#touch .sup')?.classList.toggle('ready', m >= MAX_METER);
    }
  }
}
function overlay(title, sub = '', btn = false, copy = false) {
  $('t').textContent = title; $('s').textContent = sub;
  $('btn').hidden = !btn; $('copy').hidden = !copy; $('ov').hidden = !title;
}
function banner(txt, small = false) {
  const b = $('banner'); b.textContent = txt; b.classList.toggle('small', small);
  b.classList.remove('show'); void b.offsetWidth; b.classList.add('show');
}
const pips = () => [0, 1].forEach((i) => $('w' + i).querySelectorAll('i').forEach((p, k) => p.classList.toggle('on', k < wins[i])));
const names = () => { members.forEach((m) => { $('n' + m.slot).textContent = m.name; }); refreshWait(); netRender(); };

// Contador de combos estilo arcade: golpes conectados con menos de 1,1 s entre uno y otro
const combo = [{ n: 0, t: 0 }, { n: 0, t: 0 }];
function comboHit(slot) {
  const c = combo[slot], now = performance.now();
  c.n = now - c.t < 1100 ? c.n + 1 : 1; c.t = now;
  if (c.n < 2) return;
  const el = $('k' + slot); el.innerHTML = `<b>${c.n}</b> HITS`;
  el.classList.remove('show'); void el.offsetWidth; el.classList.add('show');
}
const gainMeter = (f, v) => { f.meter = Math.min(MAX_METER, (f.meter ?? 0) + v); };

/* ============ Efectos de impacto ============ */
let hs = 0, slowT = 0, slow = 1, zoomK = 0, shake = 0, camX = 0, camZ = 10, darkT = 0;
function impact(x, y, kind) {
  fx.hit(x, y, kind);
  hs = kind === 'heavy' ? .09 : kind === 'block' ? .03 : .06;               // "hit-stop": congela la animación un instante
  shake = Math.max(shake, kind === 'heavy' ? .2 : kind === 'block' ? .05 : .12);
  zoomK = Math.max(zoomK, kind === 'heavy' ? 1 : .5);
  if (kind !== 'block') post.uni.flash.value = .3;
  stage.cheer = Math.min(1, stage.cheer + .25);
  if (kind === 'block') sfx.block(); else sfx.hit(kind === 'heavy');
}

/* ============ Proyectiles (bola de energía pixel art) ============ */
// Cada cliente simula la bola. El que la RECIBE aplica el daño (igual que los golpes).
const ORB = [orbTexture([0xffffff, 0x7df9ff, 0x2b7bff]), orbTexture([0xffffff, 0xffd166, 0xff3d9a])];
ORB.forEach((t) => t.repeat.set(.25, 1));
const orbMat = ORB.map((map) => new MeshBasicMaterial({ map, alphaTest: .5, side: DoubleSide }));
const orbGeo = new PlaneGeometry(.75, .75);
const PROJ = Array.from({ length: 6 }, () => {
  const m = new Mesh(orbGeo, orbMat[0]); m.visible = false; scene.add(m);
  return { m, on: false, x: 0, y: 0, dir: 1, t: 0, mine: false, big: false };
});
function spawnProj(x, y, dir, mine, big = false) {
  const p = PROJ.find((q) => !q.on); if (!p) return;
  Object.assign(p, { on: true, x, y, dir, t: 0, mine, big });
  p.m.material = orbMat[big ? 1 : 0]; p.m.visible = true; p.m.position.set(x, y, .1);
  p.m.scale.set(dir * (big ? 2 : 1), big ? 2 : 1, 1);
  sfx.fire(big);
}
const kill = (p) => { p.on = false; p.m.visible = false; };
function projClear() { PROJ.forEach(kill); }
let orbFrame = 0;
function projStep() {
  for (const p of PROJ) {
    if (!p.on) continue;
    p.x += p.dir * (p.big ? 6 : 7) * STEP; p.t += STEP;
    const tg = p.mine ? opp : me, rad = p.big ? .75 : .45;
    // Dos bolas que chocan se anulan (como en los juegos de pelea clásicos)
    const clash = PROJ.find((q) => q !== p && q.on && q.mine !== p.mine && Math.abs(q.x - p.x) < .5 && Math.abs(q.y - p.y) < .6);
    if (clash) {
      fx.hit((p.x + clash.x) / 2, p.y, 'heavy'); sfx.hit(true);
      if (p.big && !clash.big) kill(clash);              // el SUPER atraviesa una bola común
      else if (!p.big && clash.big) { kill(p); continue; }
      else { kill(p); kill(clash); continue; }
    }
    if (Math.abs(p.x - tg.x) < rad && p.y >= tg.y - .2 && p.y <= tg.y + tg.hurtH() + .2 && tg.st !== S.ko && tg.st !== S.down) {
      kill(p);
      if (p.mine) { impact(p.x, p.y, p.big ? 'heavy' : 'hit'); gainMeter(me, 6); comboHit(cfg.slot); }
      else onHit({ d: p.big ? 18 : 8, dir: p.dir, low: false, kd: p.big });
    } else if (p.t > 2 || Math.abs(p.x) > HALF + 3) kill(p);
    else {
      p.m.position.x = p.x;
      fx.emit(p.x - p.dir * .3, p.y + (Math.random() - .5) * .3, 0, -p.dir * 1.5, 0, 0, .25, .1, p.big ? 0xffd166 : 0x7df9ff, 0);
    }
  }
}

/* ============ Partida: rondas, tiempo y red ============ */
let phase = 'wait'; // wait | intro | fight | roundend | over
let round = 1, wins = [0, 0], timeLeft = ROUND_TIME, matchNo = 1, lastPerfect = false;
let members = [], rematch = [false, false], ch = null;

function tryStart() { if (phase === 'wait' && members.length >= 2) startRound(); }
function startRound() {
  F.forEach((f) => f.reset()); projClear(); applyChars();
  timeLeft = ROUND_TIME; phase = 'intro'; overlay('');
  const final = wins[0] === WINS - 1 && wins[1] === WINS - 1;
  banner(final ? 'RONDA FINAL' : 'RONDA ' + round);
  announce(final ? 'Ronda final' : 'Ronda ' + (NUM[round] ?? round));
  setTimeout(() => {
    if (phase !== 'intro') return;
    banner('¡PELEA!'); announce('¡Pelea!');
    setTimeout(() => { if (phase === 'intro') phase = 'fight'; }, 500);
  }, 1400);
}
function endRound(w, why) {
  if (phase !== 'fight') return;
  phase = 'roundend';
  if (w >= 0) { wins[w]++; pips(); }
  lastPerfect = why === 'ko' && w >= 0 && F[w].hp >= MAX_HP;
  banner(why === 'time' ? 'TIEMPO' : 'K.O.'); announce(why === 'time' ? 'Tiempo' : 'K. O.');
  if (why === 'ko') { slowT = 1; sfx.ko(); }
  if (lastPerfect) setTimeout(() => { banner('PERFECT!'); announce('¡Perfecto!'); }, 1300);
  stage.cheer = 1;
  setTimeout(() => {
    const win = wins.findIndex((x) => x >= WINS);
    if (win >= 0) matchOver(win); else { round++; startRound(); }
  }, lastPerfect ? 3300 : 2600);
}
function matchOver(w) {
  phase = 'over';
  overlay(w === cfg.slot ? '¡VICTORIA!' : 'DERROTA', `Rondas ${wins[cfg.slot]} - ${wins[1 - cfg.slot]}`, members.length === 2);
  announce(w === cfg.slot ? '¡Ganaste!' : 'Perdiste');
  reportResult(w);
}
function forfeit() {
  phase = 'over'; projClear(); overlay('¡GANASTE!', 'Tu rival se desconectó');
  wins[cfg.slot] = WINS; lastPerfect = false; reportResult(cfg.slot);
}
function tryRematch() {
  if (phase === 'over' && rematch[0] && rematch[1] && members.length === 2) {
    wins = [0, 0]; round = 1; matchNo++; rematch = [false, false]; pips(); $('btn').disabled = false;
    F.forEach((f) => { f.meter = 0; }); startRound();
  }
}
$('btn').addEventListener('click', () => {
  rematch[cfg.slot] = true; tx('rematch', {});
  $('btn').disabled = true; $('s').textContent = 'Esperando al rival…';
  tryRematch();
});
$('copy').addEventListener('click', () => {
  navigator.clipboard?.writeText(location.href); $('copy').textContent = '¡COPIADO!';
  setTimeout(() => { $('copy').textContent = 'COPIAR ENLACE'; }, 1500);
});

// Resultado -> Laravel (HTTP). El controlador lo guarda y lo EMITE por el canal público 'arena'
// con el evento CombateFinalizado: así el lobby de todos se entera sin recargar (igual que emisor -> receptor).
// Lo mandan los dos jugadores; el servidor ignora el duplicado.
function reportResult(winner) {
  const url = `/sala/${cfg.code}/resultado`;
  log('HTTP', `POST ${url} (ganador: jugador ${winner + 1})`);
  fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
    body: JSON.stringify({ match: matchNo, winner, wins, perfect: lastPerfect }),
  }).then((r) => log('HTTP', `${r.status} ← ${r.status === 202 ? 'emitido CombateFinalizado en canal "arena"' : 'ya lo había informado el rival'}`))
    .catch(() => log('HTTP', 'error al informar el resultado'));
}

function timeUp() { // el jugador 1 decide y avisa, así los dos coinciden
  const w = F[0].hp === F[1].hp ? -1 : F[0].hp > F[1].hp ? 0 : 1;
  tx('timeup', { w }); endRound(w, 'time');
}
function tickTimer(dt) {
  if (phase !== 'fight') return;
  timeLeft -= dt;
  const s = Math.max(0, Math.ceil(timeLeft));
  if (s !== shownT) { shownT = s; $('timer').textContent = s; $('timer').classList.toggle('low', s <= 10); }
  if (cfg.slot === 0 && timeLeft <= 0) timeUp();
  else if (cfg.slot === 1 && timeLeft <= -1.5) timeUp(); // respaldo si no llegó el aviso
}

/* ============ Red: whispers por el canal de presencia ============ */
// tx/rx envuelven ch.whisper / listenForWhisper solo para contar mensajes y mostrarlos en el panel de red (F2)
const net = { out: 0, in: 0, outRate: 0, inRate: 0, ping: null, log: [] };
function tx(event, data) {
  if (!ch) return;
  ch.whisper(event, data); // whisper = cliente → Reverb → el otro cliente. No pasa por PHP.
  net.out++;
  if (event !== 'state' && event !== 'ping' && event !== 'pong') log('↑', `whisper "${event}" ${JSON.stringify(data)}`);
}
const rx = (event, fn) => (data) => {
  net.in++;
  if (event !== 'state' && event !== 'ping' && event !== 'pong') log('↓', `whisper "${event}" ${JSON.stringify(data)}`);
  fn(data);
};

// Envío del estado propio: solo si cambió algo (o latido cada 1 s), máx 20 Hz
let lastSend = 0, lastSig = '';
function send(now, force = false) {
  if (!ch || phase === 'wait') return;
  const a = [r2(me.x), r2(me.y), me.facing, me.st, me.hp, me.alt ? 1 : 0, Math.round(me.meter)];
  const sig = a.join();
  if (!force && (now - lastSend < SEND_MS || (sig === lastSig && now - lastSend < 1000))) return;
  lastSend = now; lastSig = sig;
  tx('state', a);
}
function onState(a) {
  opp.tx = a[0]; opp.ty = a[1]; opp.facing = a[2];
  if (opp.st !== a[3]) { opp.st = a[3]; opp.stT = 0; }
  opp.hp = a[4]; opp.alt = !!a[5]; opp.meter = a[6] ?? 0;
  if (!opp.seen) { opp.seen = true; opp.x = a[0]; opp.y = a[1]; }
}
function onHit(h) {
  if (phase !== 'fight' || me.st === S.ko) return;
  const r = me.hurt(h);
  if (!r) return;
  impact(me.x, me.y + 1.2, r === 'block' ? 'block' : h.d >= 10 ? 'heavy' : 'hit');
  gainMeter(me, r === 'block' ? 3 : 5);
  if (r !== 'block') comboHit(1 - cfg.slot);
  if (me.hp <= 0) { send(performance.now(), true); tx('ko', {}); endRound(1 - cfg.slot, 'ko'); }
}
function setMember(m) { chars[m.slot] = m.char; applyChars(); }

let connState = 'conectando';
function refreshWait() { // pantalla de espera con diagnóstico de conexión
  if (phase !== 'wait') return;
  const st = connState === 'connected' ? `CONECTADO · ${members.length}/2 JUGADORES` : `CONECTANDO AL SERVIDOR… (${connState})`;
  overlay('SALA ' + cfg.code, st + '\nPara probar solo: abrí este enlace en OTRO navegador o en incógnito\n(la misma sesión cuenta como un solo jugador)', false, true);
}
refreshWait();

// Canal de PRESENCIA (privado): antes de entrar, Echo hace POST /broadcasting/auth y Laravel ejecuta
// la regla de routes/channels.php (solo los 2 jugadores de la sala). Echo le agrega el prefijo "presence-".
ch = window.Echo.join(`fight.${cfg.code}`)
  .here((u) => { members = u; u.forEach(setMember); names(); tryStart(); log('WS', `suscripto a presence-fight.${cfg.code} (${u.length}/2)`); })
  .joining((u) => { members.push(u); setMember(u); names(); tryStart(); log('WS', `entró ${u.name}`); })
  .leaving((u) => {
    members = members.filter((m) => m.id !== u.id); refreshWait(); netRender(); log('WS', `salió ${u.name}`);
    if (phase === 'over') $('btn').hidden = true; else if (phase !== 'wait') forfeit();
  })
  .listenForWhisper('state', rx('state', onState))
  .listenForWhisper('hit', rx('hit', onHit))
  .listenForWhisper('fire', rx('fire', (a) => spawnProj(a.x, a.y, a.dir, false, !!a.big)))
  .listenForWhisper('ko', rx('ko', () => endRound(cfg.slot, 'ko')))
  .listenForWhisper('timeup', rx('timeup', (a) => endRound(a.w, 'time')))
  .listenForWhisper('rematch', rx('rematch', () => { rematch[1 - cfg.slot] = true; tryRematch(); }))
  .listenForWhisper('ping', rx('ping', (a) => tx('pong', a)))
  .listenForWhisper('pong', rx('pong', (a) => { net.ping = Math.round(performance.now() - a.t); }))
  .error(() => overlay('SIN ACCESO', 'No pudiste entrar a esta sala (falló la autorización del canal).'));
window.Echo.connector?.pusher?.connection?.bind('state_change', ({ current }) => { connState = current; refreshWait(); netRender(); log('WS', `conexión: ${current}`); });

// Canal PÚBLICO del trabajo guiado: los mensajes de /emisor aparecen en plena pelea
escucharAnunciador($('target-div'), { ocultarEn: 7000, alRecibir: (e) => { sfx.ui(); log('↓', `canal público "style-channel" → RemoteStyleUpdate "${e.newText}"`); } });

/* ============ Panel de red (F2) ============ */
function log(dir, msg) {
  const t = new Date().toLocaleTimeString('es-AR', { hour12: false });
  net.log.unshift(`${t} ${dir} ${msg}`); net.log.length = Math.min(net.log.length, 9);
  if (!$('net').hidden) $('nlog').textContent = net.log.join('\n');
}
function netRender() {
  if ($('net').hidden) return;
  const o = window.Echo.options ?? {}, tls = o.forceTLS;
  $('nstate').textContent = connState;
  $('nsock').textContent = window.Echo.socketId?.() ?? '—';
  $('nurl').textContent = `${tls ? 'wss' : 'ws'}://${o.wsHost}:${tls ? o.wssPort : o.wsPort}/app/${o.key}`;
  $('nch').textContent = `presence-fight.${cfg.code} (${members.length}/2)`;
  $('nping').textContent = net.ping == null ? '—' : net.ping + ' ms';
  $('nrate').textContent = `↑ ${net.outRate}/s   ↓ ${net.inRate}/s`;
  $('nlog').textContent = net.log.join('\n');
}
function toggleNet() { $('net').hidden = !$('net').hidden; netRender(); }
$('netbtn').addEventListener('click', toggleNet);
$('netx').addEventListener('click', toggleNet);
setInterval(() => {
  net.outRate = net.out; net.inRate = net.in; net.out = net.in = 0;
  if (members.length === 2) tx('ping', { t: performance.now() });
  netRender();
}, 1000);
/* ============ Menú de pausa (Esc) ============ */
// No pausa la simulación (el rival sigue jugando): solo frena tus controles y ofrece salir al menú.
let paused = false;
function togglePause(force) {
  paused = force ?? !paused;
  $('pause').hidden = !paused;
  ['left', 'right', 'jump', 'crouch', 'block'].forEach((k) => hold(k, false));
  $('quit-note').hidden = !(phase === 'fight' || phase === 'intro' || phase === 'roundend');
  if (paused) $('resume').focus();
}
$('menubtn').addEventListener('click', () => togglePause());
$('fsbtn').addEventListener('click', () => { toggleFullscreen(); setTimeout(resize, 300); });
$('resume').addEventListener('click', () => togglePause(false));
$('pause').addEventListener('click', (e) => { if (e.target.id === 'pause') togglePause(false); });

function muteBtn() { $('mute').textContent = sfx.toggle() ? '🔇' : '🔊'; }
$('mute').textContent = sfx.muted ? '🔇' : '🔊';
$('mute').addEventListener('click', muteBtn);

/* ============ Loop: simulación fija + render con post-proceso ============ */
// El canvas toma el tamaño del contenedor #stage (en el celular vertical ocupa solo la parte de arriba)
function resize() {
  const box = $('stage'), W = box.clientWidth || innerWidth, H = box.clientHeight || innerHeight;
  const h = Math.min(PIXEL_H, H), w = Math.round(h * W / H);
  renderer.setPixelRatio(1);
  renderer.setSize(w, h, false);
  post.resize(w, h);
  camera.aspect = w / h; camera.updateProjectionMatrix();
}
let touchLand = false;
const orient = () => { touchLand = document.body.classList.contains('touch') && innerWidth > innerHeight; };
addEventListener('resize', () => { orient(); resize(); }); orient(); resize();

function simulate(now) {
  const hit = me.step(STEP, phase === 'fight' && !paused ? keys : NOKEYS, opp);
  if (hit && hit.fire) { spawnProj(hit.fire.x, hit.fire.y, hit.fire.dir, true, hit.fire.big); tx('fire', hit.fire); }
  else if (hit) {
    tx('hit', hit); impact(opp.x, opp.y + 1.2, hit.d >= 10 ? 'heavy' : 'hit');
    gainMeter(me, 8); comboHit(cfg.slot); send(now, true);
  }
  projStep();
  opp.follow(STEP);
  send(now);
}

// Sonidos y "super freeze" según el estado de cada luchador (sirve igual para el local y el remoto)
const lastSt = [S.idle, S.idle];
function cues() {
  F.forEach((f, i) => {
    if (f.st === lastSt[i]) return;
    lastSt[i] = f.st;
    const a = ATK[ST[f.st]];
    if (f.st === S.super) { darkT = .55; hs = .25; banner('SUPER!', true); sfx.charge(); announce('¡Súper!'); }
    else if (a && !a.proj) sfx.whoosh();
  });
}

let last = performance.now(), acc = 0;
function frame(now) {
  requestAnimationFrame(frame);
  const dt = Math.min(.25, (now - last) / 1000); last = now; acc += dt;
  while (acc >= STEP) { simulate(now); acc -= STEP; }
  cues();

  hs = Math.max(0, hs - dt);
  if (slowT > 0) { slowT -= dt; slow = slowT > 0 ? .3 : 1; }
  const vdt = dt * (hs > 0 ? .06 : 1) * slow; // tiempo "visual": hit-stop y cámara lenta en el K.O.
  tickTimer(dt);
  F[0].draw(vdt); F[1].draw(vdt);
  orbFrame += dt * 14; const fr = (orbFrame | 0) % 4 * .25; ORB.forEach((t) => { t.offset.x = fr; });
  fx.update(dt); stage.update(dt); hud();

  // Cámara: sigue a los dos, se aleja si se separan, tiembla y hace zoom al pegar
  const mid = (F[0].x + F[1].x) / 2, gap = Math.abs(F[0].x - F[1].x);
  camX += (mid * .8 - camX) * Math.min(1, dt * 5);
  // Si la pantalla es angosta (celular vertical) la cámara se aleja lo justo para que entren los dos
  const fit = (Math.min(gap, 10) / 2 + 1.6) / (Math.tan(16 * Math.PI / 180) * camera.aspect);
  camZ += (Math.max(7.2 + Math.min(gap, 10) * .55, fit) - camZ) * Math.min(1, dt * 4);
  shake = Math.max(0, shake - dt * .9); zoomK = Math.max(0, zoomK - dt * 3);
  const sx = shake ? (Math.random() - .5) * shake * 1.4 : 0, sy = shake ? (Math.random() - .5) * shake : 0;
  const low = touchLand ? .5 : 0; // celular horizontal: la pelea sube para que el mando no tape los pies
  camera.position.set(camX + sx, 1.8 - low + sy, camZ - zoomK * .7);
  camera.fov = 32 - zoomK * 1.5; camera.updateProjectionMatrix();
  camera.lookAt(camX, 1.15 - low * 1.2, 0);

  darkT = Math.max(0, darkT - dt);
  post.uni.dark.value = Math.min(1, darkT * 3);
  post.uni.flash.value = Math.max(0, post.uni.flash.value - dt * 5);
  post.uni.grey.value = slowT > 0 ? .5 : 0;
  post.render(scene, camera);
}
requestAnimationFrame(frame);
