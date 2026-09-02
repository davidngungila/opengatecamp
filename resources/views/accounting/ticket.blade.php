<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  *{box-sizing:border-box;}
  body{font-family:courier;color:#000;margin:0;padding:0;font-size:10px;}
  .center{text-align:center;}
  .logo{text-align:center;margin-bottom:4px;}
  .logo img{width:34mm;height:34mm;}
  .org{font-size:13px;font-weight:bold;text-transform:uppercase;letter-spacing:.5px;text-align:center;}
  .event{font-size:11px;font-weight:bold;text-align:center;margin-top:2px;}
  .title{font-size:14px;font-weight:bold;text-align:center;letter-spacing:3px;margin:6px 0;border-top:1px dashed #000;border-bottom:1px dashed #000;padding:3px 0;}
  .det{width:100%;font-size:10px;border-collapse:collapse;}
  .det td{padding:2px 0;border-bottom:1px dotted #ddd;}
  .det td.lbl{color:#444;width:38%;}
  .det td.val{font-weight:bold;}
  .amt{text-align:center;font-size:12px;font-weight:bold;margin:8px 0;}
  .qr{text-align:center;margin:8px 0 4px;}
  .qr img{width:52px;height:52px;}
  .barcode{text-align:center;font-size:9px;letter-spacing:2px;font-weight:bold;margin-top:3px;}
  .foot{margin-top:6px;text-align:center;font-size:8px;line-height:1.4;border-top:1px dashed #000;padding-top:5px;}
  .admit{text-align:center;font-size:11px;font-weight:bold;margin-top:4px;background:#000;color:#fff;padding:2px 0;}
</style>
</head>
<body>
  @if(!empty($logoPath) && file_exists($logoPath))
  <div class="logo"><img src="{{ $logoPath }}" alt="{{ $org }}"></div>
  @endif
  <div class="center org">{{ $org }}</div>
  <div class="event">{{ $event->title }}</div>
  <div class="center" style="font-size:9px">{{ $event->venue ?? '' }} · {{ $event->start_date->format('d M Y') }}</div>

  <div class="title">TICKET</div>

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
    Open Gate Summer Camp {{ $event->start_date->format('Y') }}
  </div>
</body>
</html>