// Post-proceso "pixel": renderiza chiquito, y en una pasada final aplica
// contorno por profundidad, paleta reducida con dither, saturación, viñeta y flash.
import {
  WebGLRenderTarget, DepthTexture, NearestFilter, ShaderMaterial, PlaneGeometry,
  Mesh, Scene, OrthographicCamera, Vector2,
} from 'three';

const VS = `
varying vec2 vUv;
void main() { vUv = uv; gl_Position = vec4(position.xy, 0.0, 1.0); }`;

const FS = `
uniform sampler2D tDiffuse;
uniform sampler2D tDepth;
uniform vec2 texel;
uniform float near, far, edgeMax, flash, grey, dark;
varying vec2 vUv;
float lin(vec2 uv) {
  float z = texture2D(tDepth, uv).x * 2.0 - 1.0;
  return 2.0 * near * far / (far + near - z * (far - near));
}
void main() {
  vec3 c = texture2D(tDiffuse, vUv).rgb;
  float d = lin(vUv);
  // Laplaciano de la profundidad: borde = salto brusco (las superficies planas dan ~0)
  float lap = lin(vUv + vec2(texel.x, 0.0)) + lin(vUv - vec2(texel.x, 0.0))
            + lin(vUv + vec2(0.0, texel.y)) + lin(vUv - vec2(0.0, texel.y)) - 4.0 * d;
  float edge = d < edgeMax ? step(0.12 + 0.006 * d, lap) : 0.0;
  c = pow(max(c, 0.0), vec3(1.0 / 2.2));
  float l = dot(c, vec3(0.299, 0.587, 0.114));
  c = mix(vec3(l), c, 1.18);
  c = (c - 0.5) * 1.06 + 0.5;
  float n = fract(52.9829189 * fract(dot(gl_FragCoord.xy, vec2(0.06711056, 0.00583715))));
  c = floor(c * 8.0 + n) / 8.0;
  c = mix(c, vec3(0.03, 0.03, 0.09), edge);
  vec2 q = vUv - 0.5;
  c *= 1.0 - dot(q, q) * 0.9;
  c *= 1.0 - dark * 0.6; // "super freeze": oscurece todo menos los efectos
  c = mix(c, vec3(1.0), flash);
  c = mix(c, vec3(dot(c, vec3(0.333))), grey);
  gl_FragColor = vec4(c, 1.0);
}`;

export function createPost(renderer) {
  const uni = {
    tDiffuse: { value: null }, tDepth: { value: null }, texel: { value: new Vector2(1, 1) },
    near: { value: .5 }, far: { value: 200 }, edgeMax: { value: 20 }, flash: { value: 0 }, grey: { value: 0 }, dark: { value: 0 },
  };
  const quad = new Mesh(new PlaneGeometry(2, 2), new ShaderMaterial({ uniforms: uni, vertexShader: VS, fragmentShader: FS, depthTest: false, depthWrite: false }));
  quad.frustumCulled = false;
  const qScene = new Scene(); qScene.add(quad);
  const qCam = new OrthographicCamera(-1, 1, 1, -1, 0, 1);
  let rt = null;

  function resize(w, h) {
    if (rt) { rt.depthTexture.dispose(); rt.dispose(); }
    rt = new WebGLRenderTarget(w, h, { minFilter: NearestFilter, magFilter: NearestFilter, depthTexture: new DepthTexture(w, h) });
    uni.tDiffuse.value = rt.texture; uni.tDepth.value = rt.depthTexture; uni.texel.value.set(1 / w, 1 / h);
  }
  function render(scene, camera) {
    uni.near.value = camera.near; uni.far.value = camera.far;
    uni.edgeMax.value = camera.position.z + 6; // solo contorneo lo cercano (luchadores y escenografía), no el skyline
    renderer.setRenderTarget(rt);
    renderer.clear();
    renderer.render(scene, camera);
    renderer.setRenderTarget(null);
    renderer.render(qScene, qCam);
  }
  return { resize, render, uni };
}
