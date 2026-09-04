@extends('layouts.app')

@section('title', 'Trial Balance — OpenGate Camp Connect')
@section('crumb', 'Finance / Financial Accounting / Trial Balance')
@section('page_title', 'Trial Balance')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Trial Balance</h2><div class="sub">@if($fy) Period: {{ $fy->name }}. @else All periods. @endif Debits must equal credits.</div></div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">Print</button>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table" style="min-width:0">
        <thead><tr><th>Code</th><th>Account</th><th>Type</th><th style="text-align:right">Debit (TZS)</th><th style="text-align:right">Credit (TZS)</th></tr></thead>
        <tbody>
          @forelse($rows as $r)
          <tr style="cursor:pointer" data-view-tb data-id="{{ $r['account']->id }}">
            <td>{{ $r['account']->code }}</td>
            <td>{{ $r['account']->name }}</td>
            <td><span class="badge badge-neutral badge-dotted">{{ ucfirst($r['account']->type) }}</span></td>
            <td style="text-align:right">{{ $r['debit'] > 0 ? number_format($r['debit'], 2) : '—' }}</td>
            <td style="text-align:right">{{ $r['credit'] > 0 ? number_format($r['credit'], 2) : '—' }}</td>
          </tr>
          @empty
          <tr><td colspan="5"><div class="empty-state"><h3>No postings yet</h3><p>Post journal entries to build the trial balance.</p></div></td></tr>
          @endforelse
        </tbody>
        <tfoot>
          <tr style="font-weight:800;background:var(--blue-light)">
            <td colspan="3">TOTALS</td>
            <td style="text-align:right">TZS {{ number_format($totals['debit'], 2) }}</td>
            <td style="text-align:right">TZS {{ number_format($totals['credit'], 2) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  @if($totals['debit'] != $totals['credit'])
    <p class="badge badge-danger badge-dotted" style="margin-top:10px">Out of balance by TZS {{ number_format(abs($totals['debit'] - $totals['credit']), 2) }}</p>
  @else
    <p class="badge badge-success badge-dotted" style="margin-top:10px">Balanced <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px"><path d="M20 6L9 17l-5-5"/></svg></p>
  @endif
</div>

<div class="drawer-overlay" id="tbDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="tbDrawerTitle">Account Details</h3><p id="tbDrawerType" class="badge badge-neutral badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="info-grid">
        <div class="info-row"><span>Code</span><b id="tbDrawerCode">—</b></div>
        <div class="info-row"><span>Name</span><b id="tbDrawerName">—</b></div>
        <div class="info-row"><span>Type</span><b id="tbDrawerTypeVal">—</b></div>
        <div class="info-row"><span>Total Debits</span><b id="tbDrawerDr">—</b></div>
        <div class="info-row"><span>Total Credits</span><b id="tbDrawerCr">—</b></div>
        <div class="info-row"><span>Net Balance</span><b id="tbDrawerNet">—</b></div>
      </div>

      <div class="payments-head" style="margin-top:18px">
        <span>Recent Activity</span><span class="payments-count" id="tbLinesCount">0</span>
      </div>
      <div id="tbLines" class="payments-list"></div>
      <div style="margin-top:14px">
        <a id="tbLedgerLink" href="#" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">View Full Ledger</a>
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
  document.querySelectorAll('[data-view-tb]').forEach(function(tr){
    tr.addEventListener('click', function(e){
      if(e.target.closest('a') || e.target.closest('button') || e.target.closest('form')) return;
      var id = tr.dataset.id;
      fetch('{{ url("/accounting/api/trial-balance") }}/' + id)
        .then(function(r){ return r.json(); })
        .then(function(d){
          var a = d.account;
          var typeColors = {asset:'info',liability:'warning',equity:'purple',income:'success',expense:'danger'};
          document.getElementById('tbDrawerTitle').textContent = a.code + ' — ' + a.name;
          document.getElementById('tbDrawerType').textContent = a.type.charAt(0).toUpperCase() + a.type.slice(1);
          document.getElementById('tbDrawerType').className = 'badge badge-' + (typeColors[a.type]||'neutral') + ' badge-dotted';
          document.getElementById('tbDrawerCode').textContent = a.code;
          document.getElementById('tbDrawerName').textContent = a.name;
          document.getElementById('tbDrawerTypeVal').textContent = a.type.charAt(0).toUpperCase() + a.type.slice(1);
          document.getElementById('tbDrawerDr').textContent = 'TZS ' + d.debit.toLocaleString();
          document.getElementById('tbDrawerCr').textContent = 'TZS ' + d.credit.toLocaleString();
          document.getElementById('tbDrawerNet').textContent = 'TZS ' + Math.abs(d.net).toLocaleString();
          document.getElementById('tbDrawerNet').style.color = d.net >= 0 ? 'var(--success)' : 'var(--danger)';

          document.getElementById('tbLinesCount').textContent = d.recentLines.length;
          var list = document.getElementById('tbLines');
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

          document.getElementById('tbLedgerLink').href = '{{ url("/accounting/ledger") }}?account=' + a.id;
          openDrawerById('tbDetailDrawer');
        });
    });
  });
});
</script>
@endpush
