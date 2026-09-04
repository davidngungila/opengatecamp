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
      radial-gradient(1100px 520px at 85% -10%, rgba(var(--accent-rgb),.10), transparent 60%),
      radial-gradient(800px 460px at 8% 110%, rgba(var(--accent-rgb),.07), transparent 55%),
      linear-gradient(180deg, #ffffff, #f6f8fc);
    background-attachment: fixed;
    color:#1f2937;
    min-height:100%;
    -webkit-font-smoothing:antialiased;
    display:flex;flex-direction:column;align-items:center;
    padding:32px 16px 48px;
    overflow-x:hidden;
    padding-top:max(32px, env(safe-area-inset-top, 0px));
    padding-bottom:max(48px, calc(env(safe-area-inset-bottom, 0px) + 16px));
    padding-left:max(16px, env(safe-area-inset-left, 0px));
    padding-right:max(16px, env(safe-area-inset-right, 0px));
  }
  .brand-mark{
    display:flex;align-items:center;gap:10px;margin-bottom:28px;
  }
  .brand-mark .logo{
    width:46px;height:46px;border-radius:14px;background:#ffffff;
    border:1px solid #e5e7eb;
    display:flex;align-items:center;justify-content:center;
    box-shadow:0 8px 24px rgba(15,23,42,.08);
  }
  .brand-mark .logo svg{width:26px;height:26px;}
  .brand-mark .org-name{font-weight:800;font-size:15px;letter-spacing:.5px;text-transform:uppercase;line-height:1.2;color:#0f172a;}
  .brand-mark .org-sub{font-size:10px;letter-spacing:2.5px;color:#64748b;text-transform:uppercase;}
  .card{
    width:100%;max-width:min(520px,100%);
    background:#ffffff;
    border:1px solid #e9edf3;
    border-radius:26px;
    box-shadow:0 30px 70px rgba(15,23,42,.12), 0 2px 8px rgba(15,23,42,.05);
    position:relative;
    overflow:hidden;
    animation:cardIn .7s cubic-bezier(.2,.8,.2,1);
  }
  .banner{
    background-color:var(--card-bg);
    background:
      radial-gradient(560px 280px at 90% -10%, rgba(var(--accent-rgb),.38), transparent 60%),
      linear-gradient(160deg, var(--card-bg), #0a0f1a);
    color:#fff;
    padding:40px 32px 30px;
    text-align:center;
    position:relative;
    overflow:hidden;
  }
  .banner::after{
    content:'';position:absolute;top:-70px;left:-70px;pointer-events:none;
    width:220px;height:220px;border-radius:50%;
    background:radial-gradient(circle, rgba(var(--accent-rgb),.22), transparent 70%);
  }
  @keyframes cardIn{from{opacity:0;transform:translateY(24px) scale(.98);}to{opacity:1;transform:none;}}
  .type-badge{
    display:inline-flex;align-items:center;gap:6px;
    font-size:11px;font-weight:800;letter-spacing:2px;text-transform:uppercase;
    color:#0a0f1e;background:var(--card-accent);
    border-radius:999px;padding:7px 14px;margin-bottom:20px;
    box-shadow:0 6px 18px rgba(0,0,0,.22);
    position:relative;z-index:1;
  }
  .type-badge .dot{width:6px;height:6px;border-radius:50%;background:#0a0f1e;}
  h1.title{
    font-size:clamp(26px,5vw,34px);font-weight:800;line-height:1.2;letter-spacing:-.5px;
    margin-bottom:12px;
    text-shadow:0 2px 18px rgba(0,0,0,.35);
    position:relative;z-index:1;
  }
  .ornament{
    display:flex;align-items:center;gap:12px;margin:6px 0 0;
    position:relative;z-index:1;
  }
  .ornament .line{height:1px;flex:1;background:linear-gradient(90deg,transparent,rgba(255,255,255,.65),transparent);opacity:.9;}
  .ornament .diamond{width:8px;height:8px;transform:rotate(45deg);background:#fff;opacity:.95;border-radius:1px;}
  .card-body{padding:28px 32px 32px;}
  .message{
    font-size:15px;line-height:1.75;color:#334155;
    margin-bottom:24px;white-space:pre-line;
  }
  .event-box{
    background:#f8fafc;
    border:1px solid #e5e9f0;
    border-radius:14px;padding:14px 18px;margin-bottom:22px;
  }
  .event-box .evt-name{font-weight:800;font-size:14px;color:#0f172a;margin-bottom:4px;}
  .event-box .evt-meta{font-size:12.5px;color:#5b6472;display:flex;flex-wrap:wrap;gap:10px;}
  .event-box .evt-meta svg{width:13px;height:13px;vertical-align:-2px;margin-right:3px;}
  .progress-wrap{margin:24px 0;}
  .progress-head{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px;}
  .progress-head .collected{font-size:20px;font-weight:800;color:#111827;}
  .progress-head .target{font-size:12.5px;color:#6b7280;}
  .progress-track{
    height:10px;border-radius:999px;background:#e8edf4;overflow:hidden;
    border:1px solid #dfe5ee;
  }
  .progress-fill{
    height:100%;border-radius:999px;background:linear-gradient(90deg,var(--card-accent),rgba(var(--accent-rgb),.65));
    box-shadow:0 0 14px rgba(var(--accent-rgb),.4);
    transition:width 1s cubic-bezier(.2,.8,.2,1);
    width:{{ $card->progress_percent }}%;
  }
  .cta-btn{
    width:100%;cursor:pointer;
    background:var(--card-accent);color:#0a0f1e;
    border:1.5px solid rgba(10,15,30,.16);
    font-family:inherit;font-size:15px;font-weight:800;letter-spacing:.5px;
    padding:16px 24px;border-radius:14px;
    box-shadow:0 12px 30px rgba(15,23,42,.14);
    transition:transform .18s ease, box-shadow .18s ease, filter .18s ease;
    margin-top:24px;
  }
  .cta-btn:hover{transform:translateY(-2px);box-shadow:0 16px 38px rgba(15,23,42,.2);filter:brightness(1.04);}
  .cta-btn:active{transform:translateY(0);}
  .contributor-note{
    font-size:12.5px;color:#6b7280;text-align:center;margin-top:14px;line-height:1.5;
  }
  .divider{height:1px;background:#e9edf3;margin:28px 0;}
  .footer-org{text-align:center;margin-top:28px;}
  .footer-org strong{font-size:13px;letter-spacing:1.5px;text-transform:uppercase;color:#111827;}
  .footer-org span{display:block;font-size:11px;color:#6b7280;margin-top:3px;letter-spacing:.5px;}

  /* Contribution form */
  .form-wrap{
    display:none;
    background:#f8fafc;border:1px solid #e5e9f0;
    border-radius:16px;padding:22px;margin-top:20px;
  }
  .form-wrap.show{display:block;animation:cardIn .45s cubic-bezier(.2,.8,.2,1);}
  .form-wrap h3{font-size:16px;font-weight:800;color:#0f172a;margin-bottom:4px;}
  .form-wrap .hint{font-size:12px;color:#6b7280;margin-bottom:14px;}
  .amount-preset{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px;}
  .amount-preset button{
    background:#ffffff;border:1px solid #d7dee9;
    color:#334155;font-family:inherit;font-weight:700;font-size:13px;
    padding:11px 6px;border-radius:10px;cursor:pointer;transition:all .15s ease;
  }
  .amount-preset button:hover{background:rgba(var(--accent-rgb),.08);border-color:var(--card-accent);}
  .amount-preset button.sel{background:var(--card-accent);color:#0a0f1e;border-color:var(--card-accent);}
  .mode-toggle{
    display:grid;grid-template-columns:1fr 1fr;gap:6px;
    background:#eef2f7;border:1px solid #e5e9f0;
    border-radius:12px;padding:5px;margin-bottom:14px;
  }
  .mode-btn{
    background:transparent;border:none;cursor:pointer;
    font-family:inherit;font-size:13px;font-weight:800;letter-spacing:.3px;
    color:#64748b;padding:10px 8px;border-radius:9px;transition:all .15s ease;
  }
  .mode-btn.sel{
    background:#ffffff;color:#0f172a;
    box-shadow:0 2px 8px rgba(15,23,42,.10);
  }
  .mode-panel{display:none;}
  .mode-panel.show{display:block;animation:cardIn .3s ease;}
  .pay-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;}
  .pay-opt{
    position:relative;display:flex;flex-direction:column;align-items:center;gap:6px;
    background:#ffffff;border:1.5px solid #dfe5ee;border-radius:12px;
    padding:14px 8px 12px;cursor:pointer;text-align:center;transition:all .15s ease;
  }
  .pay-opt svg{width:22px;height:22px;color:#64748b;}
  .pay-opt b{font-size:12px;color:#1f2937;font-weight:800;}
  .pay-opt span{font-size:9.5px;color:#94a3b8;line-height:1.3;}
  .pay-opt input{position:absolute;opacity:0;pointer-events:none;}
  .pay-opt.sel{
    border-color:var(--card-accent);background:rgba(var(--accent-rgb),.08);
    box-shadow:0 0 0 3px rgba(var(--accent-rgb),.14);
  }
  .pay-opt.sel svg{color:var(--card-accent);}
  .pledge-hint{
    font-size:11.5px;color:#5b6472;background:#f8fafc;border:1px dashed #d7dee9;
    border-radius:10px;padding:10px 12px;margin-top:-2px;line-height:1.5;
  }
  .field{margin-bottom:12px;}
  .field label{display:block;font-size:11.5px;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:#475569;margin-bottom:6px;}
  .field input,.field select,.field textarea{
    width:100%;background:#ffffff;
    border:1px solid #d1d9e6;
    border-radius:10px;padding:12px 14px;color:#111827;
    font-family:inherit;font-size:14px;outline:none;transition:border-color .15s ease, box-shadow .15s ease;
  }
  .field input:focus,.field select:focus,.field textarea:focus{border-color:var(--card-accent);box-shadow:0 0 0 3px rgba(var(--accent-rgb),.15);}
  .field input::placeholder,.field textarea::placeholder{color:#9aa3b2;}
  .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;}
  .grid-2 .field{margin-bottom:0;}
  .submit-row{display:grid;grid-template-columns:1fr auto;gap:10px;margin-top:6px;}
  .submit-row input[type=submit]{
    background:var(--card-accent);color:#0a0f1e;border:1.5px solid rgba(10,15,30,.16);
    font-family:inherit;font-weight:800;font-size:14px;
    padding:13px 20px;border-radius:12px;cursor:pointer;transition:filter .15s ease;
  }
  .submit-row input[type=submit]:hover{filter:brightness(1.05);}
  .submit-row .cancel{
    background:#ffffff;border:1px solid #d7dee9;color:#475569;
    font-family:inherit;font-weight:700;font-size:13px;padding:13px 18px;border-radius:12px;cursor:pointer;
  }
  .anon-row{display:flex;align-items:center;gap:8px;font-size:12px;color:#6b7280;margin-bottom:12px;}
  .anon-row input{width:16px;height:16px;accent-color:var(--card-accent);}

  /* Thank-you state */
  .thanks{
    display:none;text-align:center;padding:30px 10px 10px;
  }
  .thanks.show{display:block;animation:cardIn .5s cubic-bezier(.2,.8,.2,1);}
  .thanks .check{
    width:70px;height:70px;border-radius:50%;margin:0 auto 18px;
    background:rgba(var(--accent-rgb),.14);border:2px solid var(--card-accent);
    display:flex;align-items:center;justify-content:center;
    box-shadow:0 0 40px rgba(var(--accent-rgb),.3);
  }
  .thanks .check svg{width:34px;height:34px;stroke:var(--card-accent);}
  .thanks h3{font-size:22px;font-weight:800;color:#0f172a;margin-bottom:8px;}
  .thanks p{font-size:14px;color:#556070;line-height:1.6;}
  .thanks .again{margin-top:22px;}
  .error-box{
    display:none;background:#fef2f2;border:1px solid #fecaca;
    color:#b91c1c;border-radius:10px;padding:11px 14px;font-size:13px;margin-bottom:12px;
  }
  .error-box.show{display:block;}
  .qr-line{display:flex;align-items:center;justify-content:center;gap:10px;margin-top:26px;color:#6b7280;}
  .qr-line svg{width:15px;height:15px;}

  @media (max-width:760px){
    .field input,.field select,.field textarea{font-size:16px;}
  }
  @media (min-width:768px){
    body{padding:48px 24px 64px;}
    .card{max-width:min(560px,100%);}
    .brand-mark{margin-bottom:36px;}
    .brand-mark .logo{width:52px;height:52px;border-radius:16px;}
    .brand-mark .logo svg{width:30px;height:30px;}
    .brand-mark .org-name{font-size:17px;}
    .brand-mark .org-sub{font-size:11px;}
    .banner{padding:48px 44px 34px;}
    .card-body{padding:32px 40px 38px;}
    h1.title{font-size:clamp(30px,4vw,40px);}
    .message{font-size:16px;}
    .amount-preset button{padding:13px 8px;font-size:14px;}
    .pay-opt{padding:16px 10px 14px;}
    .pay-opt svg{width:26px;height:26px;}
    .footer-org{margin-top:40px;}
  }
  @media (max-width:520px){
    body{padding:20px 12px 40px;}
    .card{border-radius:20px;}
    .banner{padding:32px 22px 26px;}
    .card-body{padding:22px 20px 26px;}
    .grid-2{grid-template-columns:1fr;}
    .amount-preset{grid-template-columns:repeat(3,1fr);}
  }
  @media (max-width:360px){
    body{padding-left:10px;padding-right:10px;padding-bottom:32px;}
    .card{border-radius:16px;}
    .banner{padding:26px 16px 20px;}
    .card-body{padding:18px 16px 22px;}
    .type-badge{font-size:10px;letter-spacing:1.5px;padding:6px 11px;}
    .amount-preset{gap:6px;}
    .amount-preset button{font-size:12px;padding:10px 4px;}
    .pay-grid{gap:6px;}
    .pay-opt{padding:12px 4px 10px;}
    .pay-opt b{font-size:11px;}
    .pay-opt span{font-size:9px;}
    .form-wrap{padding:16px 14px;}
    .submit-row{grid-template-columns:1fr;}
    .submit-row .cancel{text-align:center;}
    .footer-org span{font-size:10px;}
  }
  @media (max-height:500px){
    body{padding-top:16px;padding-bottom:24px;}
    .banner{padding:22px 22px 18px;}
  }
  .cta-btn:focus-visible,.mode-btn:focus-visible,.amount-preset button:focus-visible,.submit-row input[type=submit]:focus-visible,.cancel:focus-visible{outline:2px solid var(--card-accent);outline-offset:2px;}
  @media (prefers-reduced-motion:reduce){
    *{animation:none!important;transition:none!important;}
  }
</style>
</head>
<body>

  <div class="brand-mark">
    <div class="logo">
      <svg viewBox="0 0 24 24" fill="none">
        <path d="M12 2L4 6v6c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10V6l-8-4z" fill="{{ $card->card_type === 'birthday' ? '#e11d48' : $card->accent_color }}"/>
        <path d="M12 6v12M9 9h6" stroke="#0a0f1e" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
    </div>
    <div>
      <div class="org-name">Open Gate</div>
      <div class="org-sub">Camp Mission</div>
    </div>
  </div>

  <div class="card" id="cardBody">
    <div class="banner">
      <span class="type-badge"><span class="dot"></span>{{ $card->getTypeLabel() }}</span>
      <h1 class="title">{{ $card->title }}</h1>
      <div class="ornament"><span class="line"></span><span class="diamond"></span><span class="line"></span></div>
    </div>

    <div class="card-body">
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
        <p>@if(session('pledge_success')) Your pledge of <b>{{ $card->currency }} {{ session('pledge_amount') }}</b> has been received. @endif @if(session('contribution_success')) Your contribution of <b>{{ $card->currency }} {{ session('contribution_amount') }}</b> has been received. @endif Mungu akubariki, and thank you for your generosity.</p>
        <button type="button" class="cta-btn again" id="contributeAgainBtn">{{ $card->cta_text }}</button>
      </div>

      <div id="contributeSection">
        <button type="button" class="cta-btn" id="ctaBtn">{{ $card->cta_text }}</button>
        @if($card->contributor_note)
        <div class="contributor-note">{{ $card->contributor_note }}</div>
        @endif

        <div class="form-wrap" id="formWrap">
          <div class="error-box" id="errorBox"></div>
          <h3>Give to this Campaign</h3>
          <div class="hint">Contribute now, or make a pledge to give later.</div>
          <form method="POST" action="{{ route('cards.contribute', $card->hash) }}" id="contributeForm">
            @csrf
            <input type="hidden" name="mode" id="modeInput" value="contribute">

            <div class="mode-toggle" id="modeToggle">
              <button type="button" class="mode-btn sel" data-mode="contribute">Contribute Now</button>
              <button type="button" class="mode-btn" data-mode="pledge">Pledge to Give</button>
            </div>

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
              <div class="field"><label>Your Name <span id="nameReq" style="opacity:.55">(optional)</span></label><input type="text" name="contributor_name" id="nameInput" placeholder="Optional" value="{{ old('contributor_name') }}"></div>
              <div class="field"><label>Phone</label><input type="text" name="contributor_phone" placeholder="+255 7XX XXX XXX" value="{{ old('contributor_phone') }}"></div>
            </div>
            <div class="field"><label>Email</label><input type="email" name="contributor_email" placeholder="Optional" value="{{ old('contributor_email') }}"></div>

            <div class="mode-panel show" id="panelContribute">
              <div class="field"><label>Payment Method *</label>
                <div class="pay-grid" id="payGrid">
                  <label class="pay-opt">
                    <input type="radio" name="method" value="mobile" {{ old('method')==='mobile' ? 'checked' : '' }}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2" width="10" height="20" rx="2.5"/><path d="M11 18h2"/></svg>
                    <b>Mobile Money</b><span>M-Pesa, Tigo Pesa, Airtel</span>
                  </label>
                  <label class="pay-opt">
                    <input type="radio" name="method" value="cash" {{ old('method')==='cash' ? 'checked' : '' }}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
                    <b>Cash</b><span>In person / at the venue</span>
                  </label>
                  <label class="pay-opt">
                    <input type="radio" name="method" value="bank" {{ old('method')==='bank' ? 'checked' : '' }}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10l9-6 9 6"/><path d="M5 10v8M9.5 10v8M14.5 10v8M19 10v8"/><path d="M3 19h18M2 22h20"/></svg>
                    <b>Bank Transfer</b><span>Direct / deposit</span>
                  </label>
                </div>
              </div>
              <div class="field"><label>Reference / Transaction No</label><input type="text" name="reference_no" placeholder="Optional" value="{{ old('reference_no') }}"></div>
            </div>

            <div class="mode-panel" id="panelPledge">
              <div class="pledge-hint">No payment today — we'll record your pledge and you can give anytime before the due date. A thank-you is sent to your phone.</div>
              <div class="field"><label>Pledge Due Date</label><input type="date" name="due_date" id="dueDate" value="{{ old('due_date') }}"></div>
            </div>

            <div class="field"><label>Note</label><textarea name="note" rows="2" placeholder="Optional blessing / message">{{ old('note') }}</textarea></div>
            <div class="submit-row">
              <input type="submit" id="submitBtn" value="Contribute Now">
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
  var modeToggle = document.getElementById('modeToggle');
  var modeInput = document.getElementById('modeInput');
  var panelContribute = document.getElementById('panelContribute');
  var panelPledge = document.getElementById('panelPledge');
  var payGrid = document.getElementById('payGrid');
  var nameInput = document.getElementById('nameInput');
  var submitBtn = document.getElementById('submitBtn');

  function showForm(){
    formWrap.classList.add('show');
    ctaBtn.style.display = 'none';
    if(cardBody) cardBody.scrollIntoView({behavior:'smooth',block:'center'});
    setTimeout(function(){ amountInput.focus(); }, 250);
  }

  function setMode(mode){
    modeToggle.querySelectorAll('.mode-btn').forEach(function(b){ b.classList.toggle('sel', b.dataset.mode === mode); });
    modeInput.value = mode;
    panelContribute.classList.toggle('show', mode === 'contribute');
    panelPledge.classList.toggle('show', mode === 'pledge');
    submitBtn.value = mode === 'pledge' ? 'Confirm Pledge' : 'Contribute Now';
  }

  if(modeToggle) modeToggle.addEventListener('click', function(e){
    var btn = e.target.closest('.mode-btn');
    if(btn) setMode(btn.dataset.mode);
  });

  function syncPaySel(){
    if(payGrid) payGrid.querySelectorAll('input').forEach(function(i){ i.parentElement.classList.toggle('sel', i.checked); });
  }
  if(payGrid){
    payGrid.addEventListener('change', function(e){ if(e.target.name === 'method') syncPaySel(); });
    syncPaySel();
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

  document.getElementById('contributeForm').addEventListener('submit', function(e){
    if(!amountInput.value || Number(amountInput.value) < 100){
      e.preventDefault();
      errorBox.textContent = 'Please enter an amount of at least ' + '{{ $card->currency }}' + ' 100.';
      errorBox.classList.add('show');
      amountInput.focus();
      return;
    }
    if(modeInput.value === 'pledge' && !nameInput.value.trim()){
      e.preventDefault();
      errorBox.textContent = 'Please enter your name to make a pledge.';
      errorBox.classList.add('show');
      nameInput.focus();
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