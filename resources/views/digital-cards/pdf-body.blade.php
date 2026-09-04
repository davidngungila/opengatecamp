<style>
  @font-face { font-family: Manrope; src: url("{{ ($web ?? false) ? asset('fonts/Manrope-Regular.ttf') : storage_path('fonts/Manrope-Regular.ttf') }}"); font-weight: normal; font-style: normal; }
  @font-face { font-family: Manrope; src: url("{{ ($web ?? false) ? asset('fonts/Manrope-Bold.ttf') : storage_path('fonts/Manrope-Bold.ttf') }}"); font-weight: bold; font-style: normal; }
  @font-face { font-family: Manrope; src: url("{{ ($web ?? false) ? asset('fonts/Manrope-ExtraBold.ttf') : storage_path('fonts/Manrope-ExtraBold.ttf') }}"); font-weight: 800; font-style: normal; }
  .pdf-page{font-family:Manrope,Arial,sans-serif;color:#000;font-size:10px;line-height:1.5;width:100%;}
  .pdf-page *{box-sizing:border-box;margin:0;padding:0;}
  .pdf-page .logo{text-align:center;margin-bottom:5px;}
  .pdf-page .logo img{width:30mm;height:30mm;}
  .pdf-page .center{text-align:center;}
  .pdf-page .org{font-size:14px;font-weight:800;text-transform:uppercase;letter-spacing:1px;}
  .pdf-page .org-sub{font-size:9px;letter-spacing:1.5px;font-weight:800;}
  .pdf-page .org-line{font-size:9px;letter-spacing:1px;font-weight:800;}
  .pdf-page .org-tag{font-size:11px;font-weight:800;letter-spacing:2px;margin-bottom:2px;}
  .pdf-page .title{
    font-size:12px;font-weight:800;text-align:center;letter-spacing:2px;margin:6px 0;
    border-top:1px dashed #000;border-bottom:1px dashed #000;
    padding:4px 0;text-transform:uppercase;
  }
  .pdf-page table.head{width:100%;border-collapse:collapse;font-size:9.5px;}
  .pdf-page table.head td{padding:1.5px 0;}
  .pdf-page table.head td.lbl{color:#444;}
  .pdf-page table.head td.r{text-align:right;font-weight:bold;white-space:nowrap;}
  .pdf-page table.det{width:100%;border-collapse:collapse;font-size:9.5px;margin-top:3px;}
  .pdf-page table.det td{padding:3px 0;border-bottom:1px dotted #ddd;}
  .pdf-page table.det td.lbl{width:40%;color:#444;font-weight:600;}
  .pdf-page table.det td.val{font-weight:bold;}
  .pdf-page table.form{width:100%;border-collapse:collapse;}
  .pdf-page table.form td{padding:5px 2px;vertical-align:middle;}
  .pdf-page table.form td.lbl{width:34%;font-size:8.5px;font-weight:bold;color:#333;}
  .pdf-page .ruled{border-top:1px dashed #000;border-bottom:1px dashed #000;padding:2px 0;margin:5px 0;}
  .pdf-page .cardav{
    background:{{ $card->background_color }};
    border:2px solid {{ $card->accent_color }};
    border-radius:8px;color:#fff;padding:14px 14px;text-align:center;
  }
  .pdf-page .type-tag{
    display:inline-block;font-size:7.5px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;
    color:#fff;border:1px solid {{ $card->accent_color }};border-radius:999px;padding:2px 8px;margin-bottom:6px;
  }
  .pdf-page .card-title{font-size:16px;font-weight:800;letter-spacing:.4px;line-height:1.25;}
  .pdf-page .card-ornament{width:40px;height:2px;background:{{ $card->accent_color }};margin:7px auto;}
  .pdf-page .card-message{font-size:9.5px;color:rgba(255,255,255,.92);text-align:left;margin-top:8px;white-space:pre-line;}
  .pdf-page .amt{text-align:center;font-size:10.5px;font-weight:800;margin:7px 0;background:#000;color:#fff;padding:4px 0;letter-spacing:1px;}
  .pdf-page .progress-note{font-size:8.5px;color:#444;text-align:center;margin-top:-2px;}
  .pdf-page .qr{text-align:center;margin:7px 0 3px;}
  .pdf-page .qr img{width:80px;height:80px;}
  .pdf-page .barcode{text-align:center;font-size:8px;letter-spacing:2px;font-weight:bold;margin-top:2px;}
  .pdf-page .qr-cap{text-align:center;font-size:8.5px;font-weight:bold;margin-bottom:2px;}
  .pdf-page .form-card{border:1px dashed #000;border-radius:6px;padding:9px;page-break-inside:avoid;}
  .pdf-page .form-note{font-size:8.5px;color:#444;margin-bottom:7px;}
  .pdf-page .dotline{border-bottom:1px dotted #444;height:12px;}
  .pdf-page .block-row td{font-size:7.5px;color:#777;padding-top:2px;}
  .pdf-page .foot{margin-top:7px;text-align:center;font-size:7.5px;line-height:1.4;border-top:1px dashed #000;padding-top:4px;}
</style>
<div class="pdf-page">

  <div class="logo">
    @if($web ?? false)
    <img src="{{ asset('logo.png') }}" alt="Open Gate Camp Mission">
    @elseif(file_exists(public_path('logo.png')))
    <img src="{{ public_path('logo.png') }}" alt="Open Gate Camp Mission">
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
    <tr><td class="lbl">Status</td><td class="r">{{ strtoupper($card->getStatusLabel()) }}</td></tr>
    <tr><td class="lbl">Issued</td><td class="r">{{ $card->created_at ? $card->created_at->format('d M Y') : now()->format('d M Y') }}</td></tr>
    <tr><td class="lbl">Card Link</td><td class="r" style="white-space:normal;font-weight:normal">{{ $card->public_url }}</td></tr>
  </table>

  <div class="ruled"></div>

  <div class="cardav">
    <div class="type-tag">{{ $card->getTypeLabel() }}</div>
    @if($card->title)
    <div class="card-title">{{ $card->title }}</div>
    <div class="card-ornament"></div>
    @endif
    @if($card->message)
    <div class="card-message">{{ $card->message }}</div>
    @endif
  </div>

  @if($card->event)
  <table class="det">
    <tr><td class="lbl">Event</td><td class="val">{{ $card->event->title }}</td></tr>
    @if($card->event->start_date)
    <tr><td class="lbl">Date</td><td class="val">{{ $card->event->start_date->format('l, d F Y') }}</td></tr>
    @endif
    @if($card->event->start_time)
    <tr><td class="lbl">Time</td><td class="val">{{ $card->event->start_time }}</td></tr>
    @endif
    @if($card->event->venue)
    <tr><td class="lbl">Venue</td><td class="val">{{ $card->event->venue }}</td></tr>
    @endif
  </table>
  @endif

  @if($card->target_amount > 0)
  <div class="amt">TZS {{ number_format($card->total_contributions, 0) }} RAISED OF TZS {{ number_format($card->target_amount, 0) }}</div>
  <div class="progress-note">{{ number_format($card->progress_percent, 1) }}% of campaign goal reached</div>
  @endif

  <div class="ruled"></div>

  <div class="qr">
    <div class="qr-cap">Scan to view this card and contribute online</div>
    @if($qrData)
    <img src="{{ $qrData }}" alt="QR">
    @endif
    <div class="barcode">*{{ $card->card_no }}*</div>
  </div>

  <div class="ruled"></div>

  <div class="form-card">
    <div class="form-note">
      Please complete the details below and return this form with your contribution, or give online at <b>{{ $card->public_url }}</b>
    </div>
    <table class="form">
      <tr><td class="lbl">Full Name</td><td class="dotline"></td></tr>
      <tr><td class="lbl">Phone Number</td><td class="dotline"></td></tr>
      <tr><td class="lbl">Email</td><td class="dotline"></td></tr>
      <tr><td class="lbl">Amount ({{ $card->currency }})</td><td class="dotline"></td></tr>
      <tr><td class="lbl">Payment Method</td><td class="dotline"></td></tr>
      <tr><td class="lbl">Reference / Txn No</td><td class="dotline"></td></tr>
      <tr><td class="lbl">Note / Message</td><td class="dotline"></td></tr>
      <tr class="block-row"><td></td><td>Cash &nbsp;·&nbsp; Bank Transfer &nbsp;·&nbsp; Mobile Money (M-Pesa, Tigo Pesa, Airtel Money)</td></tr>
    </table>
  </div>

  <div class="foot">
    Generated electronically — valid without a signature.<br>
    {{ $card->card_no }}@if($card->title) · {{ $card->title }}@endif · Open Gate Camp Mission
  </div>
</div>