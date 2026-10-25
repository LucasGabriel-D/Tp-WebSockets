// Sonido 100% sintetizado con WebAudio (sin archivos) + locutor con la voz del navegador.
// El navegador solo deja sonar audio después de una interacción: se "desbloquea" con la primera tecla/toque.
let ac = null, master = null, noiseBuf = null;
let muted = false;
try { muted = localStorage.getItem('neoMute') === '1'; } catch {}

function ctx() {
  if (ac) return ac;
  const AC = window.AudioContext || window.webkitAudioContext;
  if (!AC) return null;
  ac = new AC(); master = ac.createGain(); master.gain.value = muted ? 0 : .5; master.connect(ac.destination);
  noiseBuf = ac.createBuffer(1, ac.sampleRate * .5, ac.sampleRate);
  const d = noiseBuf.getChannelData(0); for (let i = 0; i < d.length; i++) d[i] = Math.random() * 2 - 1;
  return ac;
}
const unlock = () => { ctx()?.resume?.(); };
addEventListener('pointerdown', unlock, { once: true });
addEventListener('keydown', unlock, { once: true });

function env(g, t, a, peak, dur) { g.gain.setValueAtTime(0, t); g.gain.linearRampToValueAtTime(peak, t + a); g.gain.exponentialRampToValueAtTime(.001, t + dur); }

function noise(dur, freq, q = 1, peak = .8, type = 'lowpass') {
  const c = ctx(); if (!c || c.state !== 'running') return;
  const t = c.currentTime, src = c.createBufferSource(), f = c.createBiquadFilter(), g = c.createGain();
  src.buffer = noiseBuf; f.type = type; f.frequency.value = freq; f.Q.value = q;
  env(g, t, .004, peak, dur); src.connect(f).connect(g).connect(master); src.start(t); src.stop(t + dur + .05);
}
function tone(f0, f1, dur, type = 'square', peak = .25) {
  const c = ctx(); if (!c || c.state !== 'running') return;
  const t = c.currentTime, o = c.createOscillator(), g = c.createGain();
  o.type = type; o.frequency.setValueAtTime(f0, t); o.frequency.exponentialRampToValueAtTime(Math.max(20, f1), t + dur);
  env(g, t, .005, peak, dur); o.connect(g).connect(master); o.start(t); o.stop(t + dur + .05);
}

export const sfx = {
  hit(heavy) { noise(heavy ? .22 : .12, heavy ? 900 : 1600, 1, heavy ? 1 : .7); tone(heavy ? 160 : 240, 50, heavy ? .2 : .1, 'sine', .6); },
  block() { noise(.08, 3500, 6, .4, 'bandpass'); tone(900, 600, .06, 'square', .12); },
  whoosh() { noise(.12, 2400, 2, .25, 'bandpass'); },
  fire(big) { tone(big ? 220 : 440, big ? 60 : 110, big ? .7 : .35, 'sawtooth', .22); noise(big ? .5 : .25, 1200, 1, .4); },
  charge() { tone(120, 900, .4, 'sawtooth', .18); },
  ko() { tone(300, 40, 1.2, 'sawtooth', .35); noise(.8, 500, 1, .9); },
  ui() { tone(880, 1320, .07, 'square', .15); },
  toggle() {
    muted = !muted;
    try { localStorage.setItem('neoMute', muted ? '1' : '0'); } catch {}
    if (master) master.gain.value = muted ? 0 : .5;
    if (muted) window.speechSynthesis?.cancel();
    return muted;
  },
  get muted() { return muted; },
};

// Locutor: voz grave y lenta, en español si el navegador tiene una
let voice = null;
const pickVoice = () => { voice = window.speechSynthesis.getVoices().find((v) => v.lang?.startsWith('es')) ?? null; };
if ('speechSynthesis' in window) { pickVoice(); speechSynthesis.addEventListener?.('voiceschanged', pickVoice); }
export function announce(text) {
  if (muted || !('speechSynthesis' in window)) return;
  speechSynthesis.cancel();
  const u = new SpeechSynthesisUtterance(text);
  if (voice) u.voice = voice;
  u.lang = voice?.lang ?? 'es-ES'; u.pitch = .3; u.rate = .9; u.volume = .9;
  speechSynthesis.speak(u);
}
