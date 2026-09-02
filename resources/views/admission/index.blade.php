@extends('layouts.app')

@section('title', 'Admission Desk — Open Gate Camp Mission')
@section('crumb', 'Events / Admission')
@section('page_title', 'Admission Desk')

@section('content')
<style>
  .admission-wrap{max-width:560px;margin:0 auto;}
  .field-label{display:block;font-size:13px;font-weight:700;color:var(--text-secondary);margin-bottom:8px;}

  /* ── Scan card ───────────────────────────── */
  .scan-card{padding:20px;border-radius:14px;}
  .scan-inputrow{display:flex;gap:10px;}
  .scan-input{flex:1;min-width:0;font-size:18px;font-weight:700;letter-spacing:1px;text-transform:uppercase;padding:13px 14px;border:2px solid var(--border,#e5e7eb);border-radius:12px;background:#fff;color:var(--text);transition:border-color .15s;}
  .scan-input:focus{outline:none;border-color:var(--blue-accent);}
  .scan-find{flex-shrink:0;height:50px;}
  .scan-hint{margin:10px 0 0;font-size:12.5px;color:var(--text-tertiary);text-align:center;}

  /* ── Camera ──────────────────────────────── */
  .cam-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;}
  .cam-view{position:relative;background:#000;border-radius:12px;overflow:hidden;}
  .cam-view video{width:100%;max-height:340px;display:block;}
  .cam-view canvas{position:absolute;inset:0;width:100%;height:100%;opacity:0;}
  .cam-view #camOverlay{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:14px;font-weight:600;background:rgba(0,0,0,.35);pointer-events:none;}

  /* ── Result ──────────────────────────────── */
  .result-card{text-align:center;padding:28px 22px;border-radius:14px;}
  .result-ico{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;}
  .result-title{font-size:20px;font-weight:800;margin:0 0 6px;}
  .result-sub{font-size:13.5px;color:var(--text-tertiary);margin:0;}

  .result-details{text-align:left;margin:22px auto 0;max-width:400px;background:var(--bg-muted,#f8fafc);border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:6px 20px;}
  .result-details>div{display:flex;justify-content:space-between;gap:16px;padding:10px 0;border-bottom:1px solid #e6e9ef;font-size:13.5px;}
  .result-details>div:last-child{border-bottom:none;}
  .result-details .k{color:var(--text-tertiary);white-space:nowrap;}
  .result-details .v{font-weight:700;text-align:right;word-break:break-word;}

  .result-card.bad .result-ico{background:var(--danger-bg, #fef2f2);color:var(--danger,#dc2626);}
  .result-card.good .result-ico{background:var(--success-bg,#f0fdf4);color:var(--success,#16a34a);}
  .result-card.found .result-ico{background:var(--info-bg,#eff6ff);color:var(--blue-accent,#2563eb);}
  .result-card .ticket-code{margin-top:18px;font-size:24px;font-weight:800;letter-spacing:4px;color:var(--blue-accent,#2563eb);word-break:break-all;}
  .result-admit{width:100%;padding:14px;font-size:16px;font-weight:800;margin-top:18px;}
  .newscan{display:block;text-align:center;margin:20px 0 0;font-size:13px;color:var(--text-tertiary);}
  .newscan a{font-weight:600;color:var(--blue-accent);}

  @media (max-width:520px){
    .scan-inputrow{flex-direction:column;}
    .scan-input{font-size:16px;}
    .scan-find{width:100%;}
    .ticket-code{font-size:19px;letter-spacing:2px;}
  }
</style>

<div class="fade-in admission-wrap">
  <div class="section-head">
    <div><h2>Gate Admission</h2><div class="sub">Scan a ticket QR or enter the 6-character ticket code</div></div>
  </div>

  {{-- Scan / input --}}
  <div class="glass-card scan-card" style="margin-bottom:16px">
    <form method="POST" action="{{ route('admission.lookup') }}" id="lookupForm">
      @csrf
      <label class="field-label">Ticket QR / Code</label>
      <div class="scan-inputrow">
        <input name="code" id="codeInput" value="{{ request('code') }}" placeholder="e.g. 5R8DHY"
               autofocus autocomplete="off" spellcheck="false" class="scan-input">
        <button type="submit" class="btn btn-accent scan-find">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:6px"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
          Find
        </button>
      </div>
    </form>
    <button type="button" id="camToggle" class="btn btn-secondary btn-sm" style="margin-top:12px;width:100%;justify-content:center">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:6px"><path d="M1 4l22 14M1 20l22-14"/><rect x="3" y="5" width="18" height="15" rx="2"/><circle cx="17.5" cy="13.5" r="1"/></svg>
      Scan with Camera
    </button>
    <p class="scan-hint">Accepts the 6-char code, the printed <code>*CODE*</code> barcode, or the full QR payload</p>
  </div>

  {{-- Camera --}}
  <div class="glass-card scan-card" id="camBox" style="display:none;margin-bottom:16px">
    <div class="cam-head">
      <div class="sub" id="camStatus">Starting camera...</div>
      <button type="button" id="camStop" class="btn btn-ghost btn-sm danger">Stop</button>
    </div>
    <div class="cam-view">
      <video id="camVideo" playsinline muted></video>
      <canvas id="camCanvas"></canvas>
      <div id="camOverlay">Point at the ticket QR code</div>
    </div>
  </div>

  {{-- Not found --}}
  @if($result === 'not_found')
  <div class="glass-card result-card bad">
    <div class="result-ico">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/><path d="M8.5 8.5l5 5M13.5 8.5l-5 5"/></svg>
    </div>
    <h3 class="result-title">Ticket Not Found</h3>
    <p class="result-sub">No attendee matched “{{ $code }}”. Check the code or re-scan.</p>
    <div class="newscan"><a href="{{ route('admission.index') }}">Try again</a></div>
  </div>
  @endif

  {{-- Found --}}
  @if($result === 'found' && $attendee)
  <div class="glass-card result-card found">
    <div class="result-ico">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/><path d="M9 11l2 2 4-4"/></svg>
    </div>
    <h3 class="result-title">{{ $attendee->name }}</h3>
    <p class="result-sub">{{ $attendee->event?->title }}</p>

    <div class="ticket-code">{{ $attendee->getTicketNo() }}</div>

    <div class="result-details">
      <div><span class="k">Fellowship</span><span class="v">{{ $attendee->fellowship ?: '—' }}</span></div>
      <div><span class="k">Coming From</span><span class="v">{{ $attendee->getRegionLabel() }}</span></div>
      <div><span class="k">Phone</span><span class="v">{{ $attendee->phone ?: '—' }}</span></div>
      <div><span class="k">Paid</span><span class="v">TZS {{ number_format((float)$attendee->amount_paid, 0) }}</span></div>
      <div><span class="k">Status</span><span class="v">{{ $attendee->getStatusLabel() }}</span></div>
    </div>

    @if(! $attendee->checked_in_at)
    <form method="POST" action="{{ route('admission.admit') }}">
      @csrf
      <input type="hidden" name="code" value="{{ $code }}">
      <button type="submit" class="btn btn-accent result-admit">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px;margin-right:6px"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
        Admit to Event
      </button>
    </form>
    @else
    <div style="margin-top:18px" class="result-card good">
      <p class="result-title" style="font-size:15px;margin:0 0 4px">Already admitted</p>
      <p class="result-sub" style="font-size:13px">On {{ $attendee->checked_in_at->format('d M Y \a\t H:i') }} by {{ $attendee->checked_in_by }}</p>
    </div>
    @endif
  </div>
  @endif

  {{-- Admitted --}}
  @if($result === 'admitted' && $attendee)
  <div class="glass-card result-card good">
    <div class="result-ico">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
    </div>
    <h3 class="result-title">Admitted</h3>
    <p class="result-sub">{{ $attendee->name }} has been admitted to {{ $attendee->event?->title }}.</p>
    <div class="ticket-code">{{ $attendee->getTicketNo() }}</div>
    <div class="sub" style="margin-top:10px">
      @if(isset($sms['success']) && $sms['success']) Welcome SMS sent to {{ $attendee->phone }}.
      @elseif(isset($sms['reason']) && $sms['reason'] === 'no_phone') No phone on file — welcome SMS skipped.
      @else Welcome SMS could not be sent. @endif
    </div>
  </div>
  @endif

  <div class="newscan"><a href="{{ route('admission.index') }}">New scan</a></div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/jsQR.js') }}"></script>
<script>
(function(){
  var input = document.getElementById('codeInput');
  var form = document.getElementById('lookupForm');
  if(input){ input.focus(); }

  var camToggle = document.getElementById('camToggle');
  var camStop  = document.getElementById('camStop');
  var camBox   = document.getElementById('camBox');
  var video    = document.getElementById('camVideo');
  var canvas   = document.getElementById('camCanvas');
  var camStatus= document.getElementById('camStatus');
  var scanning = false, stream = null, raf = null, lastDecode = '';

  function normalize(code){ return String(code||'').trim(); }
  function submitCode(code){ if(code){ input.value = code; form.submit(); } }

  function stopCam(){
    scanning = false;
    if(raf){ cancelAnimationFrame(raf); raf = null; }
    if(stream){ stream.getTracks().forEach(function(t){ t.stop(); }); stream = null; }
    video.srcObject = null;
    camBox.style.display = 'none';
  }

  function tick(){
    if(!scanning) return;
    if(video.readyState === video.HAVE_ENOUGH_DATA){
      var w = video.videoWidth, h = video.videoHeight;
      if(w && h){
        canvas.width = w; canvas.height = h;
        var ctx = canvas.getContext('2d', {willReadFrequently:true});
        ctx.drawImage(video, 0, 0, w, h);
        var img = ctx.getImageData(0, 0, w, h);
        var code = jsQR(img.data, img.width, img.height, {inversionAttempts:'dontInvert'});
        if(code && code.data){
          var n = normalize(code.data);
          if(n && n !== lastDecode){
            lastDecode = n;
            camStatus.textContent = 'Scanned: '+n+' — looking up...';
            submitCode(n);
            return;
          }
        }
      }
    }
    raf = requestAnimationFrame(tick);
  }

  function startCam(){
    if(!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia){
      camStatus.textContent = 'Camera not supported in this browser.';
      return;
    }
    camBox.style.display = 'block';
    camStatus.textContent = 'Requesting camera permission...';
    navigator.mediaDevices.getUserMedia({video:{facingMode:'environment', width:{ideal:1280}, height:{ideal:720}}, audio:false})
      .then(function(s){
        stream = s;
        video.srcObject = s;
        video.play();
        scanning = true;
        lastDecode = '';
        camStatus.textContent = 'Scanner ready — point at the QR code';
        raf = requestAnimationFrame(tick);
      })
      .catch(function(err){
        camStatus.textContent = 'Camera unavailable: '+(err && err.name ? err.name : 'permission denied');
      });
  }

  if(camToggle){ camToggle.addEventListener('click', startCam); }
  if(camStop){ camStop.addEventListener('click', stopCam); }
})();
</script>
@endpush