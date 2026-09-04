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
  html,body{min-height:100%;}
  body{
    font-family:'Manrope',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
    background:linear-gradient(180deg, #ffffff, #f4f7fb);
    color:#1f2937;
    -webkit-font-smoothing:antialiased;
    overflow-x:hidden;
    display:flex;flex-direction:column;
  }
  .hero{
    position:relative;
    width:100%;
    background-color:var(--card-bg);
    background:
      radial-gradient(1100px 520px at 85% -20%, rgba(var(--accent-rgb),.4), transparent 60%),
      radial-gradient(780px 460px at 8% 120%, rgba(var(--accent-rgb),.22), transparent 55%),
      linear-gradient(160deg, var(--card-bg), #0a0f1a);
    color:#fff;
    text-align:center;
    overflow:hidden;
    padding:56px 16px 64px;
    padding-top:max(48px, calc(env(safe-area-inset-top, 0px) + 16px));
    padding-left:max(16px, env(safe-area-inset-left, 0px));
    padding-right:max(16px, env(safe-area-inset-right, 0px));
  }
  .hero::after{
    content:'';position:absolute;top:-120px;left:-120px;pointer-events:none;
    width:360px;height:360px;border-radius:50%;
    background:radial-gradient(circle, rgba(var(--accent-rgb),.28), transparent 70%);
  }
  .hero::before{
    content:'';position:absolute;bottom:-160px;right:-160px;pointer-events:none;
    width:420px;height:420px;border-radius:50%;
    background:radial-gradient(circle, rgba(255,255,255,.08), transparent 70%);
  }
  .hero-inner{position:relative;z-index:1;max-width:680px;margin:0 auto;}
  .brand-mark{
    display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:42px;
  }
  .brand-mark .logo{
    width:48px;height:48px;border-radius:14px;background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.22);
    display:flex;align-items:center;justify-content:center;
    box-shadow:0 10px 28px rgba(0,0,0,.22);
  }
  .brand-mark .logo svg{width:27px;height:27px;}
  .brand-mark .org-name{font-weight:800;font-size:16px;letter-spacing:.5px;text-transform:uppercase;line-height:1.2;color:#fff;}
  .brand-mark .org-sub{font-size:10px;letter-spacing:2.5px;color:rgba(255,255,255,.72);text-transform:uppercase;}
  .type-badge{
    display:inline-flex;align-items:center;gap:6px;
    font-size:11px;font-weight:800;letter-spacing:2px;text-transform:uppercase;
    color:#0a0f1e;background:var(--card-accent);
    border-radius:999px;padding:8px 16px;margin-bottom:22px;
    box-shadow:0 8px 22px rgba(0,0,0,.25);
  }
  .type-badge .dot{width:6px;height:6px;border-radius:50%;background:#0a0f1e;}
  h1.title{
    font-size:clamp(30px,7vw,48px);font-weight:800;line-height:1.15;letter-spacing:-.5px;
    margin-bottom:14px;
    text-shadow:0 3px 24px rgba(0,0,0,.35);
  }
  .ornament{display:flex;align-items:center;justify-content:center;gap:14px;margin:10px auto 18px;max-width:320px;}
  .ornament .line{height:1px;flex:1;background:linear-gradient(90deg,transparent,rgba(255,255,255,.7),transparent);opacity:.9;}
  .ornament .diamond{width:9px;height:9px;transform:rotate(45deg);background:#fff;opacity:.95;border-radius:1px;}
  .message{
    font-size:16px;line-height:1.8;color:rgba(255,255,255,.94);
    max-width:600px;margin:0 auto;white-space:pre-line;
  }
  .content{
    flex:1;
    background:
      radial-gradient(900px 420px at 90% 0%, rgba(var(--accent-rgb),.06), transparent 60%),
      linear-gradient(180deg, #ffffff, #f4f7fb);
    padding:44px 16px 56px;
    padding-left:max(16px, env(safe-area-inset-left, 0px));
    padding-right:max(16px, env(safe-area-inset-right, 0px));
  }
  .content-inner{max-width:620px;margin:0 auto;}
  .greet{
    display:flex;align-items:center;gap:10px;
    background:linear-gradient(90deg, rgba(var(--accent-rgb),.14), rgba(var(--accent-rgb),.05));
    border:1px solid rgba(var(--accent-rgb),.35);
    color:#0f172a;border-radius:14px;padding:13px 18px;margin-bottom:24px;
    font-weight:800;font-size:14.5px;
  }
  .greet svg{width:18px;height:18px;color:var(--card-accent);flex:none;}
  .event-box{
    background:#ffffff;
    border:1px solid #e5e9f0;
    border-radius:16px;padding:16px 20px;margin-bottom:26px;
    box-shadow:0 8px 26px rgba(15,23,42,.06);
  }
  .event-box .evt-name{font-weight:800;font-size:15px;color:#0f172a;margin-bottom:4px;}
  .event-box .evt-meta{font-size:12.5px;color:#5b6472;display:flex;flex-wrap:wrap;gap:12px;}
  .event-box .evt-meta svg{width:13px;height:13px;vertical-align:-2px;margin-right:3px;}
  .progress-wrap{margin:28px 0;}
  .progress-head{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:9px;}
  .progress-head .collected{font-size:22px;font-weight:800;color:#111827;}
  .progress-head .target{font-size:13px;color:#6b7280;}
  .progress-track{
    height:11px;border-radius:999px;background:#e8edf4;overflow:hidden;
    border:1px solid #dfe5ee;
  }
  .progress-fill{
    height:100%;border-radius:999px;background:linear-gradient(90deg,var(--card-accent),rgba(var(--accent-rgb),.65));
    box-shadow:0 0 14px rgba(var(--accent-rgb),.4);
    transition:width 1s cubic-bezier(.2,.8,.2,1);
    width:{{ $card->progress_percent }}%;
  }
  .cta-grid{
    display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:26px;
  }
  .cta-btn{
    width:100%;cursor:pointer;
    background:var(--card-accent);color:#0a0f1e;
    border:1.5px solid rgba(10,15,30,.16);
    font-family:inherit;font-size:16px;font-weight:800;letter-spacing:.5px;
    padding:18px 24px;border-radius:16px;
    box-shadow:0 14px 34px rgba(var(--accent-rgb),.28);
    transition:transform .18s ease, box-shadow .18s ease, filter .18s ease, background .18s ease;
  }
  .cta-btn.alt{
    background:#ffffff;border:2px solid var(--card-accent);color:#0f172a;
    box-shadow:0 14px 34px rgba(15,23,42,.10);
  }
  .cta-btn.alt:hover{filter:none;background:rgba(var(--accent-rgb),.08);}
  .cta-btn:hover{transform:translateY(-2px);box-shadow:0 18px 42px rgba(var(--accent-rgb),.38);filter:brightness(1.04);}
  .cta-btn:active{transform:translateY(0);}
  .contributor-note{
    font-size:13px;color:#6b7280;text-align:center;margin-top:16px;line-height:1.5;
  }
  .footer-org{
    width:100%;
    background:#0f172a;
    color:#cbd5e1;
    text-align:center;
    padding:30px 16px 38px;
    padding-bottom:max(28px, calc(env(safe-area-inset-bottom, 0px) + 16px));
    padding-left:max(16px, env(safe-area-inset-left, 0px));
    padding-right:max(16px, env(safe-area-inset-right, 0px));
  }
  .footer-org strong{font-size:13px;letter-spacing:2px;text-transform:uppercase;color:#ffffff;}
  .footer-org span{display:block;font-size:11px;color:#94a3b8;margin-top:5px;letter-spacing:.5px;}

  /* Contribution form */
  .form-wrap{
    display:none;
    background:#ffffff;border:1px solid #e5e9f0;
    border-radius:20px;padding:26px;margin-top:22px;
    box-shadow:0 18px 46px rgba(15,23,42,.08);
  }
  .form-wrap.show{display:block;animation:cardIn .45s cubic-bezier(.2,.8,.2,1);}
  @keyframes cardIn{from{opacity:0;transform:translateY(18px) scale(.99);}to{opacity:1;transform:none;}}
  .form-wrap h3{font-size:18px;font-weight:800;color:#0f172a;margin-bottom:4px;}
  .form-wrap .hint{font-size:12.5px;color:#6b7280;margin-bottom:16px;}
  .amount-preset{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px;}
  .amount-preset button{
    background:#f8fafc;border:1px solid #d7dee9;
    color:#334155;font-family:inherit;font-weight:700;font-size:13px;
    padding:12px 6px;border-radius:11px;cursor:pointer;transition:all .15s ease;
  }
  .amount-preset button:hover{background:rgba(var(--accent-rgb),.08);border-color:var(--card-accent);}
  .amount-preset button.sel{background:var(--card-accent);color:#0a0f1e;border-color:var(--card-accent);}
  .mode-toggle{
    display:grid;grid-template-columns:1fr 1fr;gap:6px;
    background:#eef2f7;border:1px solid #e5e9f0;
    border-radius:12px;padding:5px;margin-bottom:16px;
  }
  .mode-btn{
    background:transparent;border:none;cursor:pointer;
    font-family:inherit;font-size:13.5px;font-weight:800;letter-spacing:.3px;
    color:#64748b;padding:11px 8px;border-radius:9px;transition:all .15s ease;
  }
  .mode-btn.sel{
    background:#ffffff;color:#0f172a;
    box-shadow:0 2px 8px rgba(15,23,42,.10);
  }
  .mode-panel{display:none;}
  .mode-panel.show{display:block;animation:cardIn .3s ease;}
  .pay-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;}
  .pay-opt{
    position:relative;display:flex;flex-direction:column;align-items:center;gap:6px;
    background:#f8fafc;border:1.5px solid #dfe5ee;border-radius:14px;
    padding:16px 8px 13px;cursor:pointer;text-align:center;transition:all .15s ease;
  }
  .pay-opt svg{width:24px;height:24px;color:#64748b;}
  .pay-opt b{font-size:12.5px;color:#1f2937;font-weight:800;}
  .pay-opt span{font-size:10px;color:#94a3b8;line-height:1.3;}
  .pay-opt input{position:absolute;opacity:0;pointer-events:none;}
  .pay-opt.sel{
    border-color:var(--card-accent);background:rgba(var(--accent-rgb),.08);
    box-shadow:0 0 0 3px rgba(var(--accent-rgb),.14);
  }
  .pay-opt.sel svg{color:var(--card-accent);}
  .pledge-hint{
    font-size:12px;color:#5b6472;background:#f8fafc;border:1px dashed #d7dee9;
    border-radius:11px;padding:11px 13px;margin-bottom:14px;line-height:1.55;
  }
  .field{margin-bottom:14px;}
  .field label{display:block;font-size:11.5px;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:#475569;margin-bottom:7px;}
  .field input,.field select,.field textarea{
    width:100%;background:#f8fafc;
    border:1px solid #d1d9e6;
    border-radius:11px;padding:13px 15px;color:#111827;
    font-family:inherit;font-size:14px;outline:none;transition:border-color .15s ease, box-shadow .15s ease;
  }
  .field input:focus,.field select:focus,.field textarea:focus{border-color:var(--card-accent);box-shadow:0 0 0 3px rgba(var(--accent-rgb),.15);background:#ffffff;}
  .field input::placeholder,.field textarea::placeholder{color:#9aa3b2;}
  .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:0;}
  .grid-2 .field{margin-bottom:14px;}
  .submit-row{display:grid;grid-template-columns:1fr auto;gap:10px;margin-top:6px;}
  .submit-row input[type=submit]{
    background:var(--card-accent);color:#0a0f1e;border:1.5px solid rgba(10,15,30,.16);
    font-family:inherit;font-weight:800;font-size:14.5px;
    padding:14px 22px;border-radius:12px;cursor:pointer;transition:filter .15s ease;
  }
  .submit-row input[type=submit]:hover{filter:brightness(1.05);}
  .submit-row .cancel{
    background:#ffffff;border:1px solid #d7dee9;color:#475569;
    font-family:inherit;font-weight:700;font-size:13px;padding:14px 18px;border-radius:12px;cursor:pointer;
  }
  .anon-row{display:flex;align-items:center;gap:8px;font-size:12px;color:#6b7280;margin-bottom:12px;}
  .anon-row input{width:16px;height:16px;accent-color:var(--card-accent);}

  /* Thank-you state */
  .thanks{
    display:none;text-align:center;padding:40px 10px 20px;
  }
  .thanks.show{display:block;animation:cardIn .5s cubic-bezier(.2,.8,.2,1);}
  .thanks .check{
    width:76px;height:76px;border-radius:50%;margin:0 auto 20px;
    background:rgba(var(--accent-rgb),.14);border:2px solid var(--card-accent);
    display:flex;align-items:center;justify-content:center;
    box-shadow:0 0 44px rgba(var(--accent-rgb),.3);
  }
  .thanks .check svg{width:36px;height:36px;stroke:var(--card-accent);}
  .thanks h3{font-size:24px;font-weight:800;color:#0f172a;margin-bottom:10px;}
  .thanks p{font-size:15px;color:#556070;line-height:1.65;}
  .thanks .again{margin-top:24px;}
  .error-box{
    display:none;background:#fef2f2;border:1px solid #fecaca;
    color:#b91c1c;border-radius:11px;padding:12px 14px;font-size:13px;margin-bottom:14px;
  }
  .error-box.show{display:block;}

  @media (max-width:760px){
    .field input,.field select,.field textarea{font-size:16px;}
  }
  @media (max-width:520px){
    .hero{padding:40px 14px 44px;}
    .brand-mark{margin-bottom:32px;}
    h1.title{font-size:clamp(26px,8vw,34px);}
    .content{padding:34px 14px 44px;}
    .form-wrap{padding:20px 16px;}
    .grid-2{grid-template-columns:1fr;}
    .amount-preset{gap:7px;}
    .pay-grid{gap:8px;}
  }
  @media (max-width:360px){
    .hero{padding-top:28px;}
    .brand-mark .logo{width:42px;height:42px;}
    .type-badge{font-size:10px;letter-spacing:1.5px;padding:7px 12px;}
    .amount-preset{gap:6px;}
    .amount-preset button{font-size:12px;padding:11px 4px;}
    .pay-grid{gap:6px;}
    .pay-opt{padding:12px 4px 10px;}
    .pay-opt b{font-size:11px;}
    .pay-opt span{font-size:9px;}
    .form-wrap{padding:16px 13px;}
    .submit-row{grid-template-columns:1fr;}
    .submit-row .cancel{text-align:center;}
    .footer-org span{font-size:10px;}
  }
  @media (max-height:500px){
    .hero{padding-top:20px;padding-bottom:28px;}
  }
  .cta-btn:focus-visible,.mode-btn:focus-visible,.amount-preset button:focus-visible,.submit-row input[type=submit]:focus-visible,.cancel:focus-visible{outline:2px solid var(--card-accent);outline-offset:2px;}
  @media (prefers-reduced-motion:reduce){
    *{animation:none!important;transition:none!important;}
  }
</style>
</head>
<body>

  <header class="hero">
    <div class="hero-inner">
      <div class="brand-mark">
        <div class="logo">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M12 2L4 6v6c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10V6l-8-4z" fill="{{ $card->card_type === 'birthday' ? '#ffffff' : $card->accent_color }}"/>
            <path d="M12 6v12M9 9h6" stroke="{{ $card->card_type === 'birthday' ? '#e11d48' : '#0a0f1e' }}" stroke-width="1.6" stroke-linecap="round"/>
          </svg>
        </div>
        <div>
          <div class="org-name">Open Gate</div>
          <div class="org-sub">Camp Mission</div>
        </div>
      </div>
      <span class="type-badge"><span class="dot"></span>{{ $card->getTypeLabel() }}</span>
      <h1 class="title">{{ $card->title }}</h1>
      <div class="ornament"><span class="line"></span><span class="diamond"></span><span class="line"></span></div>
      <div class="message">{{ $card->message }}</div>
    </div>
  </header>

  <main class="content" id="cardBody">
    <div class="content-inner">

      @if(!empty($recipient?->name))
      <div class="greet">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
        <span>Shukurani {{ $recipient->name }}! This card has been prepared for you.</span>
      </div>
      @endif

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

      @if($card->target_amount > 0)
      <div class="progress-wrap">
        <div class="progress-head">
          <span class="collected">{{ $card->currency }} {{ number_format($card->total_contributions) }}</span>
          <span class="target">of {{ $card->currency }} {{ number_format($card->target_amount) }} goal</span>
        </div>
        <div class="progress-track"><div class="progress-fill" id="progressFill"></div></div>
      </div>
      @endif

      <div class="thanks @if(session('contribution_success') || session('pledge_success')) show @endif" id="thanksBox">
        <div class="check">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
        </div>
        <h3>Asante Sana!</h3>
        <p>@if(session('pledge_success')) Your pledge of <b>{{ $card->currency }} {{ session('pledge_amount') }}</b> has been received. @endif @if(session('contribution_success')) Your contribution of <b>{{ $card->currency }} {{ session('contribution_amount') }}</b> has been received. @endif Mungu akubariki, and thank you for your generosity.</p>
        <button type="button" class="cta-btn again" id="contributeAgainBtn">{{ $card->cta_text }}</button>
      </div>

      <div id="contributeSection">
        <div class="cta-grid">
          <button type="button" class="cta-btn" id="ctaBtn">Changia Sasa</button>
          <button type="button" class="cta-btn alt" id="pledgeBtn">Weka Ahadi Leo</button>
        </div>
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
              <button type="button" class="mode-btn sel" data-mode="contribute">Changia Sasa</button>
              <button type="button" class="mode-btn" data-mode="pledge">Weka Ahadi</button>
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
              <div class="field"><label>Your Name <span style="opacity:.55">(optional)</span></label><input type="text" name="contributor_name" id="nameInput" placeholder="Optional" value="{{ old('contributor_name', $recipient->name ?? '') }}"></div>
              <div class="field"><label>Phone</label><input type="text" name="contributor_phone" placeholder="+255 7XX XXX XXX" value="{{ old('contributor_phone', $recipient->phone ?? '') }}"></div>
            </div>
            <div class="field"><label>Email</label><input type="email" name="contributor_email" placeholder="Optional" value="{{ old('contributor_email') }}"></div>

            <div class="mode-panel show" id="panelContribute">
              <div class="field"><label>Payment Method *</label>
                <div class="pay-grid" id="payGrid">
                  <label class="pay-opt">
                    <input type="radio" name="method" value="mobile" {{ old('method')==='mobile' ? 'checked' : '' }}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2" width="10" height="20" rx="2.5"/><path d="M11 18h2"/></svg>
                    <b>Mobile Money</b><span>M-Pesa, Tigo, Airtel</span>
                  </label>
                  <label class="pay-opt">
                    <input type="radio" name="method" value="cash" {{ old('method')==='cash' ? 'checked' : '' }}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
                    <b>Cash</b><span>In person / at venue</span>
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
              <input type="submit" id="submitBtn" value="Tuma Changia">
              <button type="button" class="cancel" id="cancelBtn">Cancel</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </main>

  <footer class="footer-org">
    <strong>Open Gate Camp Mission</strong>
    <span>UMOJA WA VYUO · KARISMATIKI KATOLIKI TANZANIA · JIMBO LA MOSHI NA ARUSHA</span>
  </footer>

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
  var pledgeBtn = document.getElementById('pledgeBtn');
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
    submitBtn.value = mode === 'pledge' ? 'Thibitisha Ahadi' : 'Tuma Changia';
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

  if(ctaBtn) ctaBtn.addEventListener('click', function(){ showForm(); setMode('contribute'); });
  if(pledgeBtn) pledgeBtn.addEventListener('click', function(){ showForm(); setMode('pledge'); });
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