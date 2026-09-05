<style>
  @font-face { font-family: Manrope; src: url("{{ ($web ?? false) ? asset('fonts/Manrope-Regular.ttf') : storage_path('fonts/Manrope-Regular.ttf') }}"); font-weight: normal; font-style: normal; }
  @font-face { font-family: Manrope; src: url("{{ ($web ?? false) ? asset('fonts/Manrope-Bold.ttf') : storage_path('fonts/Manrope-Bold.ttf') }}"); font-weight: bold; font-style: normal; }
  @font-face { font-family: Manrope; src: url("{{ ($web ?? false) ? asset('fonts/Manrope-ExtraBold.ttf') : storage_path('fonts/Manrope-ExtraBold.ttf') }}"); font-weight: 800; font-style: normal; }
  @php
    $bgUrl = null;
    if ($card->image_path) {
        if ($web ?? false) {
            $bgUrl = asset('storage/'.$card->image_path);
        } else {
            $bgUrl = file_exists(storage_path('app/public/'.$card->image_path))
                ? str_replace('\\', '/', storage_path('app/public/'.$card->image_path))
                : null;
        }
    }
    $bc = (string) ($card->background_color ?: '#ffffff');
    if ($bgUrl === null) {
        $bc = '#ffffff';
        $isLight = true;
    } else {
        $bchex = ltrim($bc, '#');
        if (strlen($bchex) === 3) { $bchex = $bchex[0].$bchex[0].$bchex[1].$bchex[1].$bchex[2].$bchex[2]; }
        $brightness = (0.299*hexdec(substr($bchex,0,2)) + 0.587*hexdec(substr($bchex,2,2)) + 0.114*hexdec(substr($bchex,4,2)));
        $isLight = $brightness > 150;
    }
    $textColor = $isLight ? '#0f172a' : '#ffffff';
    $mutedColor = $isLight ? '#475569' : 'rgba(255,255,255,.94)';
  @endphp
  .pdf-page{font-family:Manrope,Arial,sans-serif;color:{{ $textColor }};width:1080px;height:1350px;position:relative;line-height:1.32;background-color:{{ $bc }};@if($bgUrl) background-image:url("{{ $bgUrl }}");background-size:cover;background-position:center;@endif}
  .pdf-page *{box-sizing:border-box;margin:0;padding:0;}
  .pdf-page .scrim{position:absolute;top:0;left:0;width:1080px;height:1350px;background:{{ $isLight ? 'rgba(255,255,255,.10)' : 'rgba(8,12,20,.45)' }};}
  .pdf-page .sheet{padding:0 44px;position:relative;overflow:hidden;}
  .pdf-page .logo{text-align:center;padding-top:22px;margin-bottom:8px;}
  .pdf-page .logo img{width:110px;height:110px;}
  .pdf-page .center{text-align:center;}
  .pdf-page .org{font-size:36px;font-weight:800;text-transform:uppercase;letter-spacing:3px;margin-top:12px;}
  .pdf-page .org-sub{font-size:22px;letter-spacing:4px;font-weight:800;margin-top:2px;}
  .pdf-page .org-line{font-size:22px;letter-spacing:2px;font-weight:800;margin-top:2px;}
  .pdf-page .org-tag{font-size:25px;font-weight:800;letter-spacing:5px;margin-top:2px;}
  .pdf-page .title{font-size:30px;font-weight:800;text-align:center;letter-spacing:6px;border-top:2px dashed {{ $textColor }};border-bottom:2px dashed {{ $textColor }};padding:8px 0;margin:12px 0;text-transform:uppercase;}
  .pdf-page table.head{width:100%;border-collapse:collapse;font-size:21px;}
  .pdf-page table.head td{padding:2px 6px;}
  .pdf-page table.head td.lbl{color:{{ $mutedColor }};}
  .pdf-page table.head td.r{text-align:right;font-weight:bold;white-space:nowrap;}
  .pdf-page .ruled{border-top:2px dashed {{ $textColor }};margin:8px 0;}
  .pdf-page .cardav{background:{{ $bc }};border:3px solid {{ $card->accent_color }};border-radius:18px;color:{{ $textColor }};padding:22px 34px;text-align:center;margin-top:8px;}
  .pdf-page .type-tag{display:inline-block;font-size:18px;font-weight:800;letter-spacing:4px;text-transform:uppercase;color:{{ $textColor }};border:2px solid {{ $card->accent_color }};border-radius:999px;padding:4px 16px;margin-bottom:6px;}
  .pdf-page .card-title{font-size:46px;font-weight:800;letter-spacing:.6px;line-height:1.2;}
  .pdf-page .card-subtitle{font-size:22px;font-weight:600;color:{{ $card->accent_color }};margin-top:2px;}
  .pdf-page .card-ornament{width:110px;height:3px;background:{{ $card->accent_color }};margin:8px auto;}
  .pdf-page .card-message{font-size:21px;color:{{ $mutedColor }};text-align:left;margin-top:8px;white-space:pre-line;}
  .pdf-page .amt{text-align:center;font-size:24px;font-weight:800;margin:10px 0 2px;background:#000;color:#fff;padding:6px 0;letter-spacing:2px;border-radius:8px;}
  .pdf-page .progress-note{font-size:19px;color:{{ $mutedColor }};text-align:center;}
  .pdf-page .qr{text-align:center;margin:10px 0 0;}
  .pdf-page .qr img{width:110px;height:110px;}
  .pdf-page .barcode{text-align:center;font-size:18px;letter-spacing:5px;font-weight:bold;margin-top:2px;}
  .pdf-page .qr-cap{text-align:center;font-size:19px;font-weight:bold;margin-bottom:2px;}
  .pdf-page .form-card{border:2px dashed {{ $textColor }};border-radius:14px;padding:14px 26px;page-break-inside:avoid;margin-top:10px;}
  .pdf-page .form-note{font-size:18px;color:{{ $mutedColor }};margin-bottom:10px;}
  .pdf-page .dotline{border-bottom:2px dotted {{ $mutedColor }};height:25px;}
  .pdf-page table.form{width:100%;border-collapse:collapse;}
  .pdf-page table.form td{padding:4px 4px;vertical-align:middle;}
  .pdf-page table.form td.lbl{width:36%;font-size:20px;font-weight:bold;color:{{ $textColor }};}
  .pdf-page .foot{margin-top:10px;text-align:center;font-size:18px;line-height:1.4;border-top:2px dashed {{ $textColor }};padding-top:6px;}
</style>
<div class="pdf-page">
  @if($bgUrl)<div class="scrim"></div>@endif
  <div class="sheet">

  <div class="logo">
    @if($web ?? false)
    <img src="{{ asset('logo.png') }}" alt="OpenGate Camp Connect">
    @elseif(file_exists(public_path('logo.png')))
    <img src="{{ public_path('logo.png') }}" alt="OpenGate Camp Connect">
    @endif
  </div>

  <div class="center org">UMOJA WA VYUO</div>
  <div class="center org-sub">KARISMATIKI KATOLIKI TANZANIA</div>
  <div class="center org-line">JIMBO LA MOSHI NA ARUSHA</div>
  <div class="center org-tag">OPEN GATE SEASON THREE</div>

  <div class="title">DIGITAL CARD</div>

  <table class="head" cellpadding="0" cellspacing="0">
    <tr><td class="lbl">Card No</td><td class="r"><b>{{ $card->card_no }}</b></td></tr>
    <tr><td class="lbl">Type</td><td class="r">{{ $card->getTypeLabel() }}</td></tr>
    <tr><td class="lbl">Issued</td><td class="r">{{ $card->created_at ? $card->created_at->format('d M Y') : now()->format('d M Y') }}</td></tr>
    <tr><td class="lbl">Card Link</td><td class="r" style="white-space:normal;font-weight:normal">{{ $card->public_url }}</td></tr>
  </table>

  <div class="ruled"></div>

  <div class="cardav">
    <div class="type-tag">{{ $card->getTypeLabel() }}</div>
    @if($card->title)
    <div class="card-title">{{ $card->title }}</div>
    @endif
    @if($card->event)
    <div class="card-subtitle">{{ $card->event->title }}@if($card->event->start_date) · {{ $card->event->start_date->format('d M Y') }}@endif</div>
    @endif
    <div class="card-ornament"></div>
    @if($card->message)
    <div class="card-message">{{ $card->message }}</div>
    @endif
  </div>

  @if($card->target_amount > 0)
  <div class="amt">TZS {{ number_format($card->total_contributions, 0) }} RAISED OF TZS {{ number_format($card->target_amount, 0) }}</div>
  <div class="progress-note">{{ number_format($card->progress_percent, 1) }}% of campaign goal reached</div>
  @endif

  <div class="qr">
    <div class="qr-cap">Scan to view this card and contribute online</div>
    @if($qrData)
    <img src="{{ $qrData }}" alt="QR">
    @endif
    <div class="barcode">*{{ $card->card_no }}*</div>
  </div>

  <div class="form-card">
    <div class="form-note">
      Please complete the details below and return this form with your contribution, or give online at <b>{{ $card->public_url }}</b>
    </div>
    <table class="form">
      <tr><td class="lbl">Full Name</td><td class="dotline"></td></tr>
      <tr><td class="lbl">Phone Number</td><td class="dotline"></td></tr>
      <tr><td class="lbl">Amount ({{ $card->currency }})</td><td class="dotline"></td></tr>
      <tr><td class="lbl">Payment Method</td><td class="dotline"></td></tr>
      <tr><td class="lbl">Reference / Txn No</td><td class="dotline"></td></tr>
    </table>
  </div>

  <div class="foot">
    Generated electronically — valid without a signature.<br>
    {{ $card->card_no }}@if($card->title) · {{ $card->title }}@endif · OpenGate Camp Connect
  </div>

  </div>
</div>