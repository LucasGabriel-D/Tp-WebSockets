<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Emisor · NEO DOJO</title>
<style>
  *{box-sizing:border-box}
  :root{--px:ui-monospace,Menlo,Consolas,"Courier New",monospace}
  body{margin:0;min-height:100vh;background:#0b0a2a radial-gradient(circle at 50% -20%,#3a1d5c,#0b0a2a 60%);color:#fff;font-family:var(--px);padding:24px 16px}
  .wrap{max-width:760px;margin:0 auto;display:grid;gap:16px}
  h1{margin:0;font-size:clamp(28px,6vw,48px);letter-spacing:.05em;text-shadow:4px 4px 0 #000,0 0 18px #ff3d71}
  .sub{margin:0;color:#7df9ff;letter-spacing:.12em;font-size:12px}
  .box{background:#0a061ecc;border:4px solid #fff;box-shadow:0 0 0 4px #000,8px 8px 0 4px #000;padding:16px;display:grid;gap:12px}
  label{font-weight:900;letter-spacing:.2em;font-size:12px;color:#ffd166}
  input,textarea{width:100%;margin-top:6px;padding:10px;font:14px var(--px);background:#120a2e;color:#fff;border:3px solid #fff;outline:none}
  input:focus,textarea:focus{border-color:#7df9ff}
  button{font:900 16px var(--px);letter-spacing:.2em;padding:14px;color:#fff;background:linear-gradient(#ff5a4f,#d7263d);border:3px solid #fff;cursor:pointer;box-shadow:4px 4px 0 #000;text-shadow:2px 2px 0 #000}
  button:active{transform:translate(3px,3px);box-shadow:1px 1px 0 #000}
  .presets{display:flex;flex-wrap:wrap;gap:6px}
  .presets button{font-size:11px;padding:6px 10px;background:#2b1a55;box-shadow:2px 2px 0 #000}
  .msg{padding:8px;border:2px solid;margin:0}
  .ok{background:#0f3a26;border-color:#6dff8a}.err{background:#3a0f16;border-color:#ff5a4f}
  #preview{padding:20px;border:2px dashed #fff6;min-height:60px}
  .flow{font-size:12px;line-height:1.7;color:#d8d0ff}
  .flow code{color:#ffd166}
  a{color:#7df9ff}
</style>
</head>
<body>
<div class="wrap">
  <div>
    <h1>📣 EMISOR</h1>
    <p class="sub">TRABAJO GUIADO · El mensaje aparece al instante en el lobby, en todas las peleas y en /receptor</p>
  </div>

  @if (session('ok'))
    <p class="msg ok">{{ session('ok') }}</p>
  @endif
  @if ($errors->any())
    <p class="msg err">{{ $errors->first() }}</p>
  @endif

  <form class="box" method="POST" action="{{ route('emitir') }}">
    @csrf
    <div>
      <label for="newText">TEXTO A MOSTRAR</label>
      <input type="text" id="newText" name="newText" maxlength="120" placeholder="Ej: ¡Torneo en 5 minutos!" value="{{ old('newText') }}" required>
    </div>
    <div>
      <label for="newStyle">CSS INLINE (OPCIONAL)</label>
      <textarea id="newStyle" name="newStyle" rows="5" maxlength="600" placeholder="Ej: background:#111;color:#fff;padding:24px;border-radius:16px;font-size:28px;text-align:center;">{{ old('newStyle') }}</textarea>
      <div class="presets">
        <button type="button" data-css="background:#e02424;border:2px solid #b91c1c;border-radius:12px;box-shadow:0 4px 15px rgba(220,38,38,.5);color:#fff;font-family:'Segoe UI',sans-serif;font-size:22px;padding:16px;text-align:center;">ROJO (ejemplo del práctico)</button>
        <button type="button" data-css="background:linear-gradient(90deg,#ff3d71,#ffd166);color:#000;font-weight:900;font-size:26px;padding:14px;border:4px solid #000;letter-spacing:.1em;">ARCADE</button>
        <button type="button" data-css="background:#000;color:#6dff8a;font-family:monospace;font-size:20px;padding:12px;border:2px solid #6dff8a;text-shadow:0 0 8px #6dff8a;">TERMINAL</button>
        <button type="button" data-css="">SIN ESTILO</button>
      </div>
    </div>
    <div>
      <label>VISTA PREVIA</label>
      <div id="preview">Escribí un texto…</div>
    </div>
    <button type="submit">EMITIR</button>
  </form>

</div>
<script>
  const text = document.getElementById('newText'), css = document.getElementById('newStyle'), preview = document.getElementById('preview');
  const update = () => { preview.innerText = text.value || 'Escribí un texto…'; preview.style.cssText = css.value; };
  text.addEventListener('input', update); css.addEventListener('input', update);
  document.querySelectorAll('[data-css]').forEach((b) => b.addEventListener('click', () => { css.value = b.dataset.css; update(); }));
  update();
</script>
</body>
</html>
