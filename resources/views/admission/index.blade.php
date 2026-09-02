@extends('layouts.app')

@section('title', 'Admission Desk — Open Gate Camp Mission')
@section('crumb', 'Events / Admission')
@section('page_title', 'Admission Desk')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Gate Admission</h2><div class="sub">Scan a ticket (QR) or enter the 6-character ticket code to admit</div></div>
  </div>

  <div class="glass-card" style="max-width:640px;margin:0 auto">
    <div class="section-head" style="margin-bottom:12px">
      <div><h3 style="margin:0">Scan or enter ticket</h3><div class="sub">Use the device camera, type the code, or paste the full QR payload</div></div>
      <button type="button" id="camToggle" class="btn btn-secondary btn-sm">📷 Scan with Camera</button>
    </div>

    <form method="POST" action="{{ route('admission.lookup') }}" id="lookupForm">
      @csrf
      <label style="font-size:13px;font-weight:700;color:var(--text-secondary);display:block;margin-bottom:8px">Ticket QR / Code</label>
      <div style="display:flex;gap:8px">
        <input name="code" id="codeInput" value="{{ request('code') }}" placeholder="e.g. 5R8DHY or scan QR"
               autofocus autocomplete="off" spellcheck="false"
               style="flex:1;font-size:20px;font-weight:800;letter-spacing:4px;text-transform:uppercase;padding:12px 14px;border:1.5px solid var(--border);border-radius:12px;background:#fff;color:var(--text);">
        <button type="submit" class="btn btn-accent">Find</button>
      </div>
    </form>
    <div class="sub" style="margin-top:8px">The scanner accepts the 6-char code, the printed <code>*CODE*</code> barcode, or the full QR payload.</div>
  </div>

  <div class="glass-card" id="camBox" style="max-width:640px;margin:16px auto 0;display:none">
    <div class="section-head" style="margin-bottom:8px">
      <div><h3 style="margin:0">Camera Scanner</h3><div class="sub" id="camStatus">Starting camera...</div></div>
      <button type="button" id="camStop" class="btn btn-ghost btn-sm danger">Stop Camera</button>
    </div>
    <div style="position:relative;background:#000;border-radius:12px;overflow:hidden">
      <video id="camVideo" playsinline muted style="width:100%;max-height:360px;display:block"></video>
      <canvas id="camCanvas" style="position:absolute;inset:0;width:100%;height:100%;opacity:0"></canvas>
      <div id="camOverlay" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:14px;font-weight:600;background:rgba(0,0,0,.35)">Point the camera at the ticket QR code</div>
    </div>
  </div>

  @if($result === 'not_found')
  <div class="card" style="margin:20px auto;max-width:640px;text-align:center;padding:28px;border:1.5px solid var(--danger);border-radius:14px;background:var(--danger-bg)">
    <div style="font-size:26px;font-weight:800;color:var(--danger)">NOT FOUND</div>
    <p class="text-muted" style="margin-top:6px">No attendee matched code “{{ $code }}”. Double-check the ticket or re-scan.</p>
  </div>
  @endif

  @if($result === 'found' && $attendee)
  <div class="card" style="margin:20px auto;max-width:640px;padding:24px;border-radius:14px;border:1.5px solid var(--border)">
    <div class="section-head" style="margin-bottom:16px">
      <div><h3 style="margin:0">{{ $attendee->name }}</h3><div class="sub">{{ $attendee->event?->title }}</div></div>
      <span class="badge {{ $attendee->checked_in_at ? 'badge-warning' : 'badge-success' }}">
        {{ $attendee->checked_in_at ? 'Already admitted' : 'Ready to admit' }}
      </span>
    </div>

    <div class="row" style="display:flex;justify-content:space-between;align-items:center;padding:14px 0;border-top:1px solid var(--border)">
      <span class="text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px">Ticket</span>
      <span style="font-size:22px;font-weight:800;letter-spacing:4px;color:var(--blue-accent)">{{ $attendee->getTicketNo() }}</span>
    </div>

    <div class="det-table" style="width:100%;font-size:13.5px;border-collapse:collapse">
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
      <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid #f1f5f9">
        <span class="text-muted">{{ $k }}</span><span style="font-weight:700">{{ $v }}</span>
      </div>
      @endforeach
    </div>

    @if(! $attendee->checked_in_at)
    <form method="POST" action="{{ route('admission.admit') }}" style="margin-top:18px">
      @csrf
      <input type="hidden" name="code" value="{{ $code }}">
      <button type="submit" class="btn btn-accent" style="width:100%;padding:14px;font-size:16px;font-weight:800">✓ Admit to Event + Send Welcome SMS</button>
    </form>
    @else
    <div style="margin-top:18px;text-align:center;padding:12px;background:var(--success-bg);border-radius:10px;color:var(--success);font-weight:700">
      Admitted {{ $attendee->checked_in_at->format('d M Y H:i') }} by {{ $attendee->checked_in_by }}
    </div>
    @endif
  </div>
  @endif

  @if($result === 'admitted' && $attendee)
  <div class="card" style="margin:20px auto;max-width:640px;padding:24px;border-radius:14px;border:1.5px solid var(--success);background:var(--success-bg);text-align:center">
    <div style="font-size:26px;font-weight:800;color:var(--success)">✓ ADMITTED</div>
    <p style="margin:6px 0">{{ $attendee->name }} has been admitted to {{ $attendee->event?->title }}.</p>
    <div style="font-size:20px;font-weight:800;letter-spacing:4px;color:var(--blue-accent)">{{ $attendee->getTicketNo() }}</div>
    <div class="sub" style="margin-top:8px">
      @if(isset($sms['success']) && $sms['success']) Welcome SMS sent to {{ $attendee->phone }}.
      @elseif(isset($sms['reason']) && $sms['reason'] === 'no_phone') No phone on file — welcome SMS skipped.
      @else Welcome SMS could not be sent. @endif
    </div>
  </div>
  @endif

  <div style="max-width:640px;margin:20px auto;text-align:center" class="sub">
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