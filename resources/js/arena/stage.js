// Escenario: azotea-dojo de noche en una ciudad neón, con cielo animado, skyline con paralaje,
// torii, público que festeja, faroles y pétalos. Todo generado por código (sin assets).
import {
  Group, Mesh, PlaneGeometry, BoxGeometry, CylinderGeometry, SphereGeometry,
  MeshBasicMaterial, MeshLambertMaterial, ShaderMaterial, InstancedMesh, Object3D, Color,
  CanvasTexture, NearestFilter, RepeatWrapping, SRGBColorSpace, DynamicDrawUsage,
  HemisphereLight, DirectionalLight, PointLight,
} from 'three';
import { toon } from './common.js';
import { Raster, palette, pixelTexture, rgba } from './pixel.js';

const rng = (seed) => { let s = seed; return () => (s = (s * 16807) % 2147483647) / 2147483647; };
const hex = (c) => '#' + c.toString(16).padStart(6, '0');

function canvasTex(w, h, draw, repeat) {
  const c = document.createElement('canvas'); c.width = w; c.height = h;
  draw(c.getContext('2d'), w, h);
  const t = new CanvasTexture(c);
  t.magFilter = t.minFilter = NearestFilter; t.generateMipmaps = false; t.colorSpace = SRGBColorSpace;
  if (repeat) { t.wrapS = t.wrapT = RepeatWrapping; t.repeat.set(repeat[0], repeat[1]); }
  return t;
}

/* ---------- Cielo (shader en "pixeles" de mundo): degradé escalonado, estrellas, luna, nubes ---------- */
const SKY_VS = `varying vec3 vW;
void main() { vec4 w = modelMatrix * vec4(position, 1.0); vW = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`;
const SKY_FS = `uniform float time; varying vec3 vW;
float h21(vec2 p) { p = fract(p * vec2(123.34, 456.21)); p += dot(p, p + 45.32); return fract(p.x * p.y); }
float vn(vec2 p) { vec2 i = floor(p), f = fract(p); f = f * f * (3.0 - 2.0 * f);
  return mix(mix(h21(i), h21(i + vec2(1, 0)), f.x), mix(h21(i + vec2(0, 1)), h21(i + vec2(1, 1)), f.x), f.y); }
void main() {
  vec2 p = vW.xy; vec2 cell = floor(p / 0.42);
  float t = clamp((p.y + 4.0) / 34.0, 0.0, 1.0); t = floor(t * 14.0) / 14.0;
  vec3 cH = vec3(0.98, 0.42, 0.46), cM = vec3(0.34, 0.11, 0.45), cT = vec3(0.04, 0.03, 0.17);
  vec3 c = mix(cH, cM, smoothstep(0.0, 0.5, t)); c = mix(c, cT, smoothstep(0.4, 1.0, t));
  float s = h21(cell);
  c += vec3(1.0) * step(0.985, s) * step(0.25, t) * (0.55 + 0.45 * sin(time * 3.0 + s * 60.0));
  vec2 mc = vec2(30.0, 15.0); float d = length(p - mc);
  c += vec3(0.9, 0.55, 0.8) * floor(smoothstep(15.0, 6.5, d) * 5.0) / 5.0 * 0.28;
  if (d < 6.5) { float cr = h21(floor((p - mc) / 0.9)); c = mix(vec3(1.0, 0.95, 0.75), vec3(0.86, 0.8, 0.62), step(0.84, cr)); }
  float n = vn(vec2(floor(p.x / 0.9) * 0.16 + time * 0.35, floor(p.y / 0.9) * 0.6));
  float cl = step(0.64, n) * smoothstep(-1.0, 5.0, p.y) * step(p.y, 18.0);
  c = mix(c, vec3(0.95, 0.5, 0.62), cl * 0.55);
  gl_FragColor = vec4(c, 1.0);
}`;

/* ---------- Skyline en capas ---------- */
function skylineTex(seed, body, edge, wins, lit, hMin, hMax) {
  return canvasTex(512, 160, (g, w, h) => {
    const r = rng(seed);
    g.fillStyle = body; g.fillRect(0, h * .72, w, h * .28);
    for (let x = 0; x < w;) {
      const bw = 14 + (r() * 30 | 0), bh = h * (hMin + r() * (hMax - hMin)), top = h - bh;
      g.fillStyle = body; g.fillRect(x, top, bw, bh + 2);
      g.fillStyle = edge; g.fillRect(x, top, bw, 2);
      if (r() < .3) g.fillRect(x + (bw / 2 | 0), top - 10, 2, 10);
      for (let wy = top + 6; wy < h - 6; wy += 6) for (let wx = x + 3; wx < x + bw - 3; wx += 5) {
        if (r() < lit) { g.fillStyle = wins[r() * wins.length | 0]; g.fillRect(wx, wy, 2, 3); }
      }
      x += bw + (r() * 4 | 0);
    }
  });
}

function signTex(col) {
  return canvasTex(16, 40, (g, w, h) => {
    const r = rng(col & 0xffff);
    g.fillStyle = '#12081f'; g.fillRect(0, 0, w, h);
    g.fillStyle = hex(col); g.fillRect(0, 0, w, 1); g.fillRect(0, h - 1, w, 1); g.fillRect(0, 0, 1, h); g.fillRect(w - 1, 0, 1, h);
    for (let y = 4; y < h - 4; y += 6) {
      g.fillRect(3 + (r() * 5 | 0), y, 4 + (r() * 6 | 0), 1);
      g.fillRect(3 + (r() * 9 | 0), y - 2, 1, 4);
    }
  });
}

/* ---------- Público: hoja de sprites generada con el mismo rasterizador que los luchadores ---------- */
const CROWD_VARIANTS = 8, CW = 20, CH = 34, CROWD_PPU = 22, CROWD_H = CH / CROWD_PPU;
function crowdAtlas(r) {
  const R = new Raster(CW * CROWD_VARIANTS * 2, CH), cols = CROWD_VARIANTS * 2;
  const skins = [0xf0b27a, 0xc68642, 0xe3b48c, 0x8d5524, 0xf6cfa8];
  const hairs = [0x15131a, 0x5a3a22, 0xd9a441, 0x7b2ff7, 0x2b2d42, 0xb83a3a];
  const neon = [0xff3d71, 0x29b6f6, 0xffd166, 0x7cff6b];
  for (let v = 0; v < CROWD_VARIANTS; v++) {
    const shirt = palette(new Color().setHSL(r(), .45, .32 + r() * .18).getHex()), skin = palette(skins[r() * skins.length | 0]);
    const hair = palette(hairs[r() * hairs.length | 0]), glow = neon[v % neon.length], long = r() < .4;
    for (let f = 0; f < 2; f++) {
      const ox = (v * 2 + f) * CW, cx = ox + CW / 2;
      // brazos (atrás del cuerpo cuando están abajo, arriba cuando festeja)
      if (f) { R.capsule(cx - 4, 18, cx - 7, 7, 2, 1.6, skin, 1); R.capsule(cx + 4, 18, cx + 7, 6, 2, 1.6, skin, 1); }
      else { R.capsule(cx - 5, 18, cx - 6, 27, 2, 1.6, skin, 1); R.capsule(cx + 5, 18, cx + 8, 12, 2, 1.6, skin, 1); }
      R.capsule(cx, 31, cx, 20, 6.2, 5.4, shirt, 2);               // torso
      R.ellipse(cx, 12, 4.6, 5, 0, skin, 3);                        // cabeza
      R.ellipse(cx, 10, 4.9, 3.8, 0, hair, 3, (px, py) => py < 11 || (long && Math.abs(px - cx) > 3)); // pelo
      const hx = f ? cx + 7 : cx + 8, hy = f ? 6 : 12, tilt = f ? 2 : 0; // varita de luz en la mano derecha
      for (let k = 0; k < 6; k++) R.dot(hx + Math.round(tilt * k / 6), hy - k, rgbaHex(glow), 4);
      R.dot(cx - 2, 12, rgba(26, 14, 34), 3); R.dot(cx + 2, 12, rgba(26, 14, 34), 3);
    }
  }
  R.finish();
  const tex = pixelTexture(R.canvas);
  const geo = [];
  for (let v = 0; v < CROWD_VARIANTS; v++) {
    geo.push([0, 1].map((f) => {
      const g = new PlaneGeometry(CW / CROWD_PPU, CROWD_H), uv = g.attributes.uv, c = v * 2 + f;
      for (let i = 0; i < uv.count; i++) uv.setX(i, (c + uv.getX(i)) / cols);
      return g;
    }));
  }
  return { tex, geo };
}
const rgbaHex = (h) => rgba((h >> 16) & 255, (h >> 8) & 255, h & 255);

export function createStage(scene) {
  const api = { cheer: 0, time: 0 };

  /* Luces: hemisférica + frontal cálida + dos neones de color a los costados */
  scene.add(new HemisphereLight(0x8b9cff, 0x2a1240, .95));
  const sun = new DirectionalLight(0xfff0e0, 1.9); sun.position.set(4, 7, 9); scene.add(sun);
  const pl = new PointLight(0xff3d9a, 60, 24, 2); pl.position.set(-8, 3.5, 2); scene.add(pl);
  const pr = new PointLight(0x2bd9ff, 60, 24, 2); pr.position.set(8, 3.5, 2); scene.add(pr);

  /* Cielo */
  const skyMat = new ShaderMaterial({ uniforms: { time: { value: 0 } }, vertexShader: SKY_VS, fragmentShader: SKY_FS, depthWrite: false });
  const sky = new Mesh(new PlaneGeometry(320, 160), skyMat);
  sky.position.set(0, 20, -90); sky.renderOrder = -10; scene.add(sky);

  /* Skyline: 3 capas a distinta profundidad (el paralaje sale solo de la perspectiva) */
  [
    { z: -70, u: .22, seed: 5,  body: '#3a2a6a', edge: '#6a4aa0', lit: .06, hMin: .2,  hMax: .45 },
    { z: -46, u: .15, seed: 9,  body: '#241a4a', edge: '#4a3a86', lit: .15, hMin: .25, hMax: .6 },
    { z: -28, u: .1,  seed: 21, body: '#150f2e', edge: '#2c2058', lit: .25, hMin: .25, hMax: .75 },
  ].forEach((L) => {
    const reps = 3, W = 512 * L.u * reps, H = 160 * L.u;
    const tex = skylineTex(L.seed, L.body, L.edge, ['#ffd166', '#ff7ab2', '#7de8ff'], L.lit, L.hMin, L.hMax);
    tex.wrapS = RepeatWrapping; tex.repeat.set(reps, 1);
    const m = new Mesh(new PlaneGeometry(W, H), new MeshBasicMaterial({ map: tex, alphaTest: .5 }));
    m.position.set(0, -6 + H / 2, L.z); scene.add(m);
  });

  /* Carteles de neón */
  const signs = [[-14, 2.2, 0xff3d71], [-6.5, 3.4, 0x29b6f6], [7, 2.6, 0xffd166], [15, 3.8, 0x7cff6b]].map(([x, y, col]) => {
    const m = new Mesh(new PlaneGeometry(1.6, 4), new MeshBasicMaterial({ map: signTex(col) }));
    m.position.set(x, y, -26.8); scene.add(m); return m;
  });

  /* Piso: base de piedra + tarima de madera con emblema central */
  const stone = new Mesh(new PlaneGeometry(120, 74).rotateX(-Math.PI / 2), new MeshLambertMaterial({
    map: canvasTex(64, 64, (g) => {
      const r = rng(3); g.fillStyle = '#231a40'; g.fillRect(0, 0, 64, 64);
      g.fillStyle = '#2f2455'; g.fillRect(0, 0, 64, 2); g.fillRect(0, 0, 2, 64);
      g.fillStyle = '#1a1230'; for (let i = 0; i < 40; i++) g.fillRect(r() * 64 | 0, r() * 64 | 0, 2, 1);
    }, [60, 37]),
  }));
  stone.position.set(0, 0, 2); scene.add(stone);

  const trim = new Mesh(new BoxGeometry(22.8, .05, 9.1), new MeshLambertMaterial({ color: 0x2a1424 }));
  trim.position.set(0, .01, -.25); scene.add(trim);
  const woodTex = canvasTex(352, 136, (g, w, h) => {
    const r = rng(17);
    for (let y = 0; y < h; y += 8) {
      for (let x = 0; x < w;) {
        const bw = 40 + (r() * 80 | 0), v = r() * 24 | 0;
        g.fillStyle = `rgb(${150 + v},${96 + v / 2},${58 + v / 3})`; g.fillRect(x, y, bw, 7);
        g.fillStyle = 'rgba(0,0,0,.35)'; g.fillRect(x, y + 7, bw, 1); g.fillRect(x + bw - 1, y, 1, 7);
        x += bw;
      }
    }
    const cx = w / 2, cy = h / 2;                       // emblema: anillo doble con marcas
    for (let a = 0; a < 360; a += 1) {
      const rad = a * Math.PI / 180;
      for (const [R, col] of [[46, '#c8282f'], [42, '#1a1a24'], [26, '#c8282f']]) g.fillStyle = col, g.fillRect(cx + Math.cos(rad) * R | 0, cy + Math.sin(rad) * R | 0, 2, 2);
    }
    g.fillStyle = '#1a1a24'; for (let a = 0; a < 8; a++) { const rad = a * Math.PI / 4; g.fillRect(cx + Math.cos(rad) * 34 - 2 | 0, cy + Math.sin(rad) * 34 - 2 | 0, 4, 4); }
  });
  const wood = new Mesh(new PlaneGeometry(22, 8.5).rotateX(-Math.PI / 2), new MeshLambertMaterial({ map: woodTex, polygonOffset: true, polygonOffsetFactor: -1 }));
  wood.position.set(0, .03, -.25); scene.add(wood);

  /* Torii gigante al fondo */
  const red = toon(0xc8282f), blk = toon(0x16161f);
  const torii = new Group(); torii.position.set(0, 0, -6.8); scene.add(torii);
  const put = (g, m, x, y, z = 0, rz = 0) => { const o = new Mesh(g, m); o.position.set(x, y, z); o.rotation.z = rz; torii.add(o); return o; };
  for (const sx of [-1, 1]) {
    put(new CylinderGeometry(.3, .34, 5.2, 10), red, sx * 4, 2.6);
    put(new CylinderGeometry(.45, .5, .3, 10), blk, sx * 4, .15);
    put(new BoxGeometry(1.8, .5, .7), blk, sx * 5.6, 5.45, 0, sx * -.22);
  }
  put(new BoxGeometry(9, .35, .45), red, 0, 3.9);
  put(new BoxGeometry(10, .3, .55), red, 0, 4.75);
  put(new BoxGeometry(11, .5, .7), blk, 0, 5.3);

  /* Público pixel art: cada persona es un sprite 2D (dos cuadros: quieto y festejando) */
  const N = 52, r = rng(77), dummy = new Object3D();
  const crowdSheet = crowdAtlas(r), crowdMat = [.86, .66].map((k) => new MeshBasicMaterial({ map: crowdSheet.tex, alphaTest: .5, color: new Color(k, k, k * 1.08) }));
  const crowd = [];
  for (let i = 0; i < N; i++) {
    const row = i % 2, v = r() * CROWD_VARIANTS | 0;
    const m = new Mesh(crowdSheet.geo[v][0], crowdMat[row]);
    m.scale.x = r() < .5 ? -1 : 1;
    scene.add(m);
    crowd.push({ m, v, frame: 0, x: -13 + (i >> 1) * 1.05 + row * .5 + r() * .3, z: row ? -4.3 : -3.1, y0: row ? .3 : 0, ph: r() * 6.28 });
  }

  /* Faroles */
  const lanternMat = new MeshBasicMaterial({ color: 0xffa24d });
  for (const sx of [-1, 1]) {
    const pole = new Mesh(new CylinderGeometry(.06, .07, 3.2, 6), new MeshLambertMaterial({ color: 0x2a1424 }));
    pole.position.set(sx * 9.6, 1.6, -1.6); scene.add(pole);
    const lan = new Mesh(new SphereGeometry(.38, 8, 6), lanternMat);
    lan.scale.y = 1.25; lan.position.set(sx * 9.6, 3.3, -1.6); scene.add(lan);
  }

  /* Pétalos que caen (por delante y por detrás de los luchadores) */
  const PN = 70, petals = new InstancedMesh(new BoxGeometry(.09, .09, .02), new MeshBasicMaterial({ color: 0xffffff, depthWrite: false }), PN);
  petals.instanceMatrix.setUsage(DynamicDrawUsage); petals.frustumCulled = false; scene.add(petals);
  const pd = [];
  for (let i = 0; i < PN; i++) {
    pd.push({ x: -14 + r() * 28, y: r() * 7.5, z: -6 + r() * 10, ph: r() * 6.28, s: .5 + r() * .7 });
    petals.setColorAt(i, new Color([0xffb7d5, 0xff8fb8, 0xffffff][i % 3]));
  }

  api.update = (dt) => {
    api.time += dt; const t = api.time;
    api.cheer = Math.max(0, api.cheer - dt * .35);
    skyMat.uniforms.time.value = t;
    signs.forEach((m, i) => { const f = Math.sin(t * .8 + i) > .92 ? .35 : 1; m.material.color.setScalar((.78 + .22 * Math.sin(t * 7 + i * 3)) * f); });
    lanternMat.color.setHSL(.08, 1, .55 + Math.sin(t * 9) * .03);

    const amp = .04 + .2 * api.cheer;
    for (const c of crowd) {
      const wave = Math.sin(t * (3 + api.cheer * 6) + c.ph), bob = Math.abs(wave) * amp;
      const frame = wave > .55 - api.cheer ? 1 : 0; // con más festejo, más gente levanta los brazos
      if (frame !== c.frame) { c.frame = frame; c.m.geometry = crowdSheet.geo[c.v][frame]; }
      c.m.position.set(c.x, c.y0 + CROWD_H / 2 + bob, c.z);
    }

    for (let i = 0; i < PN; i++) {
      const p = pd[i];
      p.y -= p.s * dt; p.x += (Math.sin(t * 1.3 + p.ph) * .4 + .25) * dt;
      if (p.y < 0) { p.y = 7.5; p.x = -14 + Math.random() * 28; }
      dummy.position.set(p.x, p.y, p.z); dummy.rotation.set(t + p.ph, 0, t * 2 + p.ph); dummy.scale.setScalar(1);
      dummy.updateMatrix(); petals.setMatrixAt(i, dummy.matrix);
    }
    petals.instanceMatrix.needsUpdate = true;
  };
  return api;
}
