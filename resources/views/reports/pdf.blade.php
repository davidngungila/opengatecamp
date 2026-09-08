<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  *{box-sizing:border-box;}
  body{font-family:Manrope, Arial, sans-serif; color:#10233F; margin:0; padding:0; font-size:9.5px;}
  @page{margin:0;}
  .page{position:relative; width:100%; min-height:297mm;}
  .content{padding:0;}

  /* Logo */
  .logo-area{text-align:center; padding-top:14px; padding-bottom:8px;}
  .logo-area img{width:60px; height:60px; object-fit:contain;}

  /* Org header */
  .organization{text-align:center; padding:0 30px 10px;}
  .organization .main{font-size:17px; font-weight:800; letter-spacing:.6px; color:#000; line-height:1.15; text-transform:uppercase;}
  .organization .sub{margin-top:3px; font-size:11px; font-weight:800; letter-spacing:1px; color:#0758B8; text-transform:uppercase;}

  /* Blue campaign header */
  .campaign-header{width:100%; background:#0758B8; color:#fff; text-align:center; padding:12px 24px 13px;}
  .campaign-header .small{font-size:11px; font-weight:800; letter-spacing:2px; text-transform:uppercase;}
  .campaign-header .large{margin-top:2px; font-size:22px; line-height:1.05; font-weight:900; text-transform:uppercase;}
  .campaign-header .line{margin-top:6px; font-size:10px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:#F2C300;}

  /* Report title block */
  .report-head{background:#EAF4FF; border-top:2px solid #0758B8; border-bottom:2px solid #0758B8; padding:10px 26px; text-align:center;}
  .report-head .title{font-size:18px; font-weight:900; text-transform:uppercase; letter-spacing:2px; color:#0758B8;}
  .report-head .subtitle{margin-top:3px; font-size:10.5px; font-weight:700; color:#10233F;}
  .report-head .meta{margin-top:5px; font-size:8.5px; color:#475569;}

  /* Filters row */
  .filters{display:block; margin:8px 26px 0; font-size:8.5px; color:#475569;}
  .filters b{color:#0758B8;}

  /* Totals strip */
  .totals{width:auto; margin:10px 26px 0; }
  .totals table{width:100%; border-collapse:collapse;}
  .totals td{background:#EAF4FF; border:1px solid #0758B8; border-radius:0; padding:5px 8px; text-align:center;}
  .totals .lbl{display:block; font-size:7.5px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.5px;}
  .totals .val{display:block; margin-top:1px; font-size:13px; font-weight:900; color:#0758B8;}

  /* Table */
  .table-wrap{margin:10px 18px 0;}
  table.data{width:100%; border-collapse:collapse; font-size:8.5px;}
  table.data thead th{background:#0758B8; color:#fff; font-weight:800; text-transform:uppercase; letter-spacing:.4px; padding:5px 6px; text-align:left; font-size:7.5px;}
  table.data tbody td{padding:4px 6px; border-bottom:1px solid #dbe6f3; vertical-align:top;}
  table.data tbody tr:nth-child(even){background:#F6FAFF;}
  table.data .num{text-align:right;}
  table.data tbody td.num{white-space:nowrap;}

  /* Footer */
  .footer{position:absolute; bottom:0; left:0; right:0; background:#0758B8; color:#fff; text-align:center; padding:7px 24px;}
  .footer .contact{font-size:9px; font-weight:800; letter-spacing:1px; text-transform:uppercase;}
  .footer .bottom{margin-top:2px; font-size:7.5px;}
</style>
</head>
<body>
<div class="page">
  <div class="content">

    <div class="logo-area">
      @if(!empty($logoPath) && file_exists($logoPath))
        <img src="{{ $logoPath }}" alt="logo">
      @endif
    </div>

    <div class="organization">
      <div class="main">{{ $orgMain }}</div>
      <div class="sub">{{ $orgSub }}</div>
    </div>

    <div class="campaign-header">
      <div class="small">OPEN GATE CAMP</div>
      <div class="large">{{ $campaign }}</div>
      <div class="line">{{ $orgLine }}</div>
    </div>

    <div class="report-head">
      <div class="title">{{ $meta['title'] }}</div>
      @if(!empty($meta['subtitle']))<div class="subtitle">{{ $meta['subtitle'] }}</div>@endif
      <div class="meta">Generated {{ $generatedAt }} · OpenGate Camp Connect</div>
    </div>

    @if(!empty($meta['filters']))
    <div class="filters">
      <b>Filters:</b>
      @forelse($meta['filters'] as $label => $value)
        <span style="margin-right:12px">{{ $label }}: <b>{{ $value }}</b></span>
      @empty
        <span>All records</span>
      @endforelse
    </div>
    @endif

    @if(!empty($totals))
    <div class="totals">
      <table>
        <tr>
          @foreach($totals as $t)
          <td style="width:{{ 100 / count($totals) }}%">
            <span class="lbl">{{ $t['label'] }}</span>
            <span class="val">{{ $t['value'] }}</span>
          </td>
          @endforeach
        </tr>
      </table>
    </div>
    @endif

    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr>
            @foreach($columns as $col)
            <th @if(!empty($col['align'])) style="text-align:{{ $col['align'] }}" @endif>{{ $col['label'] }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
          <tr>
            @foreach($columns as $col)
            @php $v = $r[$col['key'] ?? $col['label']] ?? ''; @endphp
            <td @if(!empty($col['align'])) class="num" @endif>{{ $v }}</td>
            @endforeach
          </tr>
          @empty
          <tr><td colspan="{{ count($columns) }}" style="text-align:center;padding:14px">No records found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>

  <div class="footer">
    <div class="contact">{{ $campaign }}</div>
    <div class="bottom">OpenGate Camp Connect · Generated {{ $generatedAt }}</div>
  </div>
</div>
</body>
</html>
