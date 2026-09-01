<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  *{box-sizing:border-box;}
  body{font-family:DejaVu Sans,sans-serif;color:#0B1F3A;margin:0;padding:0;}
  .head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #0B1F3A;padding-bottom:14px;}
  .brand{display:flex;align-items:center;gap:12px;}
  .brand .mark{width:46px;height:46px;border-radius:10px;background:#0B1F3A;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:15px;}
  h1{margin:0;font-size:17px;letter-spacing:.3px;}
  .org-sub{font-size:11px;color:#5B6B7F;margin-top:2px;}
  .meta{text-align:right;font-size:11px;color:#5B6B7F;}
  .meta strong{display:block;color:#0B1F3A;font-size:13px;}
  .title{text-align:center;margin:22px 0 4px;}
  .title h2{margin:0;font-size:22px;color:#2563EB;letter-spacing:4px;}
  .title p{margin:4px 0 0;font-size:11px;color:#5B6B7F;}
  .invoice{display:flex;justify-content:space-between;margin:18px 0;padding:14px 16px;border:1px solid #E3E8F0;border-radius:8px;}
  .invoice .lbl{font-size:10px;color:#5B6B7F;margin-bottom:3px;}
  .invoice .val{font-weight:700;font-size:13px;}
  .ref-field{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px;}
  table{width:100%;border-collapse:collapse;margin-top:8px;}
  th{background:#0B1F3A;color:#fff;font-size:10.5px;padding:9px 10px;text-align:left;letter-spacing:.3px;}
  td{padding:9px 10px;border-bottom:1px solid #F0F2F6;font-size:12px;vertical-align:top;}
  td.amt{text-align:right;font-weight:700;}
  .money tr:last-child td{border-bottom:none;}
  .money td.amt{font-size:13px;padding:11px 10px;}
  .money .grand{background:#F3F6FC;}
  .grand td{font-weight:800;font-size:13px;}
  .foot{margin-top:34px;font-size:10.5px;color:#5B6B7F;text-align:center;line-height:1.5;border-top:2px solid #E3E8F0;padding-top:12px;}
  .amount-word{margin-top:14px;font-size:12.5px;color:#0B1F3A;}
  .amount-word b{color:#2563EB;}
  .status{display:inline-block;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:.5px;background:#E7F6EC;color:#15803D;}
</style>
</head>
<body>
  <div class="head">
    <div class="brand">
      <div class="mark">{{ collect(explode(' ', $org))->map(fn($w)=>mb_substr($w,0,1))->take(2)->implode('') }}</div>
      <div>
        <h1>{{ $org }}</h1>
        <div class="org-sub">Official Payment Receipt</div>
      </div>
    </div>
    <div class="meta">
      <strong>{{ $receiptNo }}</strong>
      Date: {{ $entry->entry_date->format('d F Y') }}
    </div>
  </div>

  <div class="title">
    <h2>OFFICIAL RECEIPT</h2>
    <p>Record of transaction — this receipt confirms the transaction recorded below</p>
  </div>

  <div class="invoice ref-field">
    <div><div class="lbl">Reference</div><div class="val">{{ $reference }}</div></div>
    <div><div class="lbl">Entry No</div><div class="val">{{ $entry->entry_no }}</div></div>
    <div><div class="lbl">Entry Date</div><div class="val">{{ $entry->entry_date->format('d M Y') }}</div></div>
    <div><div class="lbl">Status</div><div class="val"><span class="status">POSTED</span></div></div>
  </div>

  <table class="money">
    <thead>
      <tr><th style="width:70%">Transaction / Account</th><th style="width:30%">Amount (TZS)</th></tr>
    </thead>
    <tbody>
      @if($moneyIn && $moneyInLines->count())
        @foreach($moneyInLines as $line)
        <tr>
          <td>{{ $line['label'] }}</td>
          <td class="amt">{{ number_format($line['amount'], 0) }}</td>
        </tr>
        @endforeach
        <tr>
          <td class="detail" style="color:#5B6B7F;font-weight:400;border-bottom:none;"></td>
          <td class="detail" style="border-bottom:none;"></td>
        </tr>
      @else
        @foreach($lines as $line)
        <tr>
          <td>
            <div style="font-weight:700">{{ $line['description'] }}</div>
            <div style="font-size:10.5px;color:#5B6B7F">{{ $line['code'] }} — {{ $line['account'] }}</div>
          </td>
          <td class="amt">{{ $line['debit'] > 0 ? number_format($line['debit'], 0) : number_format($line['credit'], 0) }}</td>
        </tr>
        @endforeach
      @endif
      <tr class="grand">
        <td>Total Amount</td>
        <td class="amt">TZS {{ number_format($amount, 0) }}</td>
      </tr>
    </tbody>
  </table>

  @if($entry->description)
  <div class="amount-word"><b>Memo:</b> {{ $entry->description }}</div>
  @endif

  <div class="foot">
    Thank you for your support of {{ $org }}.<br>
    This receipt was generated electronically and is valid without a signature. Receive dated {{ $entry->entry_date->format('d F Y') }}.
  </div>
</body>
</html>