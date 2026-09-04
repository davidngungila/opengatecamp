<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  *{box-sizing:border-box;margin:0;padding:0;}
  @font-face { font-family: 'Manrope'; src: url("{{ storage_path('fonts/Manrope-Regular.ttf') }}"); font-weight: normal; font-style: normal; }
  @font-face { font-family: 'Manrope'; src: url("{{ storage_path('fonts/Manrope-Bold.ttf') }}"); font-weight: bold; font-style: normal; }
  @font-face { font-family: 'Manrope'; src: url("{{ storage_path('fonts/Manrope-ExtraBold.ttf') }}"); font-weight: 800; font-style: normal; }
  body{font-family:Manrope,Arial,sans-serif;color:#111;font-size:11px;line-height:1.5;}
  .header{text-align:center;margin-bottom:18px;}
  .org{font-size:13px;font-weight:800;letter-spacing:2px;text-transform:uppercase;}
  .org-line{font-size:8.5px;letter-spacing:1.5px;font-weight:bold;}
  .card-banner{
    background:{{ $card->background_color }};
    border-radius:14px;color:#fff;padding:26px 24px;text-align:center;
    border:3px solid {{ $card->accent_color }};
    margin-bottom:20px;
  }
  .type-tag{
    display:inline-block;font-size:9px;font-weight:800;letter-spacing:2px;text-transform:uppercase;
    color:{{ $card->accent_color }};
    border:1px solid {{ $card->accent_color }};border-radius:999px;padding:3px 10px;margin-bottom:10px;
  }
  .card-title{font-size:24px;font-weight:800;letter-spacing:.5px;line-height:1.25;margin-bottom:6px;}
  .card-ornament{width:60px;height:2px;background:{{ $card->accent_color }};margin:10px auto;border-radius:2px;}
  .card-message{font-size:11.5px;color:rgba(255,255,255,.92);text-align:left;margin-top:12px;white-space:pre-line;}
  .section-block{margin-bottom:20px;}
  .section-title{
    font-size:11px;font-weight:800;letter-spacing:2px;text-transform:uppercase;
    color:{{ $card->accent_color }};margin-bottom:8px;
  }
  .event-details{width:100%;border-collapse:collapse;}
  .event-details td{padding:4px 0;border-bottom:1px dotted #ddd;font-size:11px;}
  .event-details td.lbl{width:35%;font-weight:bold;color:#555;}
  .progress-row{display:block;}
  .progress-numbers{font-size:11px;margin-bottom:6px;}
  .progress-numbers b{font-size:13px;}
  .progress-bar{height:10px;background:#eee;border-radius:6px;overflow:hidden;}
  .progress-fill{height:100%;background:{{ $card->accent_color }};border-radius:6px;width:{{ $card->progress_percent }}%;}
  .qr-box{text-align:center;margin:12px 0;}
  .qr-box img{width:110px;height:110px;}
  .qr-caption{font-size:9px;color:#666;margin-top:4px;}
  .form-card{
    border:1px solid #bbb;border-radius:10px;padding:16px;
    page-break-inside:avoid;
  }
  .form-note{font-size:9.5px;color:#666;margin-bottom:10px;}
  table.form{width:100%;border-collapse:collapse;}
  table.form td{padding:7px 2px;vertical-align:middle;}
  table.form td.lbl{width:32%;font-size:10px;font-weight:bold;color:#333;}
  .dotline{border-bottom:1.5px dotted #555;height:14px;}
  .block-row td{font-size:9px;color:#999;padding-top:2px;}
  .footer{
    margin-top:16px;text-align:center;font-size:8.5px;color:#777;
    border-top:1px solid #ddd;padding-top:8px;
  }
</style>
</head>
<body>

  <div class="header">
    <div class="org">Open Gate Camp Mission</div>
    <div class="org-line">UMOJA WA VYUO · KARISMATIKI KATOLIKI TANZANIA · JIMBO LA MOSHI NA ARUSHA</div>
  </div>

  <div class="card-banner">
    <div class="type-tag">{{ $card->getTypeLabel() }}</div>
    <div class="card-title">{{ $card->title }}</div>
    <div class="card-ornament"></div>
    <div class="card-message">{{ $card->message }}</div>
  </div>

  @if($card->event)
  <div class="section-block">
    <div class="section-title">Event Details</div>
    <table class="event-details">
      <tr><td class="lbl">Event</td><td><b>{{ $card->event->title }}</b></td></tr>
      @if($card->event->start_date)
      <tr><td class="lbl">Date</td><td>{{ $card->event->start_date->format('l, d F Y') }}</td></tr>
      @endif
      @if($card->event->start_time)
      <tr><td class="lbl">Time</td><td>{{ $card->event->start_time }}</td></tr>
      @endif
      @if($card->event->venue)
      <tr><td class="lbl">Venue</td><td>{{ $card->event->venue }}</td></tr>
      @endif
      @if($card->event->end_date && $card->event->end_date->ne($card->event->start_date))
      <tr><td class="lbl">Ends</td><td>{{ $card->event->end_date->format('l, d F Y') }}</td></tr>
      @endif
    </table>
  </div>
  @endif

  @if($card->target_amount > 0)
  <div class="section-block">
    <div class="section-title">Campaign Progress</div>
    <div class="progress-numbers">
      <b>{{ $card->currency }} {{ number_format($card->total_contributions) }}</b>
      &nbsp;raised of&nbsp;
      {{ $card->currency }} {{ number_format($card->target_amount) }}
      ({{ number_format($card->progress_percent, 1) }}%)
    </div>
    <div class="progress-bar"><div class="progress-fill"></div></div>
  </div>
  @endif

  <div class="section-block">
    <div class="section-title">View This Card Online</div>
    <div class="qr-box">
      @if($qrData)
      <img src="{{ $qrData }}" alt="QR">
      @endif
      <div class="qr-caption">Scan to view the digital card and contribute online</div>
    </div>
  </div>

  <div class="section-block">
    <div class="section-title">Contribution Form</div>
    <div class="form-card">
      <div class="form-note">
        Please complete the details below and return this form with your contribution.
        Online contributions can be made at: <b>{{ $card->public_url }}</b>
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
  </div>

  <div class="footer">
    Generated {{ now()->format('d M Y H:i') }} · {{ $card->card_no }} · Open Gate Camp Mission<br>
    Your generosity makes a difference — Mungu akubariki.
  </div>
</body>
</html>