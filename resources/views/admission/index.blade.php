@extends('layouts.app')

@section('title', 'Admission Desk — Open Gate Camp Mission')
@section('crumb', 'Events / Admission')
@section('page_title', 'Admission Desk')

@section('content')
<style>
  .admission-wrap{max-width:680px;margin:0 auto;}
  .admission-card{padding:22px 24px;border-radius:14px;margin:0 0 16px;}
  .admission-head{gap:14px;}
  .field-label{display:block;font-size:13px;font-weight:700;color:var(--text-secondary);margin-bottom:8px;}
  .admission-inputrow{display:flex;gap:10px;flex-wrap:wrap;}
  .admission-input{flex:1 1 200px;min-width:0;font-size:20px;font-weight:800;letter-spacing:4px;text-transform:uppercase;padding:12px 14px;border:1.5px solid var(--border);border-radius:12px;background:#fff;color:var(--text);}
  .admission-input:focus{outline:none;border-color:var(--blue-accent);}
  .admission-find{white-space:nowrap;}
  .admission-state{text-align:center;padding:28px;border-radius:14px;}
  .admission-state.danger{border:1.5px solid var(--danger);background:var(--danger-bg);}
  .admission-state.success{background:var(--success-bg);color:var(--success);}
  .admission-status.success{border:1.5px solid var(--success);background:var(--success-bg);text-align:center;}
  .state-title{font-size:24px;font-weight:800;}
  .state-title.success{color:var(--success);}
  .admission-ticket{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 0 0;border-top:1px solid var(--border);}
  .admission-ticket .label{font-size:12px;text-transform:uppercase;letter-spacing:.5px;}
  .ticket-code{font-size:22px;font-weight:800;letter-spacing:4px;color:var(--blue-accent);word-break:break-all;}
  .admission-details{width:100%;font-size:13.5px;}
  .admission-details>div{display:flex;justify-content:space-between;gap:14px;padding:9px 0;border-bottom:1px solid #f1f5f9;}
  .admission-details>div>span:last-child{font-weight:700;text-align:right;word-break:break-word;}
  .admission-admit{width:100%;padding:14px;font-size:16px;font-weight:800;}
  .admission-newscan{text-align:center;margin:8px 0 0;}
  .cam-view{position:relative;background:#000;border-radius:12px;overflow:hidden;}
  .cam-view video{width:100%;max-height:360px;display:block;}
  .cam-view canvas{position:absolute;inset:0;width:100%;height:100%;opacity:0;}
  .cam-view #camOverlay{position:absolute;top:0;left:0;right:0;bottom:0;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:14px;font-weight:600;background:rgba(0,0,0,.35);pointer-events:none;}
  @media (max-width:520px){
    .admission-card{padding:16px 14px;}
    .admission-inputrow{flex-direction:column;}
    .admission-input{font-size:17px;letter-spacing:2px;}
    .admission-find{width:100%;}
    .ticket-code{font-size:18px;letter-spacing:2px;}
  }
</style>
<div class="fade-in admission-wrap">
  <div class="section-head">
    <div><h2>Gate Admission</h2><div class="sub">Scan a ticket (QR) or enter the 6-character ticket code to admit</div></div>
  </div>

  <div class="glass-card admission-card">
    <div class="section-head admission-head">
      <div><h3 style="margin:0">Scan or enter ticket</h3><div class="sub">Use the device camera, type the code, or paste the full QR payload</div></div>
      <button type="button" id="camToggle" class="btn btn-secondary btn-sm">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:5px"><path d="M1 4l22 14M1 20l22-14"/><rect x="3" y="5" width="18" height="15" rx="2"/><circle cx="17.5" cy="13.5" r="1"/></svg>
        Scan with Camera
      </button>
    </div>

    <form method="POST" action="{{ route('admission.lookup') }}" id="lookupForm">
      @csrf
      <label class="field-label">Ticket QR / Code</label>
      <div class="admission-inputrow">
        <input name="code" id="codeInput" value="{{ request('code') }}" placeholder="e.g. 5R8DHY or scan QR"
               autofocus autocomplete="off" spellcheck="false"
               class="admission-input">
        <button type="submit" class="btn btn-accent admission-find">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:5px"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
          Find
        </button>
      </div>
    </form>
    <div class="sub" style="margin-top:8px">The scanner accepts the 6-char code, the printed <code>*CODE*</code> barcode, or the full QR payload.</div>
  </div>

  <div class="glass-card admission-card" id="camBox" style="display:none">
    <div class="section-head admission-head">
      <div><h3 style="margin:0">Camera Scanner</h3><div class="sub" id="camStatus">Starting camera...</div></div>
      <button type="button" id="camStop" class="btn btn-ghost btn-sm danger">Stop Camera</button>
    </div>
    <div class="cam-view">
      <video id="camVideo" playsinline muted></video>
      <canvas id="camCanvas"></canvas>
      <div id="camOverlay">Point the camera at the ticket QR code</div>
    </div>
  </div>

  @if($result === 'not_found')
  <div class="admission-card admission-state danger">
    <div class="state-title">NOT FOUND</div>
    <p class="text-muted" style="margin-top:6px">No attendee matched code “{{ $code }}”. Double-check the ticket or re-scan.</p>
  </div>
  @endif

  @if($result === 'found' && $attendee)
  <div class="admission-card">
    <div class="section-head admission-head">
      <div><h3 style="margin:0">{{ $attendee->name }}</h3><div class="sub">{{ $attendee->event?->title }}</div></div>
      <span class="badge {{ $attendee->checked_in_at ? 'badge-warning' : 'badge-success' }}">
        {{ $attendee->checked_in_at ? 'Already admitted' : 'Ready to admit' }}
      </span>
    </div>

    <div class="admission-ticket">
      <span class="text-muted label">Ticket</span>
      <span class="ticket-code">{{ $attendee->getTicketNo() }}</span>
    </div>

    <div class="admission-details">
      @php $cols = [
          'Fellowship' => $attendee->fellowship ?: '—',
          'Coming From' => $attendee->getRegionLabel(),
          'Phone' => $attendee->phone ?: '—',
          'Email' => $attendee->email ?: '—',
          'Status' => $attendee->getStatusLabel(),
          'Paid' => 'TZS '.number_format((float)$attendee->amount_paid, 0),
          'Fee' => 'TZS '.number_format((float)($attendee->fee_amount ?: 0), 0),
      ]; @endphp
      @foreach($cols as $k => $v)
      <div><span class="text-muted">{{ $k }}</span><span>{{ $v }}</span></div>
      @endforeach
    </div>

    @if(! $attendee->checked_in_at)
    <form method="POST" action="{{ route('admission.admit') }}" style="margin-top:18px">
      @csrf
      <input type="hidden" name="code" value="{{ $code }}">
      <button type="submit" class="btn btn-accent admission-admit">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px;margin-right:6px"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
        Admit to Event + Send Welcome SMS
      </button>
    </form>
    @else
    <div class="admission-state success">
      Admitted {{ $attendee->checked_in_at->format('d M Y H:i') }} by {{ $attendee->checked_in_by }}
    </div>
    @endif
  </div>
  @endif

  @if($result === 'admitted' && $attendee)
  <div class="admission-card admission-status success">
    <div class="state-title success">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px;margin-right:6px"><path d="M20 6L9 17l-5-5"/></svg>
      ADMITTED
    </div>
    <p style="margin:6px 0">{{ $attendee->name }} has been admitted to {{ $attendee->event?->title }}.</p>
    <div class="ticket-code">{{ $attendee->getTicketNo() }}</div>
    <div class="sub" style="margin-top:8px">
      @if(isset($sms['success']) && $sms['success']) Welcome SMS sent to {{ $attendee->phone }}.
      @elseif(isset($sms['reason']) && $sms['reason'] === 'no_phone') No phone on file — welcome SMS skipped.
      @else Welcome SMS could not be sent. @endif
    </div>
  </div>
  @endif

  <div class="admission-newscan">
    <a href="{{ route('admission.index') }}" class="btn btn-secondary btn-sm">New scan</a>
  </div>
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

  function normalize(code){
    // Pass the raw scanned value through — the backend parses the verification
    // URL, QR payload, barcode (*CODE*) or raw 6-char code.
    return String(code||'').trim();
  }

  function submitCode(code){
    if(!code) return;
    input.value = code;
    form.submit();
  }

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