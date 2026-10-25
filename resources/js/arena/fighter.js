// Luchador: sprite PIXEL ART 2D generado por código. Un esqueleto 2D (cadera, torso, brazos, piernas con IK)
// se anima por keyframes y cada cuadro se "rasteriza" píxel por píxel en un canvas chico (ver pixel.js).
// También contiene la simulación de combate (física, golpes, bloqueo).
import { Group, Mesh, PlaneGeometry, CircleGeometry, MeshBasicMaterial, DoubleSide } from 'three';
import { TAU, clamp, EASE } from './common.js';
import { Raster, palette, pixelTexture, rgba } from './pixel.js';

/* ============ Constantes de juego ============ */
export const HALF = 5.5, MAX_HP = 100, SPEED = 4, JUMP = 9.5, GRAV = 26, MAX_METER = 100;
// h = altura a la que pega · low = solo se bloquea agachado · kd = derriba · proj = lanza proyectil
export const ATK = {
  punch: { start: .07, active: .09, total: .32, reach: 1.0,  dmg: 6,  h: 1.35 },
  kick:  { start: .13, active: .12, total: .50, reach: 1.45, dmg: 10, h: 1.0 },
  low:   { start: .08, active: .08, total: .30, reach: .95,  dmg: 5,  h: .35, low: true },
  sweep: { start: .16, active: .14, total: .62, reach: 1.5,  dmg: 8,  h: .2,  low: true, kd: true },
  air:   { start: .05, active: .40, total: .60, reach: 1.0,  dmg: 9,  h: .7 },
  upper: { start: .10, active: .10, total: .45, reach: .95,  dmg: 12, h: 1.3 },
  spin:  { start: .18, active: .14, total: .60, reach: 1.6,  dmg: 14, h: 1.0 },
  fire:  { start: .22, active: 0,   total: .65, reach: 0,    dmg: 0,  h: 0, proj: true },
  super: { start: .40, active: 0,   total: .95, reach: 0,    dmg: 0,  h: 0, proj: true, big: true },
};
// El índice de estado viaja por la red: los nuevos se agregan AL FINAL para no romper compatibilidad
export const ST = ['idle', 'walk', 'back', 'crouch', 'jump', 'punch', 'kick', 'low', 'sweep', 'air', 'upper', 'spin', 'fire', 'block', 'cblock', 'hit', 'down', 'ko', 'super'];
export const S = {}; ST.forEach((n, i) => (S[n] = i));
export const CROUCHY = new Set([S.crouch, S.cblock, S.low, S.sweep]);

/* ============ Personajes ============ */
export const DEFS = [
  { id: 'TORA', title: 'KARATEKA', stats: [8, 6, 6], skin: 0xf0b27a, hair: 0x15131a, hairStyle: 'spike', top: 0xf4f1e8, vneck: true,
    pants: 0xf4f1e8, flare: true, shorts: false, belt: 0x1a1a1a, wrap: 0xffffff, gloves: false, shoes: null, band: 0xe53935, scarf: null,
    mask: false, beard: false, sleeves: false, size: 1.0, bulk: 1.1, alt: { top: 0xff8a3d, pants: 0xff8a3d, band: 0x29b6f6, belt: 0xffffff } },
  { id: 'KAGE', title: 'NINJA', stats: [6, 9, 5], skin: 0xe3b48c, hair: 0x111111, hairStyle: 'hood', top: 0x2b3a67, vneck: false,
    pants: 0x2b3a67, flare: false, shorts: false, belt: 0xd7263d, wrap: 0xd7263d, gloves: true, shoes: 0x101018, band: 0xd7263d, scarf: 0xd7263d,
    mask: true, beard: false, sleeves: true, size: 1.03, bulk: .9, alt: { belt: 0x29b6f6, wrap: 0x29b6f6, band: 0x29b6f6, scarf: 0x29b6f6 } },
  { id: 'AKARI', title: 'KUNOICHI', stats: [5, 8, 7], skin: 0xf6cfa8, hair: 0x7b2ff7, hairStyle: 'pony', top: 0x1fb5a8, vneck: false,
    pants: 0x1fb5a8, flare: false, shorts: true, belt: 0xffffff, wrap: 0xffffff, gloves: true, shoes: 0xffffff, band: 0xffffff, scarf: null,
    mask: false, beard: false, sleeves: false, size: .96, bulk: .86, alt: { top: 0xff5c8a, pants: 0xff5c8a, hair: 0x2b2d42 } },
  { id: 'BRUTO', title: 'LUCHADOR', stats: [10, 3, 8], skin: 0xc68642, hair: 0x241a14, hairStyle: 'bald', top: null, vneck: false,
    pants: 0xd7263d, flare: false, shorts: false, belt: 0xffd166, wrap: 0x111111, gloves: true, shoes: 0x111111, band: null, scarf: null,
    mask: false, beard: true, sleeves: false, size: 1.08, bulk: 1.32, alt: { pants: 0x2a9df4, belt: 0xffffff } },
];

/* ============ Poses y keyframes ============ */
// hy altura de cadera · tl inclinación del torso · tt giro del pecho · pt giro de la pelvis · bz peso adelante/atrás
// hd cabeza · sA/eA hombro/codo del brazo A (el de adelante) · sB/eB brazo B
// fxA/fyA, fxB/fyB = dónde queda el TOBILLO (adelante, altura): la IK dobla las rodillas sola · lie = tendido en el piso
const KEYS = ['hy', 'tl', 'tt', 'pt', 'bz', 'hd', 'sA', 'eA', 'sB', 'eB', 'fxA', 'fyA', 'fxB', 'fyB', 'lie'];
const pz = (b, o = {}) => ({ ...b, ...o });
const copy = (T, P) => { for (const k of KEYS) T[k] = P[k]; };
const mixInto = (T, B, e) => { for (const k of KEYS) T[k] += (B[k] - T[k]) * e; };

const IDLE   = { hy: .80, tl: .12, tt: .15, pt: -.1, bz: 0, hd: 0, sA: 1.0, eA: 1.7, sB: .85, eB: 1.95, fxA: .24, fyA: .07, fxB: -.28, fyB: .07, lie: 0 };
const CROUCH = pz(IDLE, { hy: .5, tl: .45, bz: .04, hd: -.25, sA: 1.0, eA: 1.8, sB: .85, eB: 2.0, fxA: .3, fxB: -.25 });
const BLOCK  = pz(IDLE, { hy: .76, tl: .2, tt: 0, pt: 0, bz: -.02, hd: .2, sA: 1.35, eA: 2.2, sB: 1.25, eB: 2.3, fxA: .2 });
const CBLOCK = pz(CROUCH, { tl: .5, tt: 0, pt: 0, hd: .2, sA: 1.35, eA: 2.2, sB: 1.25, eB: 2.3 });
const AIRP   = pz(IDLE, { hy: .8, tl: -.15, tt: 0, hd: .1, sA: .8, eA: 1.2, sB: 1.2, eB: 1.8, fxA: .8, fyA: .6, fxB: -.1, fyB: .3 });
const HIT    = pz(IDLE, { hy: .74, tl: -.55, tt: -.3, pt: .1, bz: -.08, hd: -.5, sA: .2, eA: .5, sB: .1, eB: .6, fxA: .15, fxB: -.45 });
const LYING  = pz(IDLE, { hy: .22, tl: 0, tt: 0, pt: 0, hd: -.1, sA: -.4, eA: .2, sB: -.5, eB: .3, fxA: .05, fxB: -.05, lie: 1 });

// Cada movimiento: [segundos, pose, curva]. Anticipación -> golpe (con "rebote") -> sostener -> recuperar.
const kf = (l) => l.map(([t, p, e = 'io']) => ({ t, p, e: EASE[e] }));
const swapP = (p) => ({ ...p, sA: p.sB, sB: p.sA, eA: p.eB, eB: p.eA, fxA: p.fxB, fxB: p.fxA, fyA: p.fyB, fyB: p.fyA, tt: -p.tt, pt: -p.pt });
const alt = (l) => l.map(([t, p, e]) => [t, p === IDLE ? IDLE : swapP(p), e]); // versión con el otro brazo/pierna

const PUNCH = [
  [0, IDLE],
  [.03, pz(IDLE, { sA: .55, eA: 2.4, tt: -.2, pt: -.25, bz: -.04, tl: .05 }), 'out'],
  [.07, pz(IDLE, { sA: 1.58, eA: .04, tt: .7, pt: .45, bz: .14, tl: .22, hy: .76, fxA: .45, fxB: -.32, sB: .4, eB: 2.4, hd: .05 }), 'back'],
  [.17, pz(IDLE, { sA: 1.6, eA: .08, tt: .72, pt: .47, bz: .15, tl: .24, hy: .76, fxA: .45, fxB: -.32, sB: .4, eB: 2.4, hd: .05 }), 'lin'],
  [.32, IDLE],
];
const KICK_P = pz(IDLE, { hy: .84, tl: -.35, pt: .3, fxA: .86, fyA: .98, fxB: -.04, sA: .4, eA: 1.2, sB: 1.45, eB: 1.7, hd: .12, bz: -.05 });
const KICK = [
  [0, IDLE],
  [.06, pz(IDLE, { hy: .8, tl: -.1, pt: .1, fxA: .25, fyA: .55, fxB: -.05, sA: .5, eA: 1.3, sB: 1.4, eB: 1.8, bz: -.03 }), 'out'],
  [.13, KICK_P, 'back'], [.25, KICK_P, 'lin'], [.5, IDLE],
];
const LOW_P = pz(CROUCH, { sA: 1.25, eA: .05, tt: .7, pt: .4, tl: .55, bz: .1, fxA: .4 });
const LOW = [[0, CROUCH], [.03, pz(CROUCH, { sA: .6, eA: 2.2, tt: -.25, pt: -.2 }), 'out'], [.08, LOW_P, 'back'], [.16, LOW_P, 'lin'], [.3, CROUCH]];
const SWEEP_P = pz(CROUCH, { hy: .36, tl: -.45, tt: .2, pt: .6, fxA: .84, fyA: .07, fxB: -.18, sA: .1, eA: .4, sB: -.5, eB: .3, hd: .1 });
const SWEEP = [
  [0, CROUCH],
  [.08, pz(CROUCH, { hy: .42, tl: -.1, tt: -.3, pt: -.4, fxA: .1, fxB: -.1, sA: .6, eA: 1.5, sB: 1.3, eB: 1.6 }), 'out'],
  [.16, SWEEP_P, 'back'], [.3, SWEEP_P, 'lin'], [.62, CROUCH],
];
const UP_P = pz(IDLE, { hy: .92, tl: -.05, tt: .55, pt: .4, sA: 2.75, eA: .3, sB: .5, eB: 2.3, fxA: .3, fyA: .18, fxB: -.2, fyB: .1, hd: -.1, bz: .08 });
const UPPER = [
  [0, IDLE],
  [.05, pz(IDLE, { hy: .46, tl: .5, tt: -.3, pt: -.3, sA: .8, eA: 2.2, sB: .6, eB: 2.3, fxA: .3, fxB: -.3, hd: -.15 }), 'out'],
  [.10, UP_P, 'back'], [.20, UP_P, 'lin'], [.45, IDLE],
];
const SPIN_P = pz(IDLE, { hy: .84, tl: -.3, tt: -.7, pt: -.8, fxA: .85, fyA: 1.05, fxB: -.05, sA: .8, eA: 1.0, sB: 1.4, eB: 1.6, hd: .1 });
const SPIN = [
  [0, IDLE],
  [.09, pz(IDLE, { hy: .78, tl: .05, tt: -.5, pt: -.5, fxA: .2, fyA: .5, fxB: -.1, sA: 1.2, eA: 1.2, sB: 1.2, eB: 1.4 }), 'out'],
  [.18, SPIN_P, 'back'], [.32, SPIN_P, 'lin'], [.6, IDLE],
];
const FIRE_P = pz(IDLE, { hy: .72, tl: .25, tt: .35, pt: .2, sA: 1.55, eA: .15, sB: 1.55, eB: .2, fxA: .45, fxB: -.4, bz: .1 });
const FIRE = [
  [0, IDLE],
  [.12, pz(IDLE, { hy: .7, tl: .15, tt: -.2, sA: 1.2, eA: 1.2, sB: 1.1, eB: 1.4, fxA: .3, fxB: -.35 }), 'out'],
  [.22, FIRE_P, 'back'], [.32, FIRE_P, 'lin'], [.65, IDLE],
];
const SUPER = [ // carga larga con las manos atrás y descarga
  [0, IDLE],
  [.32, pz(IDLE, { hy: .64, tl: .02, tt: -.5, pt: -.3, sA: .7, eA: 2.0, sB: .6, eB: 2.1, fxA: .38, fxB: -.48, bz: -.06, hd: .1 }), 'out'],
  [.42, pz(FIRE_P, { tt: .55, hy: .68, bz: .14 }), 'back'], [.72, FIRE_P, 'lin'], [.95, IDLE],
];
const HIT_P = pz(HIT, { tl: -.65, hd: -.6, bz: -.12 });
const BOUNCE = pz(LYING, { hy: .34, lie: .93 });
const FLY = pz(HIT, { hy: 1.0, tl: -.8, hd: -.3, lie: .4, fxA: .3, fyA: .5, fxB: -.1, fyB: .4 });
const MOVES = {
  punch: kf(PUNCH), punchB: kf(alt(PUNCH)), kick: kf(KICK), kickB: kf(alt(KICK)),
  low: kf(LOW), sweep: kf(SWEEP), upper: kf(UPPER), spin: kf(SPIN), fire: kf(FIRE), super: kf(SUPER),
  hit: kf([[0, HIT], [.07, HIT_P, 'out'], [.3, IDLE]]),
  down: kf([[0, HIT], [.12, FLY, 'out'], [.3, LYING], [.42, BOUNCE, 'out'], [.52, LYING], [.7, LYING], [.88, pz(CROUCH, { lie: .15, tl: .6 })], [1.0, IDLE]]),
  ko: kf([[0, HIT], [.12, FLY, 'out'], [.3, LYING], [.42, BOUNCE, 'out'], [.55, LYING]]),
};

function sample(T, kfs, p) {
  const n = kfs.length;
  if (p <= kfs[0].t) return copy(T, kfs[0].p);
  if (p >= kfs[n - 1].t) return copy(T, kfs[n - 1].p);
  let i = 0; while (i < n - 2 && p > kfs[i + 1].t) i++;
  const a = kfs[i], b = kfs[i + 1], u = b.e((p - a.t) / (b.t - a.t));
  for (const k of KEYS) T[k] = a.p[k] + (b.p[k] - a.p[k]) * u;
}

// IK de 2 huesos: cadera a altura hy, tobillo en (fx, fy) -> [ángulo del muslo, flexión de rodilla]
const L1 = .45, L2 = .45, IK = [0, 0];
function ik(fx, fy, hy) {
  const dy = hy - fy, D = Math.min(Math.hypot(fx, dy), L1 + L2 - 1e-3);
  const a = Math.atan2(fx, dy);
  const b = Math.acos(clamp((L1 * L1 + D * D - L2 * L2) / (2 * L1 * D), -1, 1));
  const kn = Math.PI - Math.acos(clamp((L1 * L1 + L2 * L2 - D * D) / (2 * L1 * L2), -1, 1));
  IK[0] = a + b; IK[1] = kn;
}

/* ============ Sprite 2D ============ */
// Píxeles por unidad de mundo y tamaño del canvas del sprite (el origen es el punto entre los pies)
const PPU = 44, SW = 150, SH = 124, OX = 75, OY = 116;
// Rota (x adelante, y arriba) en sentido horario: inclinar "arriba" hacia adelante es un ángulo positivo
const rot = (x, y, r) => { const c = Math.cos(r), s = Math.sin(r); return [x * c + y * s, -x * s + y * c]; };
const add = (p, d, k) => [p[0] + d[0] * k, p[1] + d[1] * k];
const lerp = (a, b, t) => [a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t];
const WHITE = rgba(255, 255, 255), INK = rgba(26, 14, 34), MOUTH = rgba(120, 20, 40);
const SHADOW_GEO = new CircleGeometry(.55, 14).rotateX(-Math.PI / 2);
const SHADOW_MAT = new MeshBasicMaterial({ color: 0x000000, transparent: true, opacity: .4, depthWrite: false, polygonOffset: true, polygonOffsetFactor: -2 });

/* Cinta/mechón/bufanda: cadena de segmentos con resorte (movimiento secundario). Ángulo 0 = colgando, + = hacia atrás */
class Tail {
  constructor(n, len, w, rest, color) { this.n = n; this.len = len; this.w = w; this.rest = rest; this.color = color; this.a = new Array(n).fill(rest); }
  update(dt, sf, vy, t, idx) { // sf: velocidad hacia adelante · vy: velocidad vertical
    const tgt = clamp(sf * .18 - vy * .05, -.5, 1.1);
    for (let i = 0; i < this.n; i++) {
      const want = this.rest + tgt * (1 + i * .3) + Math.sin(t * 6 + i * 1.2 + idx) * (.08 + Math.abs(sf) * .03);
      this.a[i] += (want - this.a[i]) * (1 - Math.exp(-(16 - i * 3.5) * dt));
    }
  }
  draw(cap, from, pal, layer) {
    let p = from;
    for (let i = 0; i < this.n; i++) {
      const q = [p[0] - Math.sin(this.a[i]) * this.len, p[1] - Math.cos(this.a[i]) * this.len];
      cap(p, q, this.w * (1 - i * .15) / 2, this.w * (1 - (i + 1) * .15) / 2, pal, layer);
      p = q;
    }
  }
}

/* ============ Luchador ============ */
export class Fighter {
  constructor(slot, char, scene) {
    this.slot = slot; this.key = '';
    this.root = new Group(); scene.add(this.root);
    this.R = new Raster(SW, SH);
    this.tex = pixelTexture(this.R.canvas);
    this.sprite = new Mesh(new PlaneGeometry(SW / PPU, SH / PPU), new MeshBasicMaterial({ map: this.tex, alphaTest: .5, side: DoubleSide }));
    this.sprite.position.y = (OY - SH / 2) / PPU; // el origen del canvas (pies) coincide con la posición del luchador
    this.root.add(this.sprite);
    this.shadow = new Mesh(SHADOW_GEO, SHADOW_MAT); this.shadow.position.y = .01; scene.add(this.shadow);
    this.T = {}; this.P = { ...IDLE };
    this.portrait = document.createElement('canvas'); this.portrait.width = this.portrait.height = 40;
    this.reset();
    this.setChar(char, false);
  }

  setChar(i, useAlt) {
    const key = i + (useAlt ? 'a' : '');
    if (key === this.key) return;
    this.key = key; this.char = i;
    this.def = { ...DEFS[i], ...(useAlt ? DEFS[i].alt : {}) };
    this.build(this.def);
  }

  // Paletas (normal y oscurecida para los miembros de atrás) y cintas según el personaje
  build(d) {
    const make = (dim) => {
      const p = {};
      for (const k of ['skin', 'top', 'pants', 'belt', 'wrap', 'hair', 'shoes', 'band', 'scarf']) if (d[k] != null) p[k] = palette(d[k], dim);
      p.torso = p.top ?? p.skin; p.sleeve = d.sleeves ? p.torso : p.skin; p.fist = d.gloves ? p.wrap : p.skin;
      p.foot = p.shoes ?? p.skin; p.shin = d.shorts ? p.skin : p.pants;
      return p;
    };
    this.pal = make(1); this.palB = make(.74);
    this.line = this.pal.torso.map(() => this.pal.torso[3]);
    this.tails = {
      band: d.band != null ? new Tail(3, .1, .07, 1.25, 'band') : null,
      pony: d.hairStyle === 'pony' ? new Tail(3, .13, .13, .55, 'hair') : null,
      scarf: d.scarf != null ? new Tail(4, .13, .1, 1.05, 'scarf') : null,
      belt: new Tail(2, .1, .055, .12, 'belt'),
    };
    this.paintPortrait();
  }

  /* ---------- Simulación ---------- */
  reset() {
    this.x = this.slot ? 2 : -2; this.y = 0; this.vy = 0; this.vx = 0; this.kx = 0;
    this.tx = this.x; this.ty = 0; this.seen = false;
    this.facing = this.slot ? -1 : 1;
    this.hp = MAX_HP; this.st = S.idle; this.stT = 0; this.t = 0;
    this.stun = 0; this.atk = null; this.atkT = 0; this.atkHit = false;
    this.fireCd = 0; this.alt = false; this.lastAtk = -9;
    this.lx = this.x; this.ly = 0; this.vxV = 0; this.vyV = 0; this.wasAir = false;
    this.landT = 0; this.ph = 0; this.fl = 0; this.dustT = 0; this.flashT = 0; this.flashK = 1; this.prevSt = S.idle;
    copy(this.P, IDLE);
  }

  set(n) { const i = S[n]; if (this.st !== i) { this.st = i; this.stT = 0; } }
  attack(n) {
    if (n === 'punch' || n === 'kick') this.alt = this.t - this.lastAtk < .55 ? !this.alt : false; // combos: alterna lado
    this.lastAtk = this.t; this.atk = n; this.atkT = 0; this.atkHit = false; this.set(n);
  }
  hurtH() { return CROUCHY.has(this.st) ? 1.0 : this.st === S.down ? .35 : 1.8; }

  // Velocidades "visuales" (para saltos con voltereta, cintas y polvo)
  track(dt) {
    this.vxV += ((this.x - this.lx) / dt - this.vxV) * .3;
    this.vyV += ((this.y - this.ly) / dt - this.vyV) * .35;
    this.lx = this.x; this.ly = this.y;
    const air = this.y > .05;
    if (this.wasAir && !air) { this.landT = .14; Fighter.fx?.dust(this.x, 0); }
    this.wasAir = air;
    if ((this.st === S.walk || this.st === S.back) && this.t - this.dustT > .22) { this.dustT = this.t; Fighter.fx?.dust(this.x - this.facing * .3, -this.facing * .5); }
  }

  // Solo el LOCAL: input + física. Devuelve un "hit" (o {fire}) cuando corresponde
  step(dt, inp, opp) {
    this.t += dt; this.stT += dt; this.fireCd -= dt;
    if (this.st === S.ko) { this.track(dt); return null; }
    const wasAir = this.y > 0;

    if (this.stun > 0) { this.stun -= dt; if (this.stun <= 0) this.set(wasAir ? 'jump' : 'idle'); }
    else if (this.atk) {
      this.atkT += dt;
      if (this.atkT >= ATK[this.atk].total) { this.atk = null; this.set(wasAir ? 'jump' : 'idle'); }
    }

    let move = 0;
    if (!this.atk && this.stun <= 0) {
      if (!wasAir) {
        this.facing = Math.sign(opp.x - this.x) || this.facing; // siempre mirando al rival
        if (inp.block) this.set(inp.crouch ? 'cblock' : 'block');
        else if (inp.crouch) {
          if (inp.punchQ) this.attack('low');
          else if (inp.kickQ) this.attack('sweep');
          else this.set('crouch');
        } else if (inp.superQ && this.meter >= MAX_METER) { this.meter = 0; this.attack('super'); }
        else if (inp.fireQ && this.fireCd <= 0) { this.fireCd = 1.2; this.attack('fire'); }
        else if (inp.upperQ) this.attack('upper');
        else if (inp.spinQ) this.attack('spin');
        else if (inp.punchQ) this.attack('punch');
        else if (inp.kickQ) this.attack('kick');
        else {
          move = (inp.right ? 1 : 0) - (inp.left ? 1 : 0);
          if (inp.jump) { this.vy = JUMP; this.vx = move * SPEED; } // el salto "compromete" la dirección
          this.set(inp.jump ? 'jump' : move ? (move * this.facing > 0 ? 'walk' : 'back') : 'idle');
        }
      } else if (inp.punchQ || inp.kickQ) this.attack('air');
      else this.set('jump');
    }
    inp.punchQ = inp.kickQ = inp.upperQ = inp.spinQ = inp.fireQ = inp.superQ = false;

    const vxe = this.y > 0 ? this.vx : move * SPEED;
    this.x += (vxe + this.kx) * dt;
    this.kx -= this.kx * Math.min(1, dt * 8);
    this.vy -= GRAV * dt; this.y += this.vy * dt;
    if (this.y <= 0) {
      this.y = 0; this.vy = 0; this.vx = 0;
      if (wasAir && this.atk === 'air') { this.atk = null; this.set('idle'); }
    }
    this.x = clamp(this.x, -HALF, HALF);
    const dx = this.x - opp.x; // no atravesar al rival (salvo saltando por encima)
    if (Math.abs(dx) < .6 && Math.abs(this.y - opp.y) < 1) this.x += (Math.sign(dx) || -this.facing) * (.6 - Math.abs(dx)) * .5;
    this.track(dt);

    if (this.atk && !this.atkHit) {
      const a = ATK[this.atk];
      if (a.proj) {
        if (this.atkT >= a.start) { this.atkHit = true; return { fire: { x: this.x + this.facing * .6, y: this.y + 1.15, dir: this.facing, big: !!a.big } }; }
      } else if (this.atkT >= a.start && this.atkT <= a.start + a.active) {
        const rel = (opp.x - this.x) * this.facing, ah = this.y + a.h;
        if (rel > -.2 && rel < a.reach + .3 && ah >= opp.y - .1 && ah <= opp.y + opp.hurtH() && opp.st !== S.ko && opp.st !== S.down) {
          this.atkHit = true;
          return { d: a.dmg, dir: this.facing, low: !!a.low, kd: !!a.kd };
        }
      }
    }
    return null;
  }

  // Cuando RECIBO un golpe (daño y bloqueo los decide la víctima). Devuelve 'block' | 'hit' | 'down' | 'ko' | null
  hurt({ d, dir, low, kd }) {
    if (this.st === S.ko || this.st === S.down) return null;
    const blocked = this.st === S.cblock || (this.st === S.block && !low);
    this.hp = Math.max(0, this.hp - (blocked ? Math.ceil(d * .2) : d));
    this.kx = dir * (blocked ? 2.5 : kd || d >= 10 ? 7 : 5.5);
    let r = 'block';
    if (!blocked) {
      this.atk = null;
      if (kd) { this.stun = 1; this.set('down'); r = 'down'; }
      else { this.stun = .3; if (this.st === S.hit) this.stT = 0; this.set('hit'); r = 'hit'; }
    }
    if (this.hp <= 0) { this.set('ko'); r = 'ko'; }
    return r;
  }

  // REMOTO: suavizado hacia la última posición recibida
  follow(dt) {
    this.t += dt; this.stT += dt;
    const k = Math.min(1, dt * 18);
    this.x += (this.tx - this.x) * k; this.y += (this.ty - this.y) * k;
    this.track(dt);
  }

  /* ---------- Animación ---------- */
  ext(a) { // 0..1 para ataques aéreos
    const p = this.stT, e = a.start + a.active;
    return p < a.start ? EASE.io(p / a.start) : p < e ? 1 : EASE.io(clamp(1 - (p - e) / (a.total - e), 0, 1));
  }

  jumpPose(T) {
    copy(T, IDLE);
    const r = clamp(this.vyV / JUMP, -1, 1), tuck = 1 - Math.abs(r), fall = Math.max(0, -r), flip = Math.abs(this.fl) > .3;
    T.hy = .72 + .08 * r; T.tl = .1 + (flip ? .25 : 0);
    T.sA = flip ? 1.1 : 1.3; T.eA = flip ? 2.3 : 1.5; T.sB = flip ? 1.0 : 1.2; T.eB = flip ? 2.4 : 1.7;
    T.fxA = flip ? .22 : .3;  T.fyA = flip ? .6 : .4 + .2 * tuck - .2 * fall;
    T.fxB = flip ? .1 : -.1;  T.fyB = flip ? .55 : .3 + .15 * tuck - .15 * fall;
  }

  target() {
    const T = this.T, s = ST[this.st], t = this.t, p = this.stT;
    switch (s) {
      case 'idle': { // guardia viva: respiración, rebote y cambio de peso
        copy(T, IDLE); const b = Math.sin(t * 4.4), w = Math.sin(t * 1.7);
        T.hy += b * .014; T.bz = w * .012; T.tl += b * .015; T.hd = Math.sin(t * 2.2) * .03;
        T.sA += Math.sin(t * 4.4 + 1) * .07; T.sB += Math.sin(t * 4.4 + 2) * .07; T.fxA += w * .02; T.fxB -= w * .02; break;
      }
      case 'walk': case 'back': {
        copy(T, IDLE);
        const d = s === 'back' ? -1 : 1, ph = this.ph, sn = Math.sin(ph), cs = Math.cos(ph), L = s === 'back' ? .22 : .3;
        T.fxA = .12 + L * sn;  T.fyA = .07 + Math.max(0, cs * d) * .16;
        T.fxB = -.14 - L * sn; T.fyB = .07 + Math.max(0, -cs * d) * .16;
        T.hy += .02 * Math.cos(2 * ph); T.tt += .1 * sn; T.pt -= .12 * sn;
        T.sA += .09 * Math.sin(2 * ph); T.sB -= .09 * Math.sin(2 * ph); if (d < 0) T.tl -= .05; break;
      }
      case 'crouch': copy(T, CROUCH); T.hy += Math.sin(t * 3) * .01; break;
      case 'block': copy(T, BLOCK); T.tl += Math.sin(t * 5) * .01; break;
      case 'cblock': copy(T, CBLOCK); break;
      case 'jump': this.jumpPose(T); break;
      case 'air': this.jumpPose(T); mixInto(T, AIRP, this.ext(ATK.air)); break;
      case 'punch': case 'kick': sample(T, MOVES[s + (this.alt ? 'B' : '')], p); break;
      default: sample(T, MOVES[s], p);
    }
    if (this.landT > 0) T.hy -= .13 * (this.landT / .14); // amortigua el aterrizaje
  }

  animate(dt) {
    if (this.landT > 0) this.landT -= dt;
    if (this.flashT > 0) this.flashT -= dt;
    if (this.st !== this.prevSt) { // flash blanco al recibir, dorado al lanzar el SUPER
      if (this.st === S.hit || this.st === S.down || this.st === S.ko) { this.flashT = .1; this.flashK = 1; }
      if (this.st === S.super) { this.flashT = .16; this.flashK = 2; }
      this.prevSt = this.st;
    }
    if (this.st === S.walk || this.st === S.back) this.ph += dt * 10 * (this.st === S.back ? -1 : 1);

    // Voltereta en saltos con impulso horizontal
    if (this.st === S.jump && Math.abs(this.vxV) > 1.8 && this.y > .1) {
      const prog = clamp((1 - this.vyV / JUMP) / 2 * 1.12, 0, 1);
      this.fl = (this.vxV * this.facing > 0 ? 1 : -1) * TAU * EASE.io(prog);
    } else {
      const tg = Math.round(this.fl / TAU) * TAU;
      this.fl += (tg - this.fl) * Math.min(1, dt * 18);
      if (Math.abs(this.fl - tg) < .01) this.fl = 0;
    }

    this.target();
    const fast = !!MOVES[ST[this.st]] || this.st === S.air;
    const k = 1 - Math.exp(-(fast ? 50 : 16) * dt), P = this.P, T = this.T;
    for (const key of KEYS) P[key] += (T[key] - P[key]) * k;

    const sf = this.vxV * this.facing, tl = this.tails;
    [tl.band, tl.pony, tl.scarf, tl.belt].forEach((x, i) => x?.update(dt, sf, this.vyV, this.t, i));
  }

  /* ---------- Dibujo píxel por píxel ---------- */
  // Arma el esqueleto 2D de la pose P (unidades del cuerpo; x = adelante, y = arriba) y lo pinta en R.
  // ppu/ox/oy permiten reusar lo mismo para el retrato del HUD (más zoom, otro encuadre).
  paint(R, P, ppu = PPU, ox = OX, oy = OY) {
    const d = this.def, pl = this.pal, pb = this.palB, sz = d.size * ppu, b = d.bulk, rb = 1 + (b - 1) * .6;
    const X = (p) => ox + p[0] * sz, Y = (p) => oy - p[1] * sz;
    const cap = (A, B, ra, rbb, pal, layer, keep) => R.capsule(X(A), Y(A), X(B), Y(B), ra * sz, rbb * sz, pal, layer, keep);
    const ell = (C, rx, ry, ang, pal, layer, keep) => R.ellipse(X(C), Y(C), rx * sz, ry * sz, ang, pal, layer, keep);

    // Cadera y torso
    const Rh = -P.lie * 1.5 + this.fl, Rt = Rh + P.tl, hip = [P.bz, P.hy];
    const up = rot(0, 1, Rt), fw = rot(1, 0, Rt), hf = rot(1, 0, Rh);
    const T = (u, f) => [hip[0] + up[0] * u + fw[0] * f, hip[1] + up[1] * u + fw[1] * f];

    // Brazos: hombro -> codo -> puño (A = el de adelante)
    const arm = (sA, eA, front) => {
      const S0 = T(.6, P.tt * .09 * (front ? 1 : -1) + (front ? -.01 : .05));
      const E = add(S0, rot(0, -1, Rt - sA), .3), fd = rot(0, -1, Rt - sA - eA);
      return { S: S0, E, W: add(E, fd, .24), F: add(E, fd, .32), ang: Rt - sA - eA };
    };
    // Piernas: cadera -> rodilla -> tobillo con IK (se aplana al estar tendido)
    const leg = (fx, fy, front) => {
      ik(fx, fy, P.hy);
      const th = IK[0] + (.05 - IK[0]) * P.lie, kn = IK[1] + (.15 - IK[1]) * P.lie;
      const J = add(hip, hf, P.pt * .06 * (front ? 1 : -1));
      const K = add(J, rot(0, -1, Rh - th), L1), An = add(K, rot(0, -1, Rh - th + kn), L2);
      return { J, K, An, foot: add(add(An, hf, .06), rot(0, -1, Rh), .03) };
    };
    const A = arm(P.sA, P.eA, true), B = arm(P.sB, P.eB, false);
    const LA = leg(P.fxA, P.fyA, true), LB = leg(P.fxB, P.fyB, false);
    this.sk = { A: A.F, B: B.F, LA: LA.foot, LB: LB.foot };

    // Cabeza (sigue mirando al frente aunque el torso se incline)
    const Rhd = Rt + P.hd - P.tl * .7, hu = rot(0, 1, Rhd), hfw = rot(1, 0, Rhd);
    const Hc = add(T(.72, .01), hu, .17);
    const H = (u, f) => [Hc[0] + hu[0] * u + hfw[0] * f, Hc[1] + hu[1] * u + hfw[1] * f];
    const hcx = X(Hc), hcy = Y(Hc); this.headPx = [hcx, hcy];
    const local = (fn) => (px, py) => { // recorte en coordenadas de la cabeza (f adelante, u arriba)
      const x = (px - hcx) / sz, y = (hcy - py) / sz;
      return fn(x * hfw[0] + y * hfw[1], x * hu[0] + y * hu[1]);
    };

    // 1) Cintas y pelo largo (atrás de todo)
    const tl = this.tails;
    tl.scarf?.draw(cap, T(.68, -.08), pl.scarf, 1);
    tl.pony?.draw(cap, H(.12, -.11), pl.hair, 1);
    tl.band?.draw(cap, H(.06, -.14), pl.band, 1);

    // 2) Brazo y pierna de atrás (más oscuros = profundidad)
    const drawArm = (a, p, layer) => {
      ell(a.S, .1 * rb, .1 * rb, 0, p.torso, layer);
      cap(a.S, a.E, .085 * rb, .07 * rb, p.sleeve, layer);
      if (d.vneck && !d.sleeves) cap(a.S, lerp(a.S, a.E, .45), .1 * rb, .092 * rb, p.torso, layer); // manga corta del gi
      cap(a.E, a.W, .07 * rb, .058 * rb, p.sleeve, layer);
      cap(lerp(a.E, a.W, .65), a.W, .066 * rb, .064 * rb, p.wrap, layer);
      ell(a.F, .1 * rb, .088 * rb, a.ang, p.fist, layer);
    };
    const drawLeg = (g, p, layer) => {
      cap(g.J, g.K, .135 * rb, .1 * rb, p.pants, layer);
      cap(g.K, g.An, .095 * rb, (d.flare ? .115 : d.shorts ? .06 : .072) * rb, p.shin, layer);
      if (d.shorts) cap(g.J, lerp(g.J, g.K, .55), .14 * rb, .11 * rb, p.pants, layer);
      ell(g.foot, .12, .055, Rh, p.foot, layer);
    };
    drawArm(B, pb, 2);
    drawLeg(LB, pb, 3);

    // 3) Torso: pelvis, abdomen, pecho, cuello y cinturón
    ell(hip, .17 * b, .13, Rh, pl.pants, 4);
    cap(T(.02, 0), T(.32, .01), .145 * b, .15 * b, pl.torso, 4);
    cap(T(.32, .02), T(.56, .03), .17 * b, .2 * b, pl.torso, 4);
    if (d.vneck) { ell(T(.58, .11), .05, .1, Rt, pl.skin, 4); cap(T(.66, .05), T(.36, .16), .012, .012, this.line, 4); }
    if (d.top == null) { // torso desnudo: pectorales y abdominales marcados
      const ln = pl.skin.map(() => pl.skin[2]);
      cap(T(.44, .02), T(.46, .2), .012, .012, ln, 4);
      cap(T(.28, .12), T(.3, .2), .01, .01, ln, 4);
    }
    cap(T(.66, 0), T(.78, .01), .062, .056, pl.skin, 4);
    cap(T(.12, -.16 * b), T(.12, .17 * b), .042, .042, pl.belt, 4);

    // 4) Pierna de adelante, nudo y punta del cinturón
    drawLeg(LA, pl, 5);
    ell(T(.12, .17 * b), .045, .04, 0, pl.belt, 4);
    tl.belt.draw(cap, T(.1, .17 * b), pl.belt, 4);

    // 5) Cabeza: cara, mentón, pelo/capucha, vincha, ojos y boca
    ell(Hc, .15, .165, Rhd, pl.skin, 6);
    ell(H(-.08, .05), .09, .072, Rhd, d.mask ? pl.torso : pl.skin, 6);
    if (d.hairStyle === 'spike' || d.hairStyle === 'pony') {
      const bangs = d.hairStyle === 'pony' ? .045 : .03;
      ell(H(.012, -.008), .162, .172, Rhd, pl.hair, 6, local((f, u) => u > bangs + Math.max(0, f) * .35 || f < -.05));
      if (d.hairStyle === 'spike') {
        for (const [u, f, df, du, len] of [[.13, .06, .25, 1, .16], [.15, -.02, -.3, 1, .18], [.12, -.1, -.85, .7, .17], [.04, -.14, -1, .25, .14], [.16, .03, -.05, 1, .15]]) {
          const o = H(u, f), m = Math.hypot(df, du), dir = [(hfw[0] * df + hu[0] * du) / m, (hfw[1] * df + hu[1] * du) / m];
          cap(o, add(o, dir, len), .05, .008, pl.hair, 6);
        }
      } else cap(H(.14, -.08), H(.16, -.13), .045, .04, pl.hair, 6); // moño de la cola
    } else if (d.hairStyle === 'hood') {
      ell(H(.005, -.01), .172, .184, Rhd, pl.torso, 6, local((f, u) => !(f > -.02 && u > -.015 && u < .065)));
    } else if (d.hairStyle === 'bald') {
      R.dot(X(H(.11, .02)), Y(H(.11, .02)), pl.skin[0], 6, 2, 1); // brillo de la pelada
    }
    if (d.beard) ell(H(-.085, .06), .1, .075, Rhd, pl.hair, 6, local((f) => f > -.04));
    if (d.band) cap(H(.065, -.155), H(.075, .148), .03, .03, pl.band, 6);

    const s = ST[this.st], p = this.stT;
    const hurt = s === 'hit' || s === 'down' || s === 'ko', blk = s === 'block' || s === 'cblock';
    const shout = !!(ATK[s] && p < .35) || (s === 'super' && p < .9);
    const e = H(.025, .088), ex = Math.floor(X(e)), ey = Math.floor(Y(e));
    if (hurt) { R.dot(ex - 1, ey, INK, 6, 3, 1); R.dot(ex - 1, ey - 1, INK, 6, 1, 1); }
    else {
      R.dot(ex - 1, ey, WHITE, 6, 2, blk ? 1 : 2); R.dot(ex, ey, INK, 6, 1, blk ? 1 : 2);
      R.dot(ex - 1, ey - 2, d.mask ? pl.band?.[2] ?? INK : INK, 6, 3, 1); // ceja
    }
    if (!d.mask) {
      const m = H(-.075, .105);
      R.dot(Math.floor(X(m)) - 1, Math.floor(Y(m)), hurt || shout ? MOUTH : INK, 6, 2, hurt || shout ? 2 : 1);
    }

    // 6) Brazo de adelante (encima de todo, como en los sprites de arcade)
    drawArm(A, pl, 7);
  }

  paintPortrait() {
    const R = new Raster(40, 40), fl = this.fl; this.fl = 0;
    this.paint(R, IDLE, 72, 0, 0); // primera pasada solo para saber dónde cae la cabeza
    const [hx, hy] = this.headPx;
    R.clear(); this.paint(R, IDLE, 72, 19 - hx, 19 - hy);
    this.fl = fl; R.finish();
    const g = this.portrait.getContext('2d'); g.clearRect(0, 0, 40, 40); g.drawImage(R.canvas, 0, 0);
  }

  // Estela de color en el puño/pie durante la fase activa del golpe, y aura al cargar el SUPER
  trail() {
    const s = ST[this.st], a = ATK[s], sz = this.def.size, f = this.facing;
    const at = (p) => [this.x + f * p[0] * sz, this.y + p[1] * sz];
    if (s === 'super' && this.stT < a.start) {
      for (const k of ['A', 'B']) {
        const [x, y] = at(this.sk[k]), ang = Math.random() * TAU;
        Fighter.fx?.emit(x + Math.cos(ang) * .5, y + Math.sin(ang) * .5, .2, -Math.cos(ang) * 2.5, -Math.sin(ang) * 2.5, 0, .2, .08, Math.random() < .5 ? 0xffd166 : 0xff3d9a, 0);
      }
      return;
    }
    if (!a || a.proj || this.stT < a.start * .6 || this.stT > a.start + a.active + .08) return;
    const leg = s === 'kick' || s === 'sweep' || s === 'spin' || s === 'air';
    const [x, y] = at(this.sk[leg ? (this.alt && s === 'kick' ? 'LB' : 'LA') : (this.alt && s === 'punch' ? 'B' : 'A')]);
    Fighter.fx?.emit(x, y, .2, 0, 0, 0, .16, .09, 0xffffff, 0);
  }

  draw(dt) {
    this.animate(dt);
    this.R.clear();
    this.paint(this.R, this.P);
    this.R.finish(this.flashT > 0 ? this.flashK : 0);
    this.tex.needsUpdate = true;

    // El que ataca se dibuja adelante. La patada giratoria da vuelta el sprite a mitad del giro.
    const spin = this.st === S.spin ? EASE.io(clamp(this.stT / ATK.spin.total, 0, 1)) * TAU : 0;
    this.root.position.set(this.x, this.y, ATK[ST[this.st]] ? .06 : this.slot ? -.02 : .02);
    this.root.scale.x = this.facing * (Math.cos(spin) < 0 ? -1 : 1);
    const s = Math.max(.3, 1 - this.y * .25) * this.def.size;
    this.shadow.position.x = this.x; this.shadow.scale.set(s, 1, s);
    this.trail();
  }
}
