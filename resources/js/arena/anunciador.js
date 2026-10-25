// ===== TRABAJO GUIADO: el RECEPTOR =====
// Mismo código que /receptor del práctico: suscribirse al canal PÚBLICO 'style-channel' y escuchar el
// evento RemoteStyleUpdate que emite el controlador cuando alguien envía el formulario de /emisor.
// El punto en '.RemoteStyleUpdate' le dice a Echo que use el nombre de broadcastAs() tal cual
// (sin punto escucharía 'App\Events\RemoteStyleUpdate').
export function escucharAnunciador(targetDiv, { ocultarEn = 0, alRecibir } = {}) {
  const caja = targetDiv.parentElement;
  let timer = null;

  window.Echo.channel('style-channel')
    .listen('.RemoteStyleUpdate', (e) => {
      targetDiv.innerText = e.newText ?? '';       // innerText: el texto nunca se interpreta como HTML
      targetDiv.style.cssText = e.newStyle ?? '';  // el CSS inline que mandó el emisor
      caja.hidden = false;
      caja.classList.remove('nuevo'); void caja.offsetWidth; caja.classList.add('nuevo');
      clearTimeout(timer);
      if (ocultarEn) timer = setTimeout(() => { caja.hidden = true; }, ocultarEn);
      alRecibir?.(e);
    });
}
