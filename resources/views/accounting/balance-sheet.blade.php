@extends('layouts.app')

@section('title', 'Balance Sheet — Open Gate Camp Mission')
@section('crumb', 'Finance / Financial Accounting / Balance Sheet')
@section('page_title', 'Balance Sheet')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Balance Sheet</h2><div class="sub">As of {{ $asOf }}. @if($fy) {{ $fy->name }}. @endif</div></div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">Print</button>
  </div>

  <div class="two-col" style="grid-template-columns:1fr 1fr;margin-bottom:0">
    <div class="solid-card">
      <h2 style="font-size:15px;margin:0 0 12px">Assets</h2>
      @forelse($assets['accounts'] as $r)
        <div class="info-row" style="cursor:pointer" data-view-bs-account data-id="{{ $r['account']->id }}" data-type="asset"><span>{{ $r['account']->code }} — {{ $r['account']->name }}</span><b>TZS {{ number_format($r['amount'], 2) }}</b></div>
      @empty
        <p class="text-muted" style="font-size:13px">No asset accounts with activity.</p>
      @endforelse
      <div class="info-row" style="border-top:2px solid var(--border-strong);margin-top:8px"><span style="font-weight:800">Total Assets</span><b>TZS {{ number_format($totals['assets'], 2) }}</b></div>
    </div>

    <div>
      <div class="solid-card" style="margin-bottom:18px">
        <h2 style="font-size:15px;margin:0 0 12px">Liabilities</h2>
        @forelse($liabilities['accounts'] as $r)
          <div class="info-row" style="cursor:pointer" data-view-bs-account data-id="{{ $r['account']->id }}" data-type="liability"><span>{{ $r['account']->code }} — {{ $r['account']->name }}</span><b>TZS {{ number_format($r['amount'], 2) }}</b></div>
        @empty
          <p class="text-muted" style="font-size:13px">No liability accounts with activity.</p>
        @endforelse
        <div class="info-row" style="border-top:2px solid var(--border-strong);margin-top:8px"><span style="font-weight:800">Total Liabilities</span><b>TZS {{ number_format($totals['liabilities'], 2) }}</b></div>
      </div>

      <div class="solid-card">
        <h2 style="font-size:15px;margin:0 0 12px">Equity</h2>
        @forelse($equity['accounts'] as $r)
          <div class="info-row" style="cursor:pointer" data-view-bs-account data-id="{{ $r['account']->id }}" data-type="equity"><span>{{ $r['account']->code }} — {{ $r['account']->name }}</span><b>TZS {{ number_format($r['amount'], 2) }}</b></div>
        @empty
          <p class="text-muted" style="font-size:13px">No equity accounts with activity.</p>
        @endforelse
        <div class="info-row"><span>Surplus / (Deficit) for period</span><b>TZS {{ number_format($netResult, 2) }}</b></div>
        <div class="info-row" style="border-top:2px solid var(--border-strong);margin-top:8px"><span style="font-weight:800">Total Equity</span><b>TZS {{ number_format($totals['equity'], 2) }}</b></div>
      </div>

      <div class="glass-card" style="margin-top:14px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <span style="font-weight:800">Liabilities + Equity</span>
        <b>TZS {{ number_format($totals['liabilities'] + $totals['equity'], 2) }}</b>
      </div>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="bsDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="bsDrawerTitle">Account Details</h3><p id="bsDrawerType" class="badge badge-neutral badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="info-grid">
        <div class="info-row"><span>Code</span><b id="bsDrawerCode">—</b></div>
        <div class="info-row"><span>Name</span><b id="bsDrawerName">—</b></div>
        <div class="info-row"><span>Type</span><b id="bsDrawerTypeVal">—</b></div>
        <div class="info-row"><span>Total Debits</span><b id="bsDrawerDr">—</b></div>
        <div class="info-row"><span>Total Credits</span><b id="bsDrawerCr">—</b></div>
        <div class="info-row"><span>Net Balance</span><b id="bsDrawerNet">—</b></div>
      </div>

      <div class="payments-head" style="margin-top:18px">
        <span>Recent Activity</span><span class="payments-count" id="bsLinesCount">0</span>
      </div>
      <div id="bsLines" class="payments-list"></div>
      <div style="margin-top:14px">
        <a id="bsLedgerLink" href="#" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">View Full Ledger</a>
      </div>
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
  document.querySelectorAll('[data-view-bs-account]').forEach(function(el){
    el.addEventListener('click', function(){
      var id = el.dataset.id;
      var type = el.dataset.type;
      fetch('{{ url("/accounting/api/accounts") }}/' + id)
        .then(function(r){ return r.json(); })
        .then(function(d){
          var a = d.account;
          var typeColors = {asset:'info',liability:'warning',equity:'purple',income:'success',expense:'danger'};
          document.getElementById('bsDrawerTitle').textContent = a.code + ' — ' + a.name;
          document.getElementById('bsDrawerType').textContent = a.type.charAt(0).toUpperCase() + a.type.slice(1);
          document.getElementById('bsDrawerType').className = 'badge badge-' + (typeColors[a.type]||'neutral') + ' badge-dotted';
          document.getElementById('bsDrawerCode').textContent = a.code;
          document.getElementById('bsDrawerName').textContent = a.name;
          document.getElementById('bsDrawerTypeVal').textContent = a.type.charAt(0).toUpperCase() + a.type.slice(1);
          document.getElementById('bsDrawerDr').textContent = 'TZS ' + d.debit.toLocaleString();
          document.getElementById('bsDrawerCr').textContent = 'TZS ' + d.credit.toLocaleString();
          document.getElementById('bsDrawerNet').textContent = 'TZS ' + Math.abs(d.net).toLocaleString();
          document.getElementById('bsDrawerNet').style.color = d.net >= 0 ? 'var(--success)' : 'var(--danger)';

          document.getElementById('bsLinesCount').textContent = d.recentLines.length;
          var list = document.getElementById('bsLines');
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
                '<div class="pay-amt" style="text-align:right"><div>' + (isDr ? 'Dr TZS ' + Number(l.debit).toLocaleString() : '') + '</div><div style="color:var(--red);font-size:11px">' + (!isDr && Number(l.credit) > 0 ? 'Cr TZS ' + Number(l.credit).toLocaleString() : '') + '</div></div>';
              list.appendChild(item);
            });
          }

          document.getElementById('bsLedgerLink').href = '{{ url("/accounting/ledger") }}?account=' + a.id;
          openDrawerById('bsDetailDrawer');
        });
    });
  });
});
</script>
@endpush
