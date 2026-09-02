@extends('layouts.app')

@section('title', 'Cash & Bank — Open Gate Camp Mission')
@section('crumb', 'Finance / Financial Accounting / Cash & Bank')
@section('page_title', 'Cash & Bank Management')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Cash &amp; Bank Management</h2><div class="sub">@if($fy) Period: {{ $fy->name }}. @else All periods. @endif Live cash position, inflows/outflows and account-level movement.</div></div>
  </div>

  {{-- KPI grid --}}
  <div class="kpi-grid" style="margin-bottom:22px">
    <div class="kpi-card" style="border:2px solid var(--blue-accent)">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--blue-light);color:var(--blue-accent)">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
      </div></div>
      <p class="kpi-label">Total Cash Position</p>
      <h3 class="kpi-value">TZS {{ number_format($totalCash) }}</h3>
      <span class="kpi-sub">{{ $balances->count() }} money {{ Str::plural('account', $balances->count()) }}</span>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--success-bg);color:var(--success)">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
      </div></div>
      <p class="kpi-label">Inflows</p>
      <h3 class="kpi-value">TZS {{ number_format($inflows) }}</h3>
      <span class="kpi-sub">Money received this {{ $fy ? 'financial year' : 'period' }}</span>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--danger-bg);color:var(--danger)">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8M14 7h7v7"/></svg>
      </div></div>
      <p class="kpi-label">Outflows</p>
      <h3 class="kpi-value">TZS {{ number_format($outflows) }}</h3>
      <span class="kpi-sub">Money spent this {{ $fy ? 'financial year' : 'period' }}</span>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:{{ $netMovement >= 0 ? 'var(--info-bg)' : 'var(--warning-bg)' }};color:{{ $netMovement >= 0 ? 'var(--info)' : 'var(--warning)' }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
      </div></div>
      <p class="kpi-label">Net Movement</p>
      <h3 class="kpi-value" style="color:{{ $netMovement >= 0 ? 'var(--green-accent)' : 'var(--red)' }}">{{ $netMovement >= 0 ? '+' : '−' }} TZS {{ number_format(abs($netMovement)) }}</h3>
      <span class="kpi-sub">Inflows minus outflows</span>
    </div>
  </div>

  {{-- Account balances row --}}
  <div class="section-head" style="margin-top:6px"><div><h2>Account Balances</h2><div class="sub">Net position per money account for the period.</div></div></div>
  <div class="stat-grid" style="grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));margin-bottom:24px">
    @foreach($balances as $b)
    <div class="glass-card" style="padding:16px 20px;text-align:center">
      <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:6px">{{ $b['account']->code }} — {{ $b['account']->name }}</div>
      <div style="font-size:24px;font-weight:700;color:var(--blue-accent)">TZS {{ number_format($b['balance']) }}</div>
      <div style="font-size:11px;color:var(--text-tertiary);margin-top:8px;display:flex;justify-content:center;gap:14px">
        <span><b style="color:var(--green-accent)">In</b> TZS {{ number_format($b['debit']) }}</span>
        <span><b style="color:var(--red)">Out</b> TZS {{ number_format($b['credit']) }}</span>
      </div>
    </div>
    @endforeach
  </div>

  <div class="two-col" style="grid-template-columns:1.6fr 1fr;align-items:start">
    {{-- Cash flow overview + chart --}}
    <div class="glass-card">
      <h2 style="font-size:14px;margin:0 0 6px">Cash Flow Overview</h2>
      <div class="sub" style="margin-bottom:6px">Monthly inflows vs outflows across money accounts.</div>
      <div class="chart-wrap" style="height:220px"><canvas id="cashChart"
        data-labels='@json(collect($monthly)->pluck("label"))'
        data-in='@json(collect($monthly)->pluck("in"))'
        data-out='@json(collect($monthly)->pluck("out"))'></canvas></div>

      @if(count($monthly))
      <div class="table-scroll" style="margin-top:10px">
        <table class="data-table mini-table">
          <thead><tr><th>Month</th><th style="text-align:right">In</th><th style="text-align:right">Out</th><th style="text-align:right">Net</th></tr></thead>
          <tbody>
            @foreach($monthly as $row)
            <tr>
              <td>{{ $row['label'] }}</td>
              <td style="text-align:right;color:var(--green-accent);font-weight:600">TZS {{ number_format($row['in']) }}</td>
              <td style="text-align:right;color:var(--red);font-weight:600">TZS {{ number_format($row['out']) }}</td>
              <td style="text-align:right;font-weight:700;color:{{ $row['net'] >= 0 ? 'var(--green-accent)' : 'var(--red)' }}">{{ $row['net'] >= 0 ? '+' : '−' }} TZS {{ number_format(abs($row['net'])) }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @else
      <div class="empty-state" style="padding:22px 16px"><p>No cash movements in this period.</p></div>
      @endif
    </div>

    <div style="display:flex;flex-direction:column;gap:16px">
      {{-- Moving filter + latest transactions --}}
      <div class="glass-card">
        <h2 style="font-size:14px;margin:0 0 12px">Cash Movements</h2>
        <form method="GET" action="{{ route('accounting.cash-bank') }}" style="display:flex;gap:8px;margin-bottom:12px">
          <select name="account" style="flex:1;height:36px;border:1px solid var(--border-strong);border-radius:10px;padding:0 10px;font-size:12.5px;background:var(--white);color:var(--text-primary)">
            <option value="">All money accounts</option>
            @foreach($balances as $b)
              <option value="{{ $b['account']->id }}" {{ (string)$activeAccount === (string)$b['account']->id ? 'selected' : '' }}>{{ $b['account']->code }} — {{ $b['account']->name }}</option>
            @endforeach
          </select>
          <button type="submit" class="btn btn-secondary" style="height:36px">Filter</button>
          @if($activeAccount)<a href="{{ route('accounting.cash-bank') }}" class="btn btn-ghost btn-sm" style="height:36px;align-self:center">Clear</a>@endif
        </form>
        @forelse($movements as $m)
        <div class="mini-row">
          <div class="m-ico" style="background:{{ $m->debit > 0 ? 'var(--green-light)' : '#fef2f2' }};color:{{ $m->debit > 0 ? 'var(--green-accent)' : 'var(--red)' }}">
            {{ $m->debit > 0 ? 'IN' : 'OUT' }}
          </div>
          <div class="m-body">
            <p>{{ $m->entry->entry_no }} — {{ Str::limit($m->entry->description, 36) }}</p>
            <span>{{ $m->account->code }} · {{ $m->entry->entry_date->format('d M Y') }}</span>
          </div>
          <span style="font-weight:700;color:{{ $m->debit > 0 ? 'var(--green-accent)' : 'var(--red)' }}">
            {{ $m->debit > 0 ? '+' : '−' }} TZS {{ number_format(max($m->debit, $m->credit)) }}
          </span>
        </div>
        @empty
        <div class="empty-state" style="padding:20px 12px"><p>No movements for this filter.</p></div>
        @endforelse
      </div>

      <div class="glass-card">
        <h2 style="font-size:14px;margin:0 0 12px">Quick Actions</h2>
        <div style="display:flex;flex-direction:column;gap:10px">
          <a href="{{ route('accounting.offerings') }}" class="btn btn-secondary" style="justify-content:center;text-align:center">+ Record Receipt</a>
          <a href="{{ route('accounting.payments') }}" class="btn btn-secondary" style="justify-content:center;text-align:center">+ Record Payment</a>
          <a href="{{ route('accounting.reconciliation') }}" class="btn btn-secondary" style="justify-content:center;text-align:center">Bank Reconciliation</a>
          <a href="{{ route('accounting.ledger') }}" class="btn btn-secondary" style="justify-content:center;text-align:center">View Ledger</a>
          <a href="{{ route('accounting.transactions') }}" class="btn btn-secondary" style="justify-content:center;text-align:center">Transaction History</a>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>try{(function(){
  if(typeof Chart==='undefined') return;
  var cv=document.getElementById('cashChart');
  if(!cv) return;
  var labels=JSON.parse(cv.dataset.labels||'[]');
  var inn=JSON.parse(cv.dataset.in||'[]');
  var out=JSON.parse(cv.dataset.out||'[]');
  if(labels.length===0){ return; }
  new Chart(cv,{type:'bar',data:{labels:labels,datasets:[
    {label:'Inflows',data:inn,backgroundColor:'rgba(22,163,74,.75)',borderRadius:5,barPercentage:.6},
    {label:'Outflows',data:out,backgroundColor:'rgba(239,68,68,.75)',borderRadius:5,barPercentage:.6}
  ]},options:{responsive:true,maintainAspectRatio:false,
    scales:{y:{beginAtZero:true,ticks:{callback:function(v){return v>=1000?(v/1000)+'k':v;}}}},
    plugins:{legend:{labels:{boxWidth:12,boxHeight:12,usePointStyle:true,font:{size:11}}}}
  }});
})();}catch(e){}</script>
@endpush
@endsection