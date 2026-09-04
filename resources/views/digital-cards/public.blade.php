<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>{{ $card->title }} — Open Gate Camp Mission</title>
<meta name="description" content="{{ Str::limit($card->message, 160) }}">
<meta property="og:title" content="{{ $card->title }}">
<meta property="og:description" content="{{ Str::limit($card->message, 200) }}">
<meta property="og:type" content="website">
<style>
  :root {
    --card-bg: {{ $card->background_color }};
    --card-accent: {{ $card->accent_color }};
    --accent-rgb: {{ $card->card_type === 'birthday' ? '255,255,255' : (hexdec(substr($card->accent_color, 1, 2)) . ',' . hexdec(substr($card->accent_color, 3, 2)) . ',' . hexdec(substr($card->accent_color, 5, 2))) }};
    --bg-rgb: {{ hexdec(substr($card->background_color, 1, 2)) . ',' . hexdec(substr($card->background_color, 3, 2)) . ',' . hexdec(substr($card->background_color, 5, 2)) }};
  }
  *{box-sizing:border-box;margin:0;padding:0;}
  html,body{height:100%;}
  body{
    font-family:'Manrope',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
    background:
      radial-gradient(1200px 600px at 80% -10%, rgba(var(--accent-rgb),.22), transparent 60%),
      radial-gradient(900px 500px at 10% 110%, rgba(var(--accent-rgb),.12), transparent 55%),
      linear-gradient(160deg, var(--card-bg), #050a1a);
    background-attachment: fixed;
    color:#fff;
    min-height:100%;
    -webkit-font-smoothing:antialiased;
    display:flex;flex-direction:column;align-items:center;
    padding:32px 16px 48px;
  }
  .brand-mark{
    display:flex;align-items:center;gap:10px;margin-bottom:28px;
  }
  .brand-mark .logo{
    width:46px;height:46px;border-radius:14px;background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.14);
    display:flex;align-items:center;justify-content:center;
    box-shadow:0 8px 24px rgba(0,0,0,.25);
  }
  .brand-mark .logo svg{width:26px;height:26px;}
  .brand-mark .org-name{font-weight:800;font-size:15px;letter-spacing:.5px;text-transform:uppercase;line-height:1.2;}
  .brand-mark .org-sub{font-size:10px;letter-spacing:2.5px;opacity:.65;text-transform:uppercase;}
  .card{
    width:100%;max-width:520px;
    background:linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.04));
    border:1px solid rgba(255,255,255,.14);
    border-radius:26px;
    backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);
    box-shadow:0 30px 80px rgba(0,0,0,.45), inset 0 1px 0 rgba(255,255,255,.12);
    padding:40px 36px 36px;
    position:relative;
    overflow:hidden;
    animation:cardIn .7s cubic-bezier(.2,.8,.2,1);
  }
  .card::before{
    content:'';position:absolute;top:0;left:0;right:0;height:6px;
    background:linear-gradient(90deg, transparent, var(--card-accent), transparent);
    opacity:.9;
  }
  .card::after{
    content:'';position:absolute;pointer-events:none;top:-60px;right:-60px;
    width:220px;height:220px;border-radius:50%;
    background:radial-gradient(circle, rgba(var(--accent-rgb),.16), transparent 70%);
  }
  @keyframes cardIn{from{opacity:0;transform:translateY(24px) scale(.98);}to{opacity:1;transform:none;}}
  .type-badge{
    display:inline-flex;align-items:center;gap:6px;
    font-size:11px;font-weight:800;letter-spacing:2px;text-transform:uppercase;
    color:var(--card-accent);
    background:rgba(var(--accent-rgb),.12);
    border:1px solid rgba(var(--accent-rgb),.35);
    border-radius:999px;padding:7px 14px;margin-bottom:22px;
  }
  .type-badge .dot{width:6px;height:6px;border-radius:50%;background:var(--card-accent);}
  h1.title{
    font-size:clamp(26px,5vw,34px);font-weight:800;line-height:1.2;letter-spacing:-.5px;
    margin-bottom:12px;
    text-shadow:0 2px 20px rgba(0,0,0,.3);
  }
  .ornament{
    display:flex;align-items:center;gap:12px;margin:6px 0 16px;
  }
  .ornament .line{height:1px;flex:1;background:linear-gradient(90deg,transparent,var(--card-accent),transparent);opacity:.7;}
  .ornament .diamond{width:8px;height:8px;transform:rotate(45deg);background:var(--card-accent);opacity:.9;border-radius:1px;}
  .message{
    font-size:15px;line-height:1.75;color:rgba(255,255,255,.88);
    margin-bottom:22px;white-space:pre-line;
  }
  .event-box{
    background:rgba(255,255,255,.07);
    border:1px solid rgba(255,255,255,.12);
    border-radius:14px;padding:14px 18px;margin-bottom:22px;
  }
  .event-box .evt-name{font-weight:800;font-size:14px;margin-bottom:4px;}
  .event-box .evt-meta{font-size:12.5px;opacity:.72;display:flex;flex-wrap:wrap;gap:10px;}
  .event-box .evt-meta svg{width:13px;height:13px;vertical-align:-2px;margin-right:3px;}
  .progress-wrap{margin:22px 0;}
  .progress-head{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px;}
  .progress-head .collected{font-size:20px;font-weight:800;color:var(--card-accent);}
  .progress-head .target{font-size:12.5px;opacity:.7;}
  .progress-track{
    height:9px;border-radius:999px;background:rgba(255,255,255,.12);overflow:hidden;
    border:1px solid rgba(255,255,255,.08);
  }
  .progress-fill{
    height:100%;border-radius:999px;background:linear-gradient(90deg,var(--card-accent),rgba(var(--accent-rgb),.65));
    box-shadow:0 0 14px rgba(var(--accent-rgb),.4);
    transition:width 1s cubic-bezier(.2,.8,.2,1);
    width:{{ $card->progress_percent }}%;
  }
  .cta-btn{
    width:100%;border:none;cursor:pointer;
    background:var(--card-accent);color:#0a0f1e;
    font-family:inherit;font-size:15px;font-weight:800;letter-spacing:.5px;
    padding:16px 24px;border-radius:14px;
    box-shadow:0 12px 32px rgba(var(--accent-rgb),.35);
    transition:transform .18s ease, box-shadow .18s ease, filter .18s ease;
    margin-top:24px;
  }
  .cta-btn:hover{transform:translateY(-2px);box-shadow:0 16px 40px rgba(var(--accent-rgb),.45);filter:brightness(1.05);}
  .cta-btn:active{transform:translateY(0);}
  .contributor-note{
    font-size:12.5px;opacity:.75;text-align:center;margin-top:14px;line-height:1.5;
  }
  .divider{height:1px;background:rgba(255,255,255,.1);margin:28px 0;}
  .footer-org{text-align:center;margin-top:28px;}
  .footer-org strong{font-size:13px;letter-spacing:1.5px;text-transform:uppercase;}
  .footer-org span{display:block;font-size:11px;opacity:.6;margin-top:3px;letter-spacing:.5px;}

  /* Contribution form */
  .form-wrap{
    display:none;
    background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);
    border-radius:16px;padding:22px;margin-top:20px;
  }
  .form-wrap.show{display:block;animation:cardIn .45s cubic-bezier(.2,.8,.2,1);}
  .form-wrap h3{font-size:16px;font-weight:800;margin-bottom:4px;}
  .form-wrap .hint{font-size:12px;opacity:.7;margin-bottom:14px;}
  .amount-preset{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px;}
  .amount-preset button{
    background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);
    color:#fff;font-family:inherit;font-weight:700;font-size:13px;
    padding:11px 6px;border-radius:10px;cursor:pointer;transition:all .15s ease;
  }
  .amount-preset button:hover{background:rgba(var(--accent-rgb),.16);border-color:var(--card-accent);}
  .amount-preset button.sel{background:var(--card-accent);color:#0a0f1e;border-color:var(--card-accent);}
  .field{margin-bottom:12px;}
  .field label{display:block;font-size:11.5px;font-weight:800;letter-spacing:1px;text-transform:uppercase;opacity:.75;margin-bottom:6px;}
  .field input,.field select,.field textarea{
    width:100%;background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.16);
    border-radius:10px;padding:12px 14px;color:#fff;
    font-family:inherit;font-size:14px;outline:none;transition:border-color .15s ease;
  }
  .field input:focus,.field select:focus,.field textarea:focus{border-color:var(--card-accent);}
  .field input::placeholder,.field textarea::placeholder{color:rgba(255,255,255,.35);}
  .field select option{color:#111;background:#fff;}
  .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;}
  .grid-2 .field{margin-bottom:0;}
  .submit-row{display:grid;grid-template-columns:1fr auto;gap:10px;margin-top:6px;}
  .submit-row input[type=submit]{
    background:var(--card-accent);color:#0a0f1e;border:none;
    font-family:inherit;font-weight:800;font-size:14px;
    padding:13px 20px;border-radius:12px;cursor:pointer;transition:filter .15s ease;
  }
  .submit-row input[type=submit]:hover{filter:brightness(1.07);}
  .submit-row .cancel{
    background:transparent;border:1px solid rgba(255,255,255,.2);color:rgba(255,255,255,.8);
    font-family:inherit;font-weight:700;font-size:13px;padding:13px 18px;border-radius:12px;cursor:pointer;
  }
  .anon-row{display:flex;align-items:center;gap:8px;font-size:12px;opacity:.8;margin-bottom:12px;}
  .anon-row input{width:16px;height:16px;accent-color:var(--card-accent);}

  /* Thank-you state */
  .thanks{
    display:none;text-align:center;padding:30px 10px 10px;
  }
  .thanks.show{display:block;animation:cardIn .5s cubic-bezier(.2,.8,.2,1);}
  .thanks .check{
    width:70px;height:70px;border-radius:50%;margin:0 auto 18px;
    background:rgba(var(--accent-rgb),.16);border:2px solid var(--card-accent);
    display:flex;align-items:center;justify-content:center;
    box-shadow:0 0 40px rgba(var(--accent-rgb),.3);
  }
  .thanks .check svg{width:34px;height:34px;stroke:var(--card-accent);}
  .thanks h3{font-size:22px;font-weight:800;margin-bottom:8px;}
  .thanks p{font-size:14px;opacity:.85;line-height:1.6;}
  .thanks .again{margin-top:22px;}
  .error-box{
    display:none;background:rgba(255,80,80,.14);border:1px solid rgba(255,120,120,.4);
    color:#ffb3b3;border-radius:10px;padding:11px 14px;font-size:13px;margin-bottom:12px;
  }
  .error-box.show{display:block;}
  .qr-line{display:flex;align-items:center;justify-content:center;gap:10px;margin-top:26px;opacity:.7;}
  .qr-line svg{width:15px;height:15px;}

  @media (max-width:520px){
    body{padding:20px 12px 40px;}
    .card{padding:30px 22px 26px;border-radius:20px;}
    .grid-2{grid-template-columns:1fr;}
    .amount-preset{grid-template-columns:repeat(3,1fr);}
  }
</style>
</head>
<body>

  <div class="brand-mark">
    <div class="logo">
      <svg viewBox="0 0 24 24" fill="none">
        <path d="M12 2L4 6v6c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10V6l-8-4z" fill="{{ $card->accent_color }}"/>
        <path d="M12 6v12M9 9h6" stroke="#0a0f1e" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
    </div>
    <div>
      <div class="org-name">Open Gate</div>
      <div class="org-sub">Camp Mission</div>
    </div>
  </div>

  <div class="card" id="cardBody">
    <div style="position:relative;z-index:1">
      <span class="type-badge"><span class="dot"></span>{{ $card->getTypeLabel() }}</span>
      <h1 class="title">{{ $card->title }}</h1>
      <div class="ornament"><span class="line"></span><span class="diamond"></span><span class="line"></span></div>

      @if($card->event)
      <div class="event-box">
        <div class="evt-name">{{ $card->event->title }}</div>
        <div class="evt-meta">
          @if($card->event->start_date)
          <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>{{ $card->event->start_date->format('d M Y') }}</span>
          @endif
          @if($card->event->venue)
          <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1116 0z"/><circle cx="12" cy="10" r="3"/></svg>{{ $card->event->venue }}</span>
          @endif
          @if($card->event->start_time)
          <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>{{ $card->event->start_time }}</span>
          @endif
        </div>
      </div>
      @endif

      <div class="message">{{ $card->message }}</div>

      @if($card->target_amount > 0)
      <div class="progress-wrap">
        <div class="progress-head">
          <span class="collected">{{ $card->currency }} {{ number_format($card->total_contributions) }}</span>
          <span class="target">of {{ $card->currency }} {{ number_format($card->target_amount) }} goal</span>
        </div>
        <div class="progress-track"><div class="progress-fill" id="progressFill"></div></div>
      </div>
      @endif

      <div class="thanks @if(session('contribution_success')) show @endif" id="thanksBox">
        <div class="check">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
        </div>
        <h3>Asante Sana!</h3>
        <p>@if(session('contribution_success')) Your contribution of <b>{{ $card->currency }} {{ session('contribution_amount') }}</b> has been received. @endif Mungu akubariki, and thank you for your generosity.</p>
        <button type="button" class="cta-btn again" id="contributeAgainBtn">{{ $card->cta_text }}</button>
      </div>

      <div id="contributeSection">
        <button type="button" class="cta-btn" id="ctaBtn">{{ $card->cta_text }}</button>
        @if($card->contributor_note)
        <div class="contributor-note">{{ $card->contributor_note }}</div>
        @endif

        <div class="form-wrap" id="formWrap">
          <div class="error-box" id="errorBox"></div>
          <h3>Make a Contribution</h3>
          <div class="hint">Fill in your details below. All contributions are appreciated.</div>
          <form method="POST" action="{{ route('cards.contribute', $card->hash) }}" id="contributeForm">
            @csrf
            <div class="amount-preset" id="amountPreset">
              <button type="button" data-amt="5000">TZS 5,000</button>
              <button type="button" data-amt="10000">TZS 10,000</button>
              <button type="button" data-amt="25000">TZS 25,000</button>
              <button type="button" data-amt="50000">TZS 50,000</button>
              <button type="button" data-amt="100000">TZS 100,000</button>
              <button type="button" data-amt="custom">Custom</button>
            </div>
            <div class="field">
              <label>Amount ({{ $card->currency }}) *</label>
              <input type="number" step="100" min="100" name="amount" id="amountInput" placeholder="0" required>
            </div>
            <div class="grid-2">
              <div class="field"><label>Your Name</label><input type="text" name="contributor_name" placeholder="Optional" value="{{ old('contributor_name') }}"></div>
              <div class="field"><label>Phone</label><input type="text" name="contributor_phone" placeholder="+255 7XX XXX XXX" value="{{ old('contributor_phone') }}"></div>
            </div>
            <div class="field"><label>Email</label><input type="email" name="contributor_email" placeholder="Optional" value="{{ old('contributor_email') }}"></div>
            <div class="field"><label>Payment Method *</label>
              <select name="method" required>
                <option value="mobile" {{ old('method')==='mobile' ? 'selected' : '' }}>Mobile Money</option>
                <option value="cash" {{ old('method')==='cash' ? 'selected' : '' }}>Cash</option>
                <option value="bank" {{ old('method')==='bank' ? 'selected' : '' }}>Bank Transfer</option>
              </select>
            </div>
            <div class="field"><label>Reference / Transaction No</label><input type="text" name="reference_no" placeholder="Optional" value="{{ old('reference_no') }}"></div>
            <div class="field"><label>Note</label><textarea name="note" rows="2" placeholder="Optional blessing / message">{{ old('note') }}</textarea></div>
            <div class="submit-row">
              <input type="submit" value="Submit Contribution">
              <button type="button" class="cancel" id="cancelBtn">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="footer-org">
    <strong>Open Gate Camp Mission</strong>
    <span>UMOJA WA VYUO · KARISMATIKI KATOLIKI TANZANIA · JIMBO LA MOSHI NA ARUSHA</span>
  </div>

<script>
(function(){
  var ctaBtn = document.getElementById('ctaBtn');
  var formWrap = document.getElementById('formWrap');
  var cancelBtn = document.getElementById('cancelBtn');
  var amountPreset = document.getElementById('amountPreset');
  var amountInput = document.getElementById('amountInput');
  var errorBox = document.getElementById('errorBox');
  var cardBody = document.getElementById('cardBody');
  var againBtn = document.getElementById('contributeAgainBtn');

  function showForm(){
    formWrap.classList.add('show');
    ctaBtn.style.display = 'none';
    if(cardBody) cardBody.scrollIntoView({behavior:'smooth',block:'center'});
    setTimeout(function(){ amountInput.focus(); }, 250);
  }

  if(ctaBtn) ctaBtn.addEventListener('click', showForm);
  if(cancelBtn) cancelBtn.addEventListener('click', function(){
    formWrap.classList.remove('show');
    ctaBtn.style.display = '';
  });

  amountPreset.addEventListener('click', function(e){
    var btn = e.target.closest('button');
    if(!btn) return;
    amountPreset.querySelectorAll('button').forEach(function(b){ b.classList.remove('sel'); });
    btn.classList.add('sel');
    if(btn.dataset.amt === 'custom'){
      amountInput.value = '';
      amountInput.focus();
    } else {
      amountInput.value = btn.dataset.amt;
    }
  });

  document.getElementById('contributeForm').addEventListener('submit', function(){
    if(!amountInput.value || Number(amountInput.value) < 100){
      e.preventDefault();
      errorBox.textContent = 'Please enter an amount of at least ' + '{{ $card->currency }}' + ' 100.';
      errorBox.classList.add('show');
      amountInput.focus();
      return;
    }
  });

  if(againBtn) againBtn.addEventListener('click', function(){
    document.getElementById('thanksBox').classList.remove('show');
    document.getElementById('contributeSection').style.display = '';
    showForm();
  });

  setTimeout(function(){
    var fill = document.getElementById('progressFill');
    if(fill){ fill.style.width = {{ $card->progress_percent }} + '%'; }
  }, 200);
})();
</script>
</body>
</html>