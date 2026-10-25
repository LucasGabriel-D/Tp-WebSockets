// Rasterizador pixel art: dibuja formas (cápsulas, elipses) píxel por píxel en un canvas chico,
// con sombreado de 3 tonos, contorno interior entre partes ("selout") y contorno exterior oscuro.
// Así los luchadores son sprites 2D generados por código, sin imágenes externas.
import { CanvasTexture, NearestFilter, SRGBColorSpace } from 'three';

const OUTLINE = rgba(20, 10, 30);
const L = norm(.55, -.84); // luz desde arriba y adelante (en coordenadas del canvas: y hacia abajo)

function norm(x, y) { const m = Math.hypot(x, y); return { x: x / m, y: y / m }; }
// ImageData en Uint32 es little-endian: 0xAABBGGRR
export function rgba(r, g, b, a = 255) { return ((a << 24) | (b << 16) | (g << 8) | r) >>> 0; }
const split = (c) => [(c >> 16) & 255, (c >> 8) & 255, c & 255];
const mix = (a, b, t) => a.map((v, i) => Math.round(v + (b[i] - v) * t));

// Paleta de 4 tonos a partir de un color: luz (tira a amarillo), base, sombra (tira a violeta) y contorno interior
export function palette(hex, dim = 1) {
  const base = split(hex).map((v) => v * dim);
  const hi = mix(base, [255, 244, 214], .32), lo = mix(base.map((v) => v * .62), [58, 29, 92], .28), dk = mix(base.map((v) => v * .35), [30, 14, 48], .4);
  return [hi, base, lo, dk].map(([r, g, b]) => rgba(Math.min(255, r), Math.min(255, g), Math.min(255, b)));
}

export class Raster {
  constructor(w, h) {
    this.w = w; this.h = h;
    this.canvas = document.createElement('canvas'); this.canvas.width = w; this.canvas.height = h;
    this.ctx = this.canvas.getContext('2d');
    this.img = this.ctx.createImageData(w, h);
    this.buf = new Uint32Array(this.img.data.buffer);
    this.id = new Uint8Array(w * h); // qué "capa" pintó cada píxel (0 = vacío)
  }

  clear() { this.buf.fill(0); this.id.fill(0); }

  plot(i, d, r, ex, ey, pal, layer) {
    const prev = this.id[i];
    let c;
    if (d > r - 1.1 && prev && prev !== layer) c = pal[3]; // borde contra otra parte del cuerpo
    else {
      const s = (ex * L.x + ey * L.y) / Math.max(r, 1);
      c = s > .42 ? pal[0] : s < -.32 ? pal[2] : pal[1];
    }
    this.buf[i] = c; this.id[i] = layer;
  }

  // Cápsula de radio variable entre A y B (en píxeles). keep(px, py) opcional para recortar la forma.
  capsule(ax, ay, bx, by, ra, rb, pal, layer, keep) {
    const x0 = Math.max(0, Math.floor(Math.min(ax - ra, bx - rb))), x1 = Math.min(this.w - 1, Math.ceil(Math.max(ax + ra, bx + rb)));
    const y0 = Math.max(0, Math.floor(Math.min(ay - ra, by - rb))), y1 = Math.min(this.h - 1, Math.ceil(Math.max(ay + ra, by + rb)));
    const dx = bx - ax, dy = by - ay, l2 = dx * dx + dy * dy || 1e-6;
    for (let y = y0; y <= y1; y++) {
      for (let x = x0; x <= x1; x++) {
        const px = x + .5, py = y + .5;
        const t = Math.min(1, Math.max(0, ((px - ax) * dx + (py - ay) * dy) / l2));
        const ex = px - (ax + dx * t), ey = py - (ay + dy * t), r = ra + (rb - ra) * t, d2 = ex * ex + ey * ey;
        if (d2 > r * r || (keep && !keep(px, py))) continue;
        this.plot(y * this.w + x, Math.sqrt(d2), r, ex, ey, pal, layer);
      }
    }
  }

  // Elipse rotada (ang en radianes, sentido horario en pantalla)
  ellipse(cx, cy, rx, ry, ang, pal, layer, keep) {
    const R = Math.max(rx, ry), c = Math.cos(ang), s = Math.sin(ang);
    const x0 = Math.max(0, Math.floor(cx - R)), x1 = Math.min(this.w - 1, Math.ceil(cx + R));
    const y0 = Math.max(0, Math.floor(cy - R)), y1 = Math.min(this.h - 1, Math.ceil(cy + R));
    const m = Math.min(rx, ry);
    for (let y = y0; y <= y1; y++) {
      for (let x = x0; x <= x1; x++) {
        const px = x + .5, py = y + .5, ex = px - cx, ey = py - cy;
        const lx = (ex * c + ey * s) / rx, ly = (-ex * s + ey * c) / ry, q = Math.sqrt(lx * lx + ly * ly);
        if (q > 1 || (keep && !keep(px, py))) continue;
        this.plot(y * this.w + x, q * m, m, (lx * c - ly * s) * m, (lx * s + ly * c) * m, pal, layer);
      }
    }
  }

  dot(x, y, c, layer = 250, w = 1, h = 1) {
    for (let j = 0; j < h; j++) for (let i = 0; i < w; i++) {
      const X = Math.floor(x) + i, Y = Math.floor(y) + j;
      if (X < 0 || Y < 0 || X >= this.w || Y >= this.h) continue;
      this.buf[Y * this.w + X] = c; this.id[Y * this.w + X] = layer;
    }
  }

  // Contorno exterior de 1 píxel + flash blanco opcional (cuando recibe un golpe)
  finish(flash = 0) {
    const { w, h, buf, id } = this;
    for (let y = 0; y < h; y++) {
      for (let x = 0; x < w; x++) {
        const i = y * w + x;
        if (id[i]) {
          if (flash && id[i] !== 255) {
            const c = buf[i], lum = ((c & 255) * .3 + ((c >> 8) & 255) * .59 + ((c >> 16) & 255) * .11) / 255;
            const v = lum > .3 ? 255 : 210;
            buf[i] = flash > 1 ? rgba(255, v, v - 40) : rgba(v, v, 255);
          }
          continue;
        }
        if ((x > 0 && id[i - 1] && id[i - 1] !== 255) || (x < w - 1 && id[i + 1] && id[i + 1] !== 255)
          || (y > 0 && id[i - w] && id[i - w] !== 255) || (y < h - 1 && id[i + w] && id[i + w] !== 255)) {
          buf[i] = OUTLINE; id[i] = 255;
        }
      }
    }
    this.ctx.putImageData(this.img, 0, 0);
  }
}

export function pixelTexture(canvas) {
  const t = new CanvasTexture(canvas);
  t.magFilter = t.minFilter = NearestFilter; t.generateMipmaps = false; t.colorSpace = SRGBColorSpace;
  return t;
}

// Bola de energía estilo "hadouken": tira de 4 cuadros animados (núcleo + llamas hacia atrás)
export function orbTexture(cols, size = 32) {
  const R = new Raster(size * 4, size), pals = cols.map((c) => palette(c));
  for (let f = 0; f < 4; f++) {
    const ox = f * size, cy = size / 2, cx = ox + size * .64;
    for (let k = 0; k < 5; k++) {
      const len = size * (.32 + ((f + k) % 4) * .07), a = (k - 2) * .32 + Math.sin(f * 1.7 + k) * .12;
      R.capsule(cx, cy, cx - Math.cos(a) * len, cy + Math.sin(a) * len, size * .2, 1, pals[2], 1);
    }
    R.ellipse(cx, cy, size * .3, size * .27, 0, pals[1], 2);
    R.ellipse(cx + 1, cy - 1, size * .17, size * .15, 0, pals[0], 3);
    R.dot(cx - 2 + (f % 2), cy - 3, rgba(255, 255, 255), 4, 3, 2);
  }
  R.finish();
  return pixelTexture(R.canvas);
}
