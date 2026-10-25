import { MeshToonMaterial, DataTexture, RedFormat, NearestFilter } from 'three';

export const TAU = Math.PI * 2;
export const clamp = (v, a, b) => Math.min(b, Math.max(a, v));

// Curvas de suavizado (0..1 -> 0..1). "back" pasa un poquito de largo y vuelve: da snap a los golpes.
export const EASE = {
  lin: (x) => x,
  out: (x) => 1 - (1 - x) * (1 - x),
  cubic: (x) => 1 - Math.pow(1 - x, 3),
  back: (x) => { const y = x - 1; return 1 + 2.7 * y * y * y + 1.7 * y * y; },
  io: (x) => (x < .5 ? 2 * x * x : 1 - Math.pow(-2 * x + 2, 2) / 2),
};

// Toon de 3 escalones (look "cel", queda muy bien con pixeles)
const GRAD = new DataTexture(new Uint8Array([60, 140, 255]), 3, 1, RedFormat);
GRAD.minFilter = GRAD.magFilter = NearestFilter;
GRAD.needsUpdate = true;
export const toon = (color) => new MeshToonMaterial({ color, gradientMap: GRAD });
