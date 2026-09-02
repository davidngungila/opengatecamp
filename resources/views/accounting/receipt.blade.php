<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  *{box-sizing:border-box;}
  body{font-family:DejaVu Sans Mono,DejaVu Sans,monospace;color:#000;margin:0;padding:4mm 2mm;width:76mm;}
  .center{text-align:center;}
  .org{font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;}
  .org-sub{font-size:9px;letter-spacing:2px;}
  .ruled{border-top:1px dashed #000;border-bottom:1px dashed #000;padding:2px 0;margin:6px 0;}
  .title{font-size:14px;font-weight:800;text-align:center;letter-spacing:3px;margin:8px 0 2px;}
  .head{margin-bottom:6px;font-size:11px;}
  .head td{padding:1px 0;}
  .head .lbl{color:#444;}
  table.lines{width:100%;border-collapse:collapse;font-size:10.5px;}
  table.lines th{border-bottom:1px dashed #000;font-size:9px;text-transform:uppercase;text-align:left;}
  table.lines td{padding:2px 0;vertical-align:top;border-bottom:none;}
  tr.total-row td{border-top:1px dashed #000;font-weight:800;font-size:12px;padding-top:3px;}
  td.r{text-align:right;}
  .nr{font-size:10px;color:#000;}
  .qr{text-align:center;margin:8px 0 4px;}
  .qr img{width:58px;height:58px;image-rendering:pixelated;}
  .foot{margin-top:6px;text-align:center;font-size:8.5px;line-height:1.4;border-top:1px dashed #000;padding-top:5px;}
  .barcode{text-align:center;font-size:8px;letter-spacing:1px;margin-top:4px;}
</style>
</head>
<body>
  <div class="center org">{{ $org }}</div>
  <div class="center org-sub">OFFICIAL RECEIPT</div>

  <div class="title">RECEIPT</div>

  <table class="head">
    <tr><td class="lbl">Receipt No</td><td class="r"><b>{{ $receiptNo }}</b></td></tr>
    <tr><td class="lbl">Entry No</td><td class="r">{{ $entry->entry_no }}</td></tr>
    <tr><td class="lbl">Date</td><td class="r">{{ $entry->entry_date->format('d M Y') }}</td></tr>
    <tr><td class="lbl">Time</td><td class="r">{{ $entry->created_at ? $entry->created_at->format('H:i') : $entry->entry_date->format('H:i') }}</td></tr>
    <tr><td class="lbl">Reference</td><td class="r">{{ $reference }}</td></tr>
    <tr><td class="lbl">Status</td><td class="r">POSTED</td></tr>
  </table>

  <div class="ruled"></div>

  <table class="lines">
    <thead><tr><th>Description</th><th class="r">Amount (TZS)</th></tr></thead>
    <tbody>
      @if($moneyIn && $moneyInLines->count())
        @foreach($moneyInLines as $line)
        <tr>
          <td>{{ $line['label'] }}</td>
          <td class="r">{{ number_format($line['amount'], 0) }}</td>
        </tr>
        @endforeach
      @else
        @foreach($lines as $line)
        <tr>
          <td>{{ $line['description'] }}<div class="nr">{{ $line['code'] }}</div></td>
          <td class="r">{{ $line['debit'] > 0 ? number_format($line['debit'], 0) : number_format($line['credit'], 0) }}</td>
        </tr>
        @endforeach
      @endif
      <tr class="total-row">
        <td>TOTAL</td>
        <td class="r">TZS {{ number_format($amount, 0) }}</td>
      </tr>
    </tbody>
  </table>

  @if($qr)
  <div class="qr"><img src="{{ $qr }}" alt="QR"></div>
  @endif
  <div class="barcode">*{{ $receiptNo }}*</div>

  <div class="foot">
    Thank you for your support of {{ $org }}.<br>
    Generated electronically — valid without a signature.
  </div>
</body>
</html>