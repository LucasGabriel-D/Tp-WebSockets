// Partículas cuadradas (pixel) en UN solo InstancedMesh: chispas, polvo, estelas.
import { InstancedMesh, BoxGeometry, MeshBasicMaterial, Object3D, Color, DynamicDrawUsage } from 'three';
import { TAU } from './common.js';

const SPARK = [0xffffff, 0xfff3b0, 0xffb347, 0xff6b6b];

export class FX {
  constructor(scene, n = 192) {
    this.n = n; this.i = 0; this.d = new Object3D();
    this.mesh = new InstancedMesh(new BoxGeometry(1, 1, 1), new MeshBasicMaterial({ color: 0xffffff, depthWrite: false }), n);
    this.mesh.instanceMatrix.setUsage(DynamicDrawUsage);
    this.mesh.frustumCulled = false;
    const white = new Color(0xffffff);
    this.p = [];
    for (let k = 0; k < n; k++) {
      this.p.push({ life: 0, max: 1, x: 0, y: 0, z: 0, vx: 0, vy: 0, vz: 0, s: .1, g: 0 });
      this.mesh.setColorAt(k, white);
      this.d.scale.setScalar(0); this.d.updateMatrix(); this.mesh.setMatrixAt(k, this.d.matrix);
    }
    scene.add(this.mesh);
    this.col = new Color();
  }

  emit(x, y, z, vx, vy, vz, life, size, color, g = 0) {
    const k = this.i; this.i = (this.i + 1) % this.n;
    Object.assign(this.p[k], { life, max: life, x, y, z, vx, vy, vz, s: size, g });
    this.mesh.setColorAt(k, this.col.set(color));
    this.mesh.instanceColor.needsUpdate = true;
  }

  update(dt) {
    const d = this.d;
    for (let k = 0; k < this.n; k++) {
      const p = this.p[k];
      if (p.life <= 0) continue;
      p.life -= dt;
      if (p.life <= 0) { d.scale.setScalar(0); }
      else {
        p.vy += p.g * dt; p.x += p.vx * dt; p.y += p.vy * dt; p.z += p.vz * dt;
        if (p.y < .03 && p.g) { p.y = .03; p.vy = 0; p.vx *= .8; }
        d.position.set(p.x, p.y, p.z);
        d.scale.setScalar(Math.max(.01, p.s * (p.life / p.max)));
      }
      d.updateMatrix(); this.mesh.setMatrixAt(k, d.matrix);
    }
    this.mesh.instanceMatrix.needsUpdate = true;
  }

  hit(x, y, kind) {
    const heavy = kind === 'heavy', block = kind === 'block';
    const n = heavy ? 24 : block ? 8 : 14, sp = heavy ? 7 : 5;
    const cols = block ? [0xbfe9ff, 0x7df9ff, 0xffffff] : SPARK;
    for (let i = 0; i < n; i++) {
      const a = Math.random() * TAU, s = sp * (.4 + Math.random() * .8);
      this.emit(x, y, .3, Math.cos(a) * s, Math.sin(a) * s + 1, (Math.random() - .5) * 2, .22 + Math.random() * .2, .07 + Math.random() * .09, cols[i % cols.length], -14);
    }
    this.emit(x, y, .45, 0, 0, 0, .09, heavy ? .95 : .6, 0xffffff, 0); // destello central
  }

  dust(x, dir = 0) {
    for (let i = 0; i < 7; i++) {
      this.emit(x + (Math.random() - .5) * .4, .05, (Math.random() - .5) * .5, (Math.random() - .5) * 2 + dir * 1.5, .6 + Math.random() * .8, 0, .35 + Math.random() * .2, .12 + Math.random() * .1, 0xb9a9d6, -2);
    }
  }
}
