// Menú: escenario en vivo de fondo con dos luchadores pixel art haciendo demos, pantalla de título,
// selección de personaje y la parte "en tiempo real" del lobby (salas abiertas, resultados y anunciador).
import './bootstrap'; // window.Echo
import { WebGLRenderer, Scene, PerspectiveCamera, Color } from 'three';
import { createPost } from './arena/post.js';
import { createStage } from './arena/stage.js';
import { FX } from './arena/fx.js';
import { Fighter, DEFS, ATK } from './arena/fighter.js';
import { escucharAnunciador } from './arena/anunciador.js';
import { sfx } from './arena/sfx.js';

const $ = (id) => document.getElementById(id);
const PIXEL_H = 270;

const renderer = new WebGLRenderer({ antialias: false, powerPreference: 'high-performance' });
$('stage').appendChild(renderer.domElement);
const scene = new Scene(); scene.background = new Color(0x0b0a2a);
const camera = new PerspectiveCamera(32, 16 / 9, .5, 200);
const post = createPost(renderer), stage = createStage(scene), fx = new FX(scene);
Fighter.fx = fx;

const n = DEFS.length;
let sel = 0;
try { sel = (Number(localStorage.getItem('neoChar')) || 0) % n; } catch {}
const L = new Fighter(0, sel, scene), R = new Fighter(1, (sel + 1) % n, scene);
L.x = L.tx = -1.4; R.x = R.tx = 1.4; L.facing = 1; R.facing = -1;

// Demo: se pegan entre ellos para mostrar los movimientos
const MOVES = ['punch', 'kick', 'upper', 'spin', 'sweep'];
let nextDemo = 1.2;
function demo(dt) {
  nextDemo -= dt; if (nextDemo > 0) return;
  nextDemo = 1.4 + Math.random() * 1.2;
  const [a, b] = Math.random() < .5 ? [L, R] : [R, L], m = MOVES[Math.random() * MOVES.length | 0], at = ATK[m];
  a.set(m); setTimeout(() => a.set('idle'), at.total * 1000 + 60);
  setTimeout(() => {
    const down = m === 'sweep';
    b.set(down ? 'down' : 'hit'); fx.hit(b.x, 1.3, m === 'spin' || m === 'upper' ? 'heavy' : 'hit'); stage.cheer = .8;
    setTimeout(() => b.set('idle'), down ? 1000 : 320);
  }, at.start * 1000 + 40);
}

// Descripción corta de cada luchador (mismo orden que DEFS)
const BIO = [
  'Karateka clásico y equilibrado. Golpes fuertes y una bola de energía confiable.',
  'Ninja veloz. Ideal para entrar y salir rápido y castigar errores.',
  'Kunoichi ágil y defensiva. Buena para presionar con patadas.',
  'Luchador lento pero demoledor. Aguanta mucho y cada golpe pesa.',
];

// Tarjetas con el retrato pixel art de cada luchador (se generan con el mismo rasterizador del juego)
const noScene = { add() {} };
const cards = DEFS.map((d, i) => {
  const b = document.createElement('button');
  b.type = 'button'; b.className = 'card'; b.setAttribute('aria-pressed', 'false'); b.setAttribute('aria-label', d.id + ', ' + d.title);
  const c = document.createElement('canvas'); c.width = c.height = 40;
  const g = c.getContext('2d'); g.imageSmoothingEnabled = false; g.drawImage(new Fighter(0, i, noScene).portrait, 0, 0);
  const label = document.createElement('span'); label.textContent = d.id;
  b.append(c, label);
  b.addEventListener('click', () => { select(i); sfx.ui(); });
  $('roster').append(b);
  return b;
});

function select(i) {
  sel = ((i % n) + n) % n;
  try { localStorage.setItem('neoChar', sel); } catch {}
  L.setChar(sel, false); R.setChar((sel + 1 + (Math.random() * (n - 1) | 0)) % n, false); // rival de demo: cualquiera menos el elegido
  const d = DEFS[sel];
  $('cn').textContent = d.id; $('ct').textContent = d.title; $('bio').textContent = BIO[sel] ?? '';
  d.stats.forEach((v, k) => { $('s' + k).style.width = v * 10 + '%'; });
  cards.forEach((c, k) => c.setAttribute('aria-pressed', k === sel ? 'true' : 'false'));
  document.querySelectorAll('input[name=char]').forEach((el) => { el.value = sel; });
}
const charName = (i) => DEFS[i]?.id ?? '?';
$('prev').addEventListener('click', () => { select(sel - 1); sfx.ui(); });
$('next').addEventListener('click', () => { select(sel + 1); sfx.ui(); });
select(sel);

// Pestañas: Jugar online / Cómo se juega
function tab(which) {
  ['play', 'help'].forEach((k) => {
    $('tab-' + k).setAttribute('aria-selected', k === which ? 'true' : 'false');
    $('pane-' + k).hidden = k !== which;
  });
}
$('tab-play').addEventListener('click', () => tab('play'));
$('tab-help').addEventListener('click', () => tab('help'));

// Portada <-> menú
const go = () => { document.body.dataset.view = 'menu'; try { sessionStorage.setItem('neoGo', '1'); } catch {} sfx.ui(); };
try { if (sessionStorage.getItem('neoGo') || document.body.dataset.auth === '1') document.body.dataset.view = 'menu'; } catch {}
$('title').addEventListener('pointerdown', go);
$('back').addEventListener('click', () => { document.body.dataset.view = 'title'; try { sessionStorage.removeItem('neoGo'); } catch {} });
$('mute').textContent = sfx.muted ? '🔇' : '🔊';
$('mute').addEventListener('click', () => { $('mute').textContent = sfx.toggle() ? '🔇' : '🔊'; });
addEventListener('keydown', (e) => {
  if (document.body.dataset.view === 'title') { go(); return; }
  if (e.target.tagName === 'INPUT') return;
  if (e.code === 'ArrowLeft') select(sel - 1);
  if (e.code === 'ArrowRight') select(sel + 1);
  if (e.code === 'Escape') $('back').click();
});

/* ============ Tiempo real en el lobby: canal PÚBLICO "arena" ============ */
// Mismo patrón que el receptor del trabajo guiado: Echo.channel(...).listen('.Evento', ...).
// El controlador emite SalaActualizada al crear/llenar una sala y CombateFinalizado al terminar una pelea.
const body = document.body, csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
let salas = new Map(JSON.parse(body.dataset.salas || '[]').map((s) => [s.code, s]));
let resultados = JSON.parse(body.dataset.resultados || '[]');

function renderSalas(nueva = null) {
  const ul = $('salas'); ul.replaceChildren();
  if (!salas.size) { ul.innerHTML = '<li class="empty">No hay salas esperando rival</li>'; return; }
  for (const s of salas.values()) {
    const li = document.createElement('li'); if (s.code === nueva) li.className = 'nueva';
    const code = document.createElement('b'); code.textContent = s.code;
    const who = document.createElement('span'); who.textContent = s.host + ' ';
    const ch = document.createElement('small'); ch.textContent = charName(s.char); who.append(ch);
    const f = document.createElement('form'); f.method = 'POST'; f.action = body.dataset.join;
    for (const [n, v] of [['_token', csrf], ['code', s.code], ['char', sel]]) {
      const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; f.append(i);
    }
    const b = document.createElement('button'); b.textContent = body.dataset.auth === '1' ? 'UNIRSE' : 'ENTRÁ PRIMERO'; b.disabled = body.dataset.auth !== '1';
    f.append(b); li.append(code, who, f); ul.append(li);
  }
}
function renderResultados() {
  const el = $('results'); el.replaceChildren();
  if (!resultados.length) { el.textContent = 'Todavía no terminó ningún combate'; el.classList.add('static'); return; }
  el.classList.remove('static');
  for (const r of resultados) {
    const sp = document.createElement('span');
    sp.innerHTML = '🏆 <b></b> (<span class="c1"></span>) venció a <span class="p"></span> (<span class="c2"></span>) · <span class="m"></span> <i></i>';
    sp.querySelector('b').textContent = r.ganador; sp.querySelector('.c1').textContent = charName(r.charGanador);
    sp.querySelector('.p').textContent = r.perdedor; sp.querySelector('.c2').textContent = charName(r.charPerdedor);
    sp.querySelector('.m').textContent = r.marcador; sp.querySelector('i').textContent = r.perfect ? 'PERFECT!' : '';
    el.append(sp);
  }
}
renderSalas(); renderResultados();

window.Echo.channel('arena')
  .listen('.SalaActualizada', (e) => {
    if (e.estado === 'abierta') salas = new Map([[e.code, e], ...salas]); else salas.delete(e.code); // la más nueva arriba
    renderSalas(e.estado === 'abierta' ? e.code : null); sfx.ui();
  })
  .listen('.CombateFinalizado', (e) => { resultados = [e, ...resultados].slice(0, 6); renderResultados(); sfx.ui(); });

// Canal público del trabajo guiado (mensajes de /emisor)
escucharAnunciador($('target-div'), { alRecibir: () => sfx.ui() });

function resize() {
  const h = Math.min(PIXEL_H, innerHeight), w = Math.round(h * innerWidth / innerHeight);
  renderer.setPixelRatio(1); renderer.setSize(w, h, false); post.resize(w, h);
  camera.aspect = w / h; camera.updateProjectionMatrix();
}
addEventListener('resize', resize); resize();

let last = performance.now(), t = 0;
function frame(now) {
  requestAnimationFrame(frame);
  const dt = Math.min(.1, (now - last) / 1000); last = now; t += dt;
  demo(dt); L.follow(dt); R.follow(dt); L.draw(dt); R.draw(dt);
  fx.update(dt); stage.update(dt);
  const menu = document.body.dataset.view === 'menu';
  camera.position.set(Math.sin(t * .35) * 1.3, menu ? 2 : 1.7, menu ? 7.4 : 6.4);
  camera.lookAt(0, menu ? 1.4 : 1.1, 0);
  post.render(scene, camera);
}
requestAnimationFrame(frame);
