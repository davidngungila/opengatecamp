<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  @font-face { font-family: Manrope; src: url("Manrope-Regular.ttf"); font-weight: normal; font-style: normal; }
  @font-face { font-family: Manrope; src: url("Manrope-Bold.ttf"); font-weight: bold; font-style: normal; }
  @font-face { font-family: Manrope; src: url("Manrope-ExtraBold.ttf"); font-weight: 800; font-style: normal; }
  body {
    font-family: Manrope, Arial, sans-serif;
    font-size: 10px;
    color: #000;
    margin: 0;
    padding: 0;
  }
  .center { text-align: center; }
  .org { font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
  .org-sub { font-size: 9.5px; letter-spacing: 2px; font-weight: 800; }
  .org-line { font-size: 9.5px; letter-spacing: 1px; font-weight: 800; }
  .org-tag { font-size: 11.5px; font-weight: 800; letter-spacing: 2px; margin-bottom: 2px; }
  .logo { text-align: center; margin-bottom: 4px; }
  .logo img { width: 34mm; height: 34mm; }
  .title { font-size: 13px; font-weight: 800; text-align: center; letter-spacing: 3px; margin: 6px 0 2px; text-transform: uppercase; }
  .ruled { border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 2px 0; margin: 5px 0; }
  table.head { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 2px; }
  table.head td { padding: 1px 0; }
  table.head td.lbl { color: #444; }
  table.head td.r { text-align: right; font-weight: bold; }
  table.lines { width: 100%; border-collapse: collapse; font-size: 10px; }
  table.lines th { border-bottom: 1px dashed #000; font-size: 9px; text-transform: uppercase; text-align: left; padding: 2px 0; }
  table.lines th.r { text-align: right; }
  table.lines td { padding: 2px 0; vertical-align: top; }
  table.lines td.r { text-align: right; white-space: nowrap; }
  tr.total-row td { border-top: 1px dashed #000; font-weight: bold; font-size: 11px; padding-top: 3px; }
  .nr { font-size: 8px; color: #444; }
  td.r { text-align: right; }
  .barcode { text-align: center; font-size: 8px; letter-spacing: 2px; margin-top: 2px; font-weight: bold; }
  .foot { margin-top: 6px; text-align: center; font-size: 8px; line-height: 1.4; border-top: 1px dashed #000; padding-top: 4px; }
  .note { font-size: 8px; color: #444; margin-top: 4px; }
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

  <div class="title">RECEIPT</div>

  <table class="head" cellpadding="0" cellspacing="0">
    <tr><td class="lbl">Receipt No</td><td class="r"><b>{{ $receiptNo }}</b></td></tr>
    <tr><td class="lbl">Payment Date</td><td class="r">{{ $payment->pay_date?->format('d M Y') }}</td></tr>
    <tr><td class="lbl">Received From</td><td class="r">{{ $pledge?->name ?? '—' }}</td></tr>
    <tr><td class="lbl">Pledge No</td><td class="r">{{ $pledge?->pledge_no ?? '—' }}</td></tr>
    <tr><td class="lbl">Method</td><td class="r">{{ ucfirst($payment->method ?? '—') }}</td></tr>
    <tr><td class="lbl">Reference</td><td class="r">{{ $payment->reference ?? '—' }}</td></tr>
  </table>

  <div class="ruled"></div>

  <table class="lines" cellpadding="0" cellspacing="0">
    <thead>
      <tr><th>Description</th><th class="r">Amount (TZS)</th></tr>
    </thead>
    <tbody>
      <tr>
        <td>Pledge payment — {{ $pledge?->event?->title ?? 'Open Gate Camp' }}
          @if($pledge?->due_date)<div class="nr">Due {{ $pledge->due_date->format('d M Y') }}</div>@endif
        </td>
        <td class="r">{{ number_format((float) $payment->amount, 0) }}</td>
      </tr>
      <tr class="total-row"><td><b>TOTAL</b></td><td class="r"><b>TZS {{ number_format((float) $payment->amount, 0) }}</b></td></tr>
    </tbody>
  </table>

  @if($pledge)
  <div class="note">
    Pledge TZS {{ number_format((float) $pledge->amount, 0) }} · Paid TZS {{ number_format((float) $pledge->paid_amount, 0) }}
    @if((float) $pledge->getRemainingAttribute() > 0)· Balance TZS {{ number_format((float) $pledge->getRemainingAttribute(), 0) }}@endif
  </div>
  @endif

  <div class="barcode">*{{ $receiptNo }}*</div>

  <div class="foot">
    Thank you for your support of {{ $org }}.<br>
    Generated electronically — valid without a signature.
  </div>
</body>
</html>