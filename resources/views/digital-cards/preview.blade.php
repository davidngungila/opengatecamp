<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Preview — {{ $card->card_no }}</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0;}
  html,body{height:100%;}
  body{
    font-family:'Segoe UI',Tahoma,Arial,sans-serif;
    background:radial-gradient(1100px 500px at 80% -10%, rgba(255,255,255,.05), transparent 60%), #0b1120;
    color:#e2e8f0;
  }
  .pv-bar{
    position:sticky;top:0;z-index:20;
    display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;
    background:#0f172a;border-bottom:1px solid #1e293b;
    padding:10px 18px;
  }
  .pv-bar .id{font-size:14px;font-weight:700;letter-spacing:.3px;}
  .pv-bar .sub{font-size:12px;color:#94a3b8;margin-top:2px;}
  .pv-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
  .pv-link{
    display:inline-flex;align-items:center;gap:8px;
    background:#1e293b;color:#e2e8f0;border:1px solid #334155;border-radius:999px;
    padding:9px 16px;font-size:13px;font-weight:600;text-decoration:none;
    transition:background .15s ease, transform .15s ease;
  }
  .pv-link:hover{background:#273449;transform:translateY(-1px);}
  .pv-check{display:flex;align-items:center;gap:8px;font-size:13px;color:#cbd5e1;cursor:pointer;user-select:none;}
  .pv-check input{width:16px;height:16px;accent-color:#4caf50;cursor:pointer;}
  .pv-stage{
    padding:28px 24px;
    height:calc(100vh - 66px);
    display:flex;align-items:flex-start;justify-content:center;
    overflow:auto;
  }
  #pvWrap{position:relative;}
  .ticket-sheet{
    background:#fff;color:#000;
    width:1080px;height:1350px;
    box-shadow:0 24px 70px rgba(0,0,0,.55);
    overflow:hidden;
  }
  @media print{
    .pv-bar,.pv-check{display:none;}
    .pv-stage{display:block;height:auto;overflow:visible;padding:0;background:#fff;}
    #pvWrap{width:auto!important;height:auto!important;}
    .ticket-sheet{width:1080px;box-shadow:none;transform:none!important;}
  }
</style>
</head>
<body>

<header class="pv-bar">
  <div>
    <div class="id">{{ $card->card_no }}@if($card->title) · {{ $card->title }}@endif</div>
    <div class="sub">Digital card preview — 1080 × 1350 px</div>
  </div>
  <div class="pv-actions">
    <label class="pv-check"><input type="checkbox" id="pvActual"> Actual size</label>
    <a class="pv-link" href="{{ $publicUrl ?? route('cards.show', $card->hash) }}" target="_blank">Fungua Ukurasa</a>
    <a class="pv-link" href="{{ route('cards.index') }}">Rudi</a>
    <a class="pv-link" style="background:{{ $card->accent_color }};color:#0a0f1e;border-color:transparent" href="{{ isset($recipient) ? route('cards.recipient.pdf', $recipient) : route('cards.pdf', $card) }}">Pakua PDF</a>
  </div>
</header>

<main class="pv-stage" id="pvStage">
  <div id="pvWrap">
    <div class="ticket-sheet" id="pvSheet">
      @include('digital-cards.pdf-body', ['card' => $card, 'qrData' => $qrData, 'recipientName' => $recipientName ?? null, 'publicUrl' => $publicUrl ?? null, 'web' => true])
    </div>
  </div>
</main>

<script>
(function () {
  var stage = document.getElementById('pvStage');
  var actual = document.getElementById('pvActual');
  var wrap = document.getElementById('pvWrap');
  var sheet = document.getElementById('pvSheet');

  function fit() {
    var natW = sheet.offsetWidth, natH = sheet.offsetHeight;
    if (!natW) return;
    var availW = stage.clientWidth - 48;
    var availH = stage.clientHeight - 48;
    var s = Math.min(1, availW / natW, availH / natH);
    if (actual && actual.checked) s = 1;
    sheet.style.transform = 'scale(' + s + ')';
    sheet.style.transformOrigin = 'top left';
    wrap.style.width = (natW * s) + 'px';
    wrap.style.height = (natH * s) + 'px';
  }

  window.addEventListener('resize', fit);
  window.addEventListener('load', function () { setTimeout(fit, 50); });
  if (actual) actual.addEventListener('change', fit);
  fit();
})();
</script>
</body>
</html>