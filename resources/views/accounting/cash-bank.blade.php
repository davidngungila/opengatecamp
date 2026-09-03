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
  <div class="stat-grid acct-bal-grid" style="margin-bottom:24px">
    @foreach($balances as $b)
    <div class="glass-card" style="padding:16px;text-align:center;cursor:pointer" data-view-cash-acct data-id="{{ $b['account']->id }}">
      <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:6px;word-break:break-word">{{ $b['account']->code }} — {{ $b['account']->name }}</div>
      <div style="font-size:clamp(18px,4vw,24px);font-weight:700;color:var(--blue-accent)">TZS {{ number_format($b['balance']) }}</div>
      <div style="font-size:11px;color:var(--text-tertiary);margin-top:8px;display:flex;justify-content:center;gap:14px;flex-wrap:wrap">
        <span><b style="color:var(--green-accent)">In</b> TZS {{ number_format($b['debit']) }}</span>
        <span><b style="color:var(--red)">Out</b> TZS {{ number_format($b['credit']) }}</span>
      </div>
    </div>
    @endforeach
  </div>

  {{-- Cash flow overview --}}
  <div class="glass-card" style="margin-bottom:22px">
    <h2 style="font-size:14px;margin:0 0 6px">Cash Flow Overview</h2>
    <div class="sub" style="margin-bottom:6px">Monthly inflows vs outflows across money accounts.</div>
    <div class="chart-wrap" style="height:clamp(180px,30vw,260px)"><canvas id="cashChart"
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

  {{-- Cash movements --}}
  <div class="glass-card" style="margin-bottom:22px">
    <h2 style="font-size:14px;margin:0 0 12px">Cash Movements</h2>
    <form method="GET" action="{{ route('accounting.cash-bank') }}" class="cash-filter-form">
      <select name="account" class="cash-filter-select">
        <option value="">All money accounts</option>
        @foreach($balances as $b)
          <option value="{{ $b['account']->id }}" {{ (string)$activeAccount === (string)$b['account']->id ? 'selected' : '' }}>{{ $b['account']->code }} — {{ $b['account']->name }}</option>
        @endforeach
      </select>
      <div class="cash-filter-btns">
        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        @if($activeAccount)<a href="{{ route('accounting.cash-bank') }}" class="btn btn-ghost btn-sm">Clear</a>@endif
      </div>
    </form>
    @forelse($movements as $m)
    <div class="mini-row" style="cursor:pointer" data-view-cash-movement data-id="{{ $m->id }}">
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

  {{-- Quick actions --}}
  <div class="glass-card">
    <h2 style="font-size:14px;margin:0 0 12px">Quick Actions</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px">
      <a href="{{ route('accounting.offerings') }}" class="btn btn-secondary btn-sm" style="justify-content:center">+ Receipt</a>
      <a href="{{ route('accounting.payments') }}" class="btn btn-secondary btn-sm" style="justify-content:center">+ Payment</a>
      <a href="{{ route('accounting.reconciliation') }}" class="btn btn-secondary btn-sm" style="justify-content:center">Reconciliation</a>
      <a href="{{ route('accounting.ledger') }}" class="btn btn-secondary btn-sm" style="justify-content:center">Ledger</a>
      <a href="{{ route('accounting.transactions') }}" class="btn btn-secondary btn-sm" style="justify-content:center">Transaction History</a>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="cashAcctDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="cashAcctDrawerTitle">Account Details</h3><p class="badge badge-info badge-dotted">Money Account</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="info-grid">
        <div class="info-row"><span>Code</span><b id="cashAcctDrawerCode">—</b></div>
        <div class="info-row"><span>Name</span><b id="cashAcctDrawerName">—</b></div>
        <div class="info-row"><span>Total Inflows</span><b id="cashAcctDrawerIn" style="color:var(--success)">—</b></div>
        <div class="info-row"><span>Total Outflows</span><b id="cashAcctDrawerOut" style="color:var(--red)">—</b></div>
        <div class="info-row"><span>Net Balance</span><b id="cashAcctDrawerNet" style="color:var(--blue-accent)">—</b></div>
      </div>
      <div class="payments-head" style="margin-top:18px">
        <span>Recent Activity</span><span class="payments-count" id="cashAcctLinesCount">0</span>
      </div>
      <div id="cashAcctLines" class="payments-list"></div>
      <div style="margin-top:14px">
        <a id="cashAcctLedgerLink" href="#" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">View Full Ledger</a>
      </div>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="cashMovDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Movement Details</h3><p id="cashMovDrawerEntry" class="badge badge-neutral badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="info-grid">
        <div class="info-row"><span>Entry No</span><b id="cashMovDrawerEntryNo">—</b></div>
        <div class="info-row"><span>Date</span><b id="cashMovDrawerDate">—</b></div>
        <div class="info-row"><span>Description</span><b id="cashMovDrawerDesc" style="white-space:normal">—</b></div>
        <div class="info-row"><span>Reference</span><b id="cashMovDrawerRef">—</b></div>
        <div class="info-row"><span>Account</span><b id="cashMovDrawerAccount">—</b></div>
        <div class="info-row"><span>Amount</span><b id="cashMovDrawerAmount">—</b></div>
      </div>
      <div class="payments-head" style="margin-top:18px">
        <span>All Journal Lines</span><span class="payments-count" id="cashMovLinesCount">0</span>
      </div>
      <div id="cashMovLines" class="payments-list"></div>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('[data-view-cash-acct]').forEach(function(el){
    el.addEventListener('click', function(){
      var id = el.dataset.id;
      fetch('{{ url("/accounting/api/accounts") }}/' + id)
        .then(function(r){ return r.json(); })
        .then(function(d){
          var a = d.account;
          document.getElementById('cashAcctDrawerTitle').textContent = a.code + ' — ' + a.name;
          document.getElementById('cashAcctDrawerCode').textContent = a.code;
          document.getElementById('cashAcctDrawerName').textContent = a.name;
          document.getElementById('cashAcctDrawerIn').textContent = 'TZS ' + d.debit.toLocaleString();
          document.getElementById('cashAcctDrawerOut').textContent = 'TZS ' + d.credit.toLocaleString();
          document.getElementById('cashAcctDrawerNet').textContent = 'TZS ' + Math.abs(d.net).toLocaleString();

          document.getElementById('cashAcctLinesCount').textContent = d.recentLines.length;
          var list = document.getElementById('cashAcctLines');
          list.innerHTML = '';
          if(d.recentLines.length === 0){
            list.innerHTML = '<div class="pay-empty">No recent activity</div>';
          } else {
            d.recentLines.forEach(function(l){
              var isDr = Number(l.debit) > 0;
              var item = document.createElement('div');
              item.className = 'pay-item';
              item.innerHTML =
                '<div class="pay-ico" style="background:' + (isDr ? 'var(--success-bg)' : 'var(--danger-bg)') + ';color:' + (isDr ? 'var(--success)' : 'var(--red)') + '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>' +
                '<div class="pay-main"><div class="pm-name">' + l.entry_no + '</div><div class="pm-sub">' + l.date + ' · ' + (l.description || '') + '</div></div>' +
                '<div class="pay-amt" style="text-align:right"><div>' + (isDr ? '+ TZS ' + Number(l.debit).toLocaleString() : '') + '</div><div style="color:var(--red);font-size:11px">' + (!isDr && Number(l.credit) > 0 ? '− TZS ' + Number(l.credit).toLocaleString() : '') + '</div></div>';
              list.appendChild(item);
            });
          }

          document.getElementById('cashAcctLedgerLink').href = '{{ url("/accounting/ledger") }}?account=' + a.id;
          openDrawerById('cashAcctDrawer');
        });
    });
  });

  document.querySelectorAll('[data-view-cash-movement]').forEach(function(el){
    el.addEventListener('click', function(){
      var id = el.dataset.id;
      fetch('{{ url("/accounting/api/cash-movements") }}/' + id)
        .then(function(r){ return r.json(); })
        .then(function(d){
          var isDr = Number(d.debit) > 0;
          document.getElementById('cashMovDrawerEntry').textContent = d.entry_no;
          document.getElementById('cashMovDrawerEntryNo').textContent = d.entry_no;
          document.getElementById('cashMovDrawerDate').textContent = d.entry_date;
          document.getElementById('cashMovDrawerDesc').textContent = d.description || '—';
          document.getElementById('cashMovDrawerRef').textContent = d.reference || '—';
          document.getElementById('cashMovDrawerAccount').textContent = d.account.code + ' — ' + d.account.name;
          document.getElementById('cashMovDrawerAmount').textContent = (isDr ? '+ ' : '− ') + 'TZS ' + Number(isDr ? d.debit : d.credit).toLocaleString();
          document.getElementById('cashMovDrawerAmount').style.color = isDr ? 'var(--success)' : 'var(--red)';

          document.getElementById('cashMovLinesCount').textContent = d.allLines.length;
          var list = document.getElementById('cashMovLines');
          list.innerHTML = '';
          d.allLines.forEach(function(l){
            var lIsDr = Number(l.debit) > 0;
            var item = document.createElement('div');
            item.className = 'pay-item';
            item.innerHTML =
              '<div class="pay-ico" style="background:' + (lIsDr ? 'var(--success-bg)' : 'var(--danger-bg)') + ';color:' + (lIsDr ? 'var(--success)' : 'var(--red)') + '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>' +
              '<div class="pay-main"><div class="pm-name">' + (l.code ? l.code + ' — ' + l.account : '—') + '</div></div>' +
              '<div class="pay-amt" style="text-align:right"><div>' + (lIsDr ? 'Dr TZS ' + Number(l.debit).toLocaleString() : '') + '</div><div style="color:var(--red);font-size:11px">' + (!lIsDr && Number(l.credit) > 0 ? 'Cr TZS ' + Number(l.credit).toLocaleString() : '') + '</div></div>';
            list.appendChild(item);
          });
          openDrawerById('cashMovDetailDrawer');
        });
    });
  });
});
</script>
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
