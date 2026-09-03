@extends('layouts.app')

@section('title', 'Income Statement — Open Gate Camp Mission')
@section('crumb', 'Finance / Financial Accounting / Income Statement')
@section('page_title', 'Income Statement')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Income Statement</h2><div class="sub">@if($fy) For {{ $fy->name }}. @else For all periods. @endif Accrual, double-entry basis.</div></div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">Print</button>
  </div>

  <div class="two-col" style="grid-template-columns:1fr 1fr;margin-bottom:0">
    <div class="solid-card">
      <h2 style="font-size:15px;margin:0 0 12px">Income</h2>
      @forelse($income['accounts'] as $r)
        <div class="info-row" style="cursor:pointer" data-view-is-account data-code="{{ $r['account']->code }}" data-id="{{ $r['account']->id }}" data-type="income"><span>{{ $r['account']->code }} — {{ $r['account']->name }}</span><b>TZS {{ number_format($r['amount'], 2) }}</b></div>
      @empty
        <p class="text-muted" style="font-size:13px">No income recorded.</p>
      @endforelse
      <div class="info-row" style="border-top:2px solid var(--border-strong);margin-top:8px"><span style="font-weight:800">Total Income</span><b>TZS {{ number_format($totalIncome, 2) }}</b></div>
    </div>

    <div class="solid-card">
      <h2 style="font-size:15px;margin:0 0 12px">Expenses</h2>
      @forelse($expense['accounts'] as $r)
        <div class="info-row" style="cursor:pointer" data-view-is-account data-code="{{ $r['account']->code }}" data-id="{{ $r['account']->id }}" data-type="expense"><span>{{ $r['account']->code }} — {{ $r['account']->name }}</span><b>TZS {{ number_format($r['amount'], 2) }}</b></div>
      @empty
        <p class="text-muted" style="font-size:13px">No expenses recorded.</p>
      @endforelse
      <div class="info-row" style="border-top:2px solid var(--border-strong);margin-top:8px"><span style="font-weight:800">Total Expenses</span><b>TZS {{ number_format($totalExpense, 2) }}</b></div>
    </div>
  </div>

  <div class="glass-card" style="margin-top:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
    <h2 style="font-size:16px;margin:0">{{ ($totalIncome - $totalExpense) >= 0 ? 'Surplus' : 'Deficit' }} for the period</h2>
    <div style="font-size:24px;font-weight:800;color:{{ ($totalIncome - $totalExpense) >= 0 ? 'var(--success)' : 'var(--danger)' }}">
      TZS {{ number_format($totalIncome - $totalExpense, 2) }}
    </div>
  </div>
</div>

<div class="drawer-overlay" id="isDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="isDrawerTitle">Account Details</h3><p id="isDrawerType" class="badge badge-neutral badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="info-grid">
        <div class="info-row"><span>Code</span><b id="isDrawerCode">—</b></div>
        <div class="info-row"><span>Name</span><b id="isDrawerName">—</b></div>
        <div class="info-row"><span>Total Debits</span><b id="isDrawerDr">—</b></div>
        <div class="info-row"><span>Total Credits</span><b id="isDrawerCr">—</b></div>
        <div class="info-row"><span>Net Amount</span><b id="isDrawerNet">—</b></div>
      </div>

      <div class="payments-head" style="margin-top:18px">
        <span>Recent Activity</span><span class="payments-count" id="isLinesCount">0</span>
      </div>
      <div id="isLines" class="payments-list"></div>
      <div style="margin-top:14px">
        <a id="isLedgerLink" href="#" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">View Full Ledger</a>
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
  document.querySelectorAll('[data-view-is-account]').forEach(function(el){
    el.addEventListener('click', function(){
      var id = el.dataset.id;
      var type = el.dataset.type;
      fetch('{{ url("/accounting/api/accounts") }}/' + id)
        .then(function(r){ return r.json(); })
        .then(function(d){
          var a = d.account;
          var typeColors = {asset:'info',liability:'warning',equity:'purple',income:'success',expense:'danger'};
          document.getElementById('isDrawerTitle').textContent = a.code + ' — ' + a.name;
          document.getElementById('isDrawerType').textContent = type.charAt(0).toUpperCase() + type.slice(1);
          document.getElementById('isDrawerType').className = 'badge badge-' + (typeColors[type]||'neutral') + ' badge-dotted';
          document.getElementById('isDrawerCode').textContent = a.code;
          document.getElementById('isDrawerName').textContent = a.name;
          document.getElementById('isDrawerDr').textContent = 'TZS ' + d.debit.toLocaleString();
          document.getElementById('isDrawerCr').textContent = 'TZS ' + d.credit.toLocaleString();
          document.getElementById('isDrawerNet').textContent = 'TZS ' + Math.abs(d.net).toLocaleString();
          document.getElementById('isDrawerNet').style.color = type === 'income' ? 'var(--success)' : 'var(--danger)';

          document.getElementById('isLinesCount').textContent = d.recentLines.length;
          var list = document.getElementById('isLines');
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

          document.getElementById('isLedgerLink').href = '{{ url("/accounting/ledger") }}?account=' + a.id;
          openDrawerById('isDetailDrawer');
        });
    });
  });
});
</script>
@endpush
