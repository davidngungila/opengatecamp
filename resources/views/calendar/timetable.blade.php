<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Timetable — OpenGate Camp Connect</title>
<style>
  *{box-sizing:border-box;}
  body{font-family:Manrope,ui-sans-serif,Arial,sans-serif;color:#0f172a;margin:0;padding:24px;background:#f1f5f9;}
  .paper{max-width:900px;margin:0 auto;background:#fff;padding:32px;border-radius:14px;box-shadow:0 4px 24px rgba(15,23,42,.08);}
  .head{display:flex;align-items:center;justify-content:space-between;border-bottom:3px solid #2563eb;padding-bottom:14px;margin-bottom:18px;gap:16px;flex-wrap:wrap;}
  .head h1{font-size:20px;margin:0;color:#1e293b;}
  .head .sub{font-size:12.5px;color:#64748b;margin-top:4px;}
  .logo{font-weight:800;color:#2563eb;letter-spacing:.5px;font-size:14px;text-transform:uppercase;}
  .controls{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:18px;padding:12px;background:#eef2ff;border:1px solid #c7d2fe;border-radius:10px;}
  .controls form{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0;}
  .controls label{font-size:12px;font-weight:700;color:#3730a3;}
  .controls select,.controls input{font:inherit;font-size:13px;padding:6px 8px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#0f172a;}
  .btn{font:inherit;font-size:13px;font-weight:600;padding:7px 14px;border:none;border-radius:8px;cursor:pointer;background:#2563eb;color:#fff;}
  .btn.ghost{background:#fff;color:#2563eb;border:1px solid #c7d2fe;}
  .actions{text-align:right;margin-bottom:14px;}
  .day{border:1px solid #e2e8f0;border-radius:12px;margin:0 0 16px;overflow:hidden;}
  .day-head{display:flex;align-items:center;justify-content:space-between;background:#f8fafc;padding:10px 14px;border-bottom:1px solid #e2e8f0;}
  .day-head b{font-size:14px;color:#0f172a;}
  .day-head .n{font-size:12px;color:#2563eb;font-weight:700;}
  table{width:100%;border-collapse:collapse;font-size:13px;}
  th,td{text-align:left;padding:9px 14px;border-bottom:1px solid #eef2f7;vertical-align:top;}
  thead th{background:#f8fafc;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#64748b;}
  tbody tr:last-child td{border-bottom:none;}
  .time{white-space:nowrap;font-weight:700;color:#2563eb;}
  .empty{text-align:center;color:#94a3b8;padding:30px;font-size:13px;}
  .foot{margin-top:20px;font-size:11px;color:#94a3b8;text-align:center;}
  @media print{
    body{background:#fff;padding:0;}
    .paper{box-shadow:none;border-radius:0;max-width:100%;padding:8mm;}
    .controls,.actions{display:none !important;}
    .day{break-inside:avoid;page-break-inside:avoid;}
  }
</style>
</head>
<body>
<div class="paper">
  <div class="head">
    <div>
      <div class="logo">OpenGate Camp Connect</div>
      <h1>
        @if($scope==='day') Timetable · {{ \Carbon\Carbon::parse(array_key_first($groups) ?? now())->format('l, d M Y') }}
        @elseif($scope==='programme') Camp Programme Timetable
        @else Timetable · {{ $monthDate->format('F Y') }}@endif
      </h1>
      <div class="sub">Activities planned per day with hours · {{ count($groups) }} day(s)</div>
    </div>
  </div>

  <div class="actions"><button class="btn" onclick="window.print()"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:6px"><path d="M8 7H3a1 1 0 00-1 1v13a1 1 0 001 1h13a1 1 0 001-1v-5"/><path d="M21 3H8a1 1 0 00-1 1v13a1 1 0 001 1h13a1 1 0 001-1V4a1 1 0 00-1-1z"/><path d="M12 8v4l3 2"/></svg>Print / Save PDF</button></div>

  <div class="controls">
    <form method="GET" action="{{ route('calendar.timetable') }}">
      <label>Scope</label>
      <select name="scope" onchange="this.form.submit()">
        <option value="month" {{ $scope==='month'?'selected':'' }}>Whole month</option>
        <option value="day" {{ $scope==='day'?'selected':'' }}>Single day</option>
        <option value="programme" {{ $scope==='programme'?'selected':'' }}>Whole programme</option>
      </select>
      <label>Month</label>
      <input type="month" name="month" value="{{ $monthDate->format('Y-m') }}" onchange="this.form.submit()">
      <label>Date</label>
      <input type="date" name="date" value="{{ array_key_first($groups) ?? $today->format('Y-m-d') }}" onchange="this.form.submit()">
    </form>
  </div>

  @forelse($groups as $dateKey => $daySessions)
  <div class="day">
    <div class="day-head">
      <b>{{ \Carbon\Carbon::parse($dateKey)->format('l, d M Y') }}</b>
      <span class="n">{{ count($daySessions) }} activities</span>
    </div>
    @if(count($daySessions))
    <table>
      <thead><tr><th style="width:88px">Time</th><th>Activity</th><th>Venue</th><th>Speaker / Facilitator</th></tr></thead>
      <tbody>
        @foreach($daySessions as $s)
        <tr>
          <td class="time">{{ $s->start_time ? \Carbon\Carbon::parse($s->start_time)->format('H:i') : '—' }}{{ $s->end_time ? '–'.substr($s->end_time,0,5) : '' }}</td>
          <td>
            <b>{{ $s->title }}</b>
            @if($s->category)<div style="font-size:11px;color:#2563eb;font-weight:600;margin-top:2px">{{ $s->category }}</div>@endif
            @if($s->description)<div style="font-size:12px;color:#64748b;margin-top:2px">{{ $s->description }}</div>@endif
          </td>
          <td>{{ $s->venue ?? '—' }}</td>
          <td>{{ trim(($s->speaker??'') . ($s->facilitator ? ' · '.$s->facilitator : '')) ?: '—' }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @else
    <div class="empty">No activities planned for this day.</div>
    @endif
  </div>
  @empty
  <div class="empty">No activities planned in this range yet. Go to the Calendar and use “+ Plan Activity”.</div>
  @endforelse

  <div class="foot">Generated by OpenGate Camp Connect · {{ $today->format('d M Y H:i') }}</div>
</div>
</body>
</html>