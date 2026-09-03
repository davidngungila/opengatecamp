@extends('layouts.app')

@section('title', 'Financial Accounting — Open Gate Camp Mission')
@section('crumb', 'Finance / Financial Accounting')
@section('page_title', 'Financial Accounting')

@section('content')
<div class="fade-in">
  <div class="welcome-block">
    <h1>Financial Accounting</h1>
    <p>Double-entry bookkeeping for the chaplaincy.
       @if($fy) Period: <b>{{ $fy->name }}</b>. @else Showing all periods. @endif
    </p>
  </div>

  <div class="kpi-grid">
    <div class="kpi-card" style="cursor:pointer" data-overview-drawer="income">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--success-bg);color:var(--success)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v6M9 5h6"/><path d="M4 22l3-11a5 5 0 0110 0l3 11"/><path d="M8 22h8"/></svg></div></div>
      <div class="kpi-value">TZS {{ number_format($totals['income']) }}</div>
      <div class="kpi-label">Total Income</div>
    </div>
    <div class="kpi-card" style="cursor:pointer" data-overview-drawer="expense">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--danger-bg);color:var(--danger)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></svg></div></div>
      <div class="kpi-value">TZS {{ number_format($totals['expense']) }}</div>
      <div class="kpi-label">Total Expenses</div>
    </div>
    <div class="kpi-card" style="cursor:pointer" data-overview-drawer="result">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--info-bg);color:var(--info)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div></div>
      <div class="kpi-value">TZS {{ number_format($totals['result']) }}</div>
      <div class="kpi-label">{{ $totals['result'] >= 0 ? 'Surplus' : 'Deficit' }} for period</div>
    </div>
    <div class="kpi-card" style="cursor:pointer" data-overview-drawer="cash">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--purple-bg);color:var(--purple)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v10M15 9.5c0-1.4-1.3-2.5-3-2.5s-3 1-3 2.3c0 3 6 1.4 6 4.3 0 1.4-1.3 2.4-3 2.4s-3-1-3-2.4"/></svg></div></div>
      <div class="kpi-value">TZS {{ number_format($totals['cash']) }}</div>
      <div class="kpi-label">Cash &amp; Bank Balance</div>
    </div>
  </div>

  <div class="two-col">
    <div class="glass-card">
      <div class="section-head" style="margin-bottom:10px"><h2>Recent Journal Entries</h2><a class="link-btn" href="{{ route('accounting.journal') }}">View all</a></div>
      @forelse($recentEntries as $e)
      <div class="mini-row" style="cursor:pointer" data-view-je data-id="{{ $e->id }}">
        <div class="m-ico" style="background:var(--blue-light);color:var(--blue-accent)">{{ $e->lines->count() }}L</div>
        <div class="m-body"><p>{{ $e->entry_no }} · {{ $e->description ?? 'Journal entry' }}</p><span>{{ $e->entry_date->format('d M Y') }} · Dr/Cr TZS {{ number_format((float) $e->lines->sum('debit'), 0) }}</span></div>
        <span class="badge badge-{{ $e->status==='posted' ? 'success' : 'neutral' }} badge-dotted">{{ ucfirst($e->status) }}</span>
      </div>
      @empty
      <div class="empty-state" style="padding:30px 16px"><p>No journal entries yet.</p></div>
      @endforelse
    </div>

    <div class="glass-card">
      <div class="section-head" style="margin-bottom:10px"><h2>Quick Links</h2></div>
      <div class="quick-actions-grid" style="grid-template-columns:repeat(2,1fr)">
        <a class="qa-btn" href="{{ route('accounting.journal.create') }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div><span>New Journal Entry</span></a>
        <a class="qa-btn" href="{{ route('accounting.accounts') }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg></div><span>Chart of Accounts</span></a>
        <a class="qa-btn" href="{{ route('accounting.trial-balance') }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div><span>Trial Balance</span></a>
        <a class="qa-btn" href="{{ route('accounting.income-statement') }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg></div><span>Income Statement</span></a>
        <a class="qa-btn" href="{{ route('accounting.balance-sheet') }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div><span>Balance Sheet</span></a>
        <a class="qa-btn" href="{{ route('accounting.ledger') }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg></div><span>General Ledger</span></a>
      </div>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="overviewDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="overviewDrawerTitle">Details</h3><p id="overviewDrawerSub" class="badge badge-neutral badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div id="overviewDrawerContent"></div>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="overviewJeDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Journal Entry</h3><p id="overviewJeNo" class="badge badge-neutral badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="info-grid">
        <div class="info-row"><span>Date</span><b id="overviewJeDate">—</b></div>
        <div class="info-row"><span>Description</span><b id="overviewJeDesc" style="white-space:normal">—</b></div>
        <div class="info-row"><span>Reference</span><b id="overviewJeRef">—</b></div>
        <div class="info-row"><span>Status</span><b id="overviewJeStatus">—</b></div>
        <div class="info-row"><span>Created By</span><b id="overviewJeBy">—</b></div>
        <div class="info-row"><span>Created At</span><b id="overviewJeAt">—</b></div>
      </div>
      <div class="payments-head" style="margin-top:18px">
        <span>Journal Lines</span><span class="payments-count" id="overviewJeLinesCount">0</span>
      </div>
      <div id="overviewJeLines" class="payments-list"></div>
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
  document.querySelectorAll('[data-overview-drawer]').forEach(function(el){
    el.addEventListener('click', function(){
      var type = el.dataset.overviewDrawer;
      fetch('{{ url("/accounting/api/overview") }}')
        .then(function(r){ return r.json(); })
        .then(function(d){
          var title = '', sub = '', html = '';
          if(type === 'income'){
            title = 'Income Breakdown';
            sub = d.totalIncome.toLocaleString() + ' total';
            html = '<div class="info-grid">';
            d.incomeAccounts.forEach(function(a){
              html += '<div class="info-row"><span>' + a.code + ' — ' + a.name + '</span><b style="color:var(--success)">TZS ' + a.amount.toLocaleString() + '</b></div>';
            });
            if(d.incomeAccounts.length === 0) html += '<div class="empty-state" style="padding:30px"><p>No income recorded.</p></div>';
            html += '</div>';
          } else if(type === 'expense'){
            title = 'Expense Breakdown';
            sub = d.totalExpense.toLocaleString() + ' total';
            html = '<div class="info-grid">';
            d.expenseAccounts.forEach(function(a){
              html += '<div class="info-row"><span>' + a.code + ' — ' + a.name + '</span><b style="color:var(--danger)">TZS ' + a.amount.toLocaleString() + '</b></div>';
            });
            if(d.expenseAccounts.length === 0) html += '<div class="empty-state" style="padding:30px"><p>No expenses recorded.</p></div>';
            html += '</div>';
          } else if(type === 'result'){
            title = 'Surplus / Deficit';
            var result = d.totalIncome - d.totalExpense;
            sub = result >= 0 ? 'Surplus' : 'Deficit';
            html = '<div class="info-grid">' +
              '<div class="info-row"><span>Total Income</span><b style="color:var(--success)">TZS ' + d.totalIncome.toLocaleString() + '</b></div>' +
              '<div class="info-row"><span>Total Expenses</span><b style="color:var(--danger)">TZS ' + d.totalExpense.toLocaleString() + '</b></div>' +
              '<div class="info-row" style="border-top:2px solid var(--border-strong);margin-top:4px"><span style="font-weight:800">' + (result >= 0 ? 'Surplus' : 'Deficit') + '</span><b style="color:' + (result >= 0 ? 'var(--success)' : 'var(--danger)') + ';font-size:16px">TZS ' + Math.abs(result).toLocaleString() + '</b></div>' +
              '</div>';
          } else if(type === 'cash'){
            title = 'Cash & Bank Balances';
            sub = d.cashAccounts.length + ' accounts';
            html = '<div class="info-grid">';
            d.cashAccounts.forEach(function(a){
              html += '<div class="info-row"><span>' + a.code + ' — ' + a.name + '</span><b style="color:var(--blue-accent)">TZS ' + a.balance.toLocaleString() + '</b></div>';
            });
            if(d.cashAccounts.length === 0) html += '<div class="empty-state" style="padding:30px"><p>No cash balances.</p></div>';
            html += '</div>';
          }
          document.getElementById('overviewDrawerTitle').textContent = title;
          document.getElementById('overviewDrawerSub').textContent = sub;
          document.getElementById('overviewDrawerContent').innerHTML = html;
          openDrawerById('overviewDrawer');
        });
    });
  });

  document.querySelectorAll('[data-view-je]').forEach(function(el){
    el.addEventListener('click', function(){
      var id = el.dataset.id;
      fetch('{{ url("/accounting/api/journal") }}/' + id)
        .then(function(r){ return r.json(); })
        .then(function(d){
          document.getElementById('overviewJeNo').textContent = d.entry.entry_no || '—';
          document.getElementById('overviewJeDate').textContent = d.entry.entry_date || '—';
          document.getElementById('overviewJeDesc').textContent = d.entry.description || '—';
          document.getElementById('overviewJeRef').textContent = d.entry.reference || '—';
          document.getElementById('overviewJeStatus').textContent = d.entry.status || '—';
          document.getElementById('overviewJeBy').textContent = d.entry.created_by || '—';
          document.getElementById('overviewJeAt').textContent = d.entry.created_at || '—';
          document.getElementById('overviewJeLinesCount').textContent = d.lines.length;
          var list = document.getElementById('overviewJeLines');
          list.innerHTML = '';
          d.lines.forEach(function(l){
            var isDr = Number(l.debit) > 0;
            var item = document.createElement('div');
            item.className = 'pay-item';
            item.innerHTML =
              '<div class="pay-ico" style="background:' + (isDr ? 'var(--success-bg)' : 'var(--danger-bg)') + ';color:' + (isDr ? 'var(--success)' : 'var(--red)') + '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>' +
              '<div class="pay-main"><div class="pm-name">' + (l.code ? l.code + ' — ' + l.account : '—') + '</div><div class="pm-sub">' + (l.description || '') + '</div></div>' +
              '<div class="pay-amt" style="text-align:right"><div>' + (isDr ? 'Dr TZS ' + Number(l.debit).toLocaleString() : '') + '</div><div style="color:var(--red);font-size:11px">' + (!isDr && Number(l.credit) > 0 ? 'Cr TZS ' + Number(l.credit).toLocaleString() : '') + '</div></div>';
            list.appendChild(item);
          });
          openDrawerById('overviewJeDrawer');
        });
    });
  });
});
</script>
@endpush
