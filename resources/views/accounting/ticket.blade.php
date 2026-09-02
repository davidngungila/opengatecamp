<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  *{box-sizing:border-box;}
  @font-face { font-family: Manrope; src: url("Manrope-Regular.ttf"); font-weight: normal; font-style: normal; }
  @font-face { font-family: Manrope; src: url("Manrope-Bold.ttf"); font-weight: bold; font-style: normal; }
  @font-face { font-family: Manrope; src: url("Manrope-ExtraBold.ttf"); font-weight: 800; font-style: normal; }
  body{font-family:Manrope,Arial,sans-serif;color:#000;margin:0;padding:0;font-size:10px;}
  .center{text-align:center;}
  .logo{text-align:center;margin-bottom:5px;}
  .logo img{width:34mm;height:34mm;}
  .org{font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:1px;text-align:center;}
  .org-sub{font-size:8px;letter-spacing:2px;text-align:center;font-weight:bold;}
  .org-line{font-size:8px;letter-spacing:1px;text-align:center;font-weight:bold;}
  .org-tag{font-size:10px;font-weight:800;letter-spacing:2px;text-align:center;margin-bottom:2px;}
  .event{font-size:11px;font-weight:bold;text-align:center;margin-top:3px;}
  .title{font-size:14px;font-weight:800;text-align:center;letter-spacing:3px;margin:6px 0;border-top:1px dashed #000;border-bottom:1px dashed #000;padding:4px 0;text-transform:uppercase;}
  .det{width:100%;font-size:10px;border-collapse:collapse;margin-top:4px;}
  .det td{padding:3px 0;border-bottom:1px dotted #ddd;}
  .det td.lbl{color:#444;width:40%;font-weight:600;}
  .det td.val{font-weight:bold;}
  .amt{text-align:center;font-size:12px;font-weight:800;margin:8px 0;background:#000;color:#fff;padding:5px 0;letter-spacing:1px;}
  .qr{text-align:center;margin:8px 0 4px;}
  .qr img{width:52px;height:52px;}
  .barcode{text-align:center;font-size:9px;letter-spacing:2px;font-weight:bold;margin-top:3px;}
  .foot{margin-top:6px;text-align:center;font-size:8px;line-height:1.4;border-top:1px dashed #000;padding-top:5px;}
  .admit{text-align:center;font-size:11px;font-weight:800;margin-top:5px;background:#000;color:#fff;padding:3px 0;letter-spacing:1px;}
  table.head{width:100%;border-collapse:collapse;font-size:10px;margin-top:2px;}
  table.head td{padding:1px 0;}
  table.head td.lbl{color:#444;}
  table.head td.r{text-align:right;font-weight:bold;}
</style>
</head>
<body>
  @if(!empty($logoPath) && file_exists($logoPath))
  <div class="logo"><img src="{{ $logoPath }}" alt="{{ $org }}"></div>
  @endif
  <div class="center org">UMOJA WA VYUO</div>
  <div class="center org-sub">KARISMATIKI KATOLIKI TANZANIA</div>
  <div class="center org-line">JIMBO LA MOSHI NA ARUSHA</div>
  <div class="center org-tag">OPEN GATE SEASON THREE</div>
  <div class="event">{{ $event->title }}</div>
  <div class="center" style="font-size:9px">{{ $event->venue ?? '' }} · {{ $event->start_date->format('d M Y') }}</div>

  <div class="title">TICKET</div>

  <table class="head" cellpadding="0" cellspacing="0">
    <tr><td class="lbl">Ticket No</td><td class="r"><b>{{ $attendee->getTicketNo() }}</b></td></tr>
    <tr><td class="lbl">Event</td><td class="r"><b>{{ $event->title }}</b></td></tr>
    <tr><td class="lbl">Date</td><td class="r">{{ $event->start_date->format('d M Y') }}</td></tr>
    <tr><td class="lbl">Venue</td><td class="r">{{ $event->venue ?? '—' }}</td></tr>
  </table>

  <div class="ruled" style="border-top:1px dashed #000;border-bottom:1px dashed #000;padding:2px 0;margin:5px 0;"></div>

  <table class="det">
    <tr><td class="lbl">Attendee</td><td class="val">{{ $attendee->name }}</td></tr>
    <tr><td class="lbl">Fellowship</td><td class="val">{{ $attendee->fellowship ?: '—' }}</td></tr>
    <tr><td class="lbl">Coming From</td><td class="val">{{ $attendee->getRegionLabel() }}</td></tr>
    <tr><td class="lbl">Phone</td><td class="val">{{ $attendee->phone ?: '—' }}</td></tr>
    <tr><td class="lbl">Issued</td><td class="val">{{ now()->format('d M Y H:i') }}</td></tr>
  </table>

  <div class="amt">TZS {{ number_format($attendee->amount_paid, 0) }} PAID</div>

  @if($qr)
  <div class="qr"><img src="{{ $qr }}" alt="QR"></div>
  @endif
  <div class="barcode">*{{ $attendee->getTicketNo() }}*</div>

  <div class="admit">ADMIT ONE · {{ $event->start_date->format('d M Y') }}</div>

  <div class="foot">
    Present this ticket with a valid ID at the open gate.<br>
    Open Gate Camp Season 3 {{ $event->start_date->format('Y') }}
  </div>
</body>
</html>
