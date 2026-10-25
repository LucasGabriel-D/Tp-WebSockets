// Controles táctiles tipo mando. Solo se crean en dispositivos con pantalla táctil (pointer: coarse).
// En PC no aparecen (podés forzarlos con ?touch=1 para probarlos).
// La disposición (horizontal / vertical) la resuelve el CSS de game.blade.php con la clase body.touch.
export function initTouch({ hold, tap }) {
  const force = new URLSearchParams(location.search).has('touch');
  if (!force && !matchMedia('(pointer: coarse)').matches) return null;
  document.body.classList.add('touch');

  const el = document.createElement('div');
  el.id = 'touch';
  el.innerHTML = `
    <div id="pad" aria-label="Joystick"><div id="knob"></div><i class="a u">▲</i><i class="a d">▼</i><i class="a l">◀</i><i class="a r">▶</i></div>
    <div id="btns">
      <button class="b" style="grid-area:up;--c:#4db8ff" data-tap="upper"><b>UPPER</b></button>
      <button class="b" style="grid-area:spin;--c:#ffd166" data-tap="spin"><b>GIRO</b></button>
      <button class="b" style="grid-area:fire;--c:#7df9ff" data-tap="fire"><b>BOLA</b></button>
      <button class="b sup" style="grid-area:sup;--c:#ffd166" data-tap="super"><b>★ SUPER</b></button>
      <button class="b" style="grid-area:pun;--c:#6dff8a" data-tap="punch"><b>PUÑO</b></button>
      <button class="b" style="grid-area:kick;--c:#ff5a4f" data-tap="kick"><b>PATADA</b></button>
      <button class="b blk" style="grid-area:blk;--c:#ffffff" data-hold="block"><b>BLOQUEAR</b></button>
    </div>`;
  document.body.appendChild(el);
  el.addEventListener('contextmenu', (e) => e.preventDefault());
  const buzz = (ms = 8) => { try { navigator.vibrate?.(ms); } catch {} };

  // --- Joystick (multi-touch seguro: captura su propio pointer) ---
  const pad = el.querySelector('#pad'), knob = el.querySelector('#knob');
  let padId = null;
  const padSet = (e) => {
    const r = pad.getBoundingClientRect();
    let dx = (e.clientX - (r.left + r.width / 2)) / (r.width / 2);
    let dy = (e.clientY - (r.top + r.height / 2)) / (r.height / 2);
    const m = Math.hypot(dx, dy); if (m > 1) { dx /= m; dy /= m; }
    knob.style.transform = `translate(${dx * r.width * .28}px, ${dy * r.height * .28}px)`;
    hold('left', dx < -.35); hold('right', dx > .35); hold('jump', dy < -.5); hold('crouch', dy > .5);
    pad.classList.add('on');
  };
  const padEnd = () => {
    padId = null; knob.style.transform = '';
    ['left', 'right', 'jump', 'crouch'].forEach((k) => hold(k, false));
    pad.classList.remove('on');
  };
  pad.addEventListener('pointerdown', (e) => { padId = e.pointerId; pad.setPointerCapture?.(e.pointerId); padSet(e); buzz(5); });
  pad.addEventListener('pointermove', (e) => { if (e.pointerId === padId) padSet(e); });
  pad.addEventListener('pointerup', (e) => { if (e.pointerId === padId) padEnd(); });
  pad.addEventListener('pointercancel', padEnd);

  // --- Botones de acción ---
  el.querySelectorAll('[data-tap]').forEach((b) => {
    b.addEventListener('pointerdown', (e) => { e.preventDefault(); b.classList.add('on'); tap(b.dataset.tap); buzz(); });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach((ev) => b.addEventListener(ev, () => b.classList.remove('on')));
  });
  el.querySelectorAll('[data-hold]').forEach((b) => {
    b.addEventListener('pointerdown', (e) => { e.preventDefault(); b.setPointerCapture?.(e.pointerId); b.classList.add('on'); hold(b.dataset.hold, true); buzz(); });
    ['pointerup', 'pointercancel'].forEach((ev) => b.addEventListener(ev, () => { b.classList.remove('on'); hold(b.dataset.hold, false); }));
  });
  return el;
}

// Pantalla completa (en Android además fija la orientación horizontal mientras dura)
export function toggleFullscreen() {
  const d = document.documentElement;
  if (document.fullscreenElement) { document.exitFullscreen?.().catch?.(() => {}); return; }
  const p = d.requestFullscreen?.({ navigationUI: 'hide' }) ?? d.webkitRequestFullscreen?.();
  p?.then?.(() => screen.orientation?.lock?.('landscape').catch(() => {})).catch?.(() => {});
}
