@extends('layouts.app')

@section('title', 'General Ledger — Open Gate Camp Mission')
@section('crumb', 'Finance / Financial Accounting / General Ledger')
@section('page_title', 'General Ledger')

@section('content')
<div class="fade-in">
  <div class="section-head"><div><h2>General Ledger</h2><div class="sub">@if($fy) Period: {{ $fy->name }}. @else All periods. @endif</div></div></div>

  <form class="toolbar" method="GET" action="{{ url('/accounting/ledger') }}">
    <select class="filter-select" name="account" style="min-width:280px" onchange="this.form.submit()">
      <option value="">— Select an account —</option>
      @foreach($accounts as $a)
        <option value="{{ $a->id }}" {{ $account?->id===$a->id ? 'selected' : '' }}>{{ $a->code }} — {{ $a->name }}</option>
      @endforeach
    </select>
  </form>

  @if($account)
  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table" style="min-width:0">
        <thead><tr><th>Date</th><th>Entry</th><th>Description</th><th style="text-align:right">Debit</th><th style="text-align:right">Credit</th><th style="text-align:right">Balance ({{ $account->isDebitNormal() ? 'Dr' : 'Cr' }})</th></tr></thead>
        <tbody>
          @forelse($lines as $l)
          <tr style="cursor:pointer" data-view-ledger data-id="{{ $l->id }}">
            <td>{{ $l->entry->entry_date->format('d M Y') }}</td>
            <td><b>{{ $l->entry->entry_no }}</b></td>
            <td>{{ $l->description ?? $l->entry->description ?? '—' }}</td>
            <td style="text-align:right">{{ $l->debit > 0 ? number_format($l->debit, 2) : '—' }}</td>
            <td style="text-align:right">{{ $l->credit > 0 ? number_format($l->credit, 2) : '—' }}</td>
            <td style="text-align:right"><b>TZS {{ number_format(abs($l->balance), 2) }}</b></td>
          </tr>
          @empty
          <tr><td colspan="6"><div class="empty-state"><h3>No movements</h3><p>This account has no activity in the selected period.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @else
  <div class="glass-card"><div class="empty-state"><p>Select an account above to view its ledger movements.</p></div></div>
  @endif
</div>

<div class="drawer-overlay" id="ledgerDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Transaction Details</h3><p id="ledgerDrawerEntry" class="badge badge-neutral badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="info-grid">
        <div class="info-row"><span>Entry No</span><b id="ledgerDrawerEntryNo">—</b></div>
        <div class="info-row"><span>Date</span><b id="ledgerDrawerDate">—</b></div>
        <div class="info-row"><span>Description</span><b id="ledgerDrawerDesc" style="white-space:normal">—</b></div>
        <div class="info-row"><span>Reference</span><b id="ledgerDrawerRef">—</b></div>
        <div class="info-row"><span>Status</span><b id="ledgerDrawerStatus">—</b></div>
        <div class="info-row"><span>Account</span><b id="ledgerDrawerAccount">—</b></div>
        <div class="info-row"><span>Debit</span><b id="ledgerDrawerDr" style="color:var(--success)">—</b></div>
        <div class="info-row"><span>Credit</span><b id="ledgerDrawerCr" style="color:var(--red)">—</b></div>
      </div>

      <div class="payments-head" style="margin-top:18px">
        <span>All Journal Lines</span><span class="payments-count" id="ledgerLinesCount">0</span>
      </div>
      <div id="ledgerLines" class="payments-list"></div>
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
  document.querySelectorAll('[data-view-ledger]').forEach(function(tr){
    tr.addEventListener('click', function(e){
      if(e.target.closest('a') || e.target.closest('button') || e.target.closest('form')) return;
      var id = tr.dataset.id;
      fetch('{{ url("/accounting/api/ledger") }}/' + id)
        .then(function(r){ return r.json(); })
        .then(function(d){
          document.getElementById('ledgerDrawerEntry').textContent = d.entry_no;
          document.getElementById('ledgerDrawerEntryNo').textContent = d.entry_no;
          document.getElementById('ledgerDrawerDate').textContent = d.entry_date;
          document.getElementById('ledgerDrawerDesc').textContent = d.description || '—';
          document.getElementById('ledgerDrawerRef').textContent = d.reference || '—';
          document.getElementById('ledgerDrawerStatus').textContent = d.status ? d.status.charAt(0).toUpperCase() + d.status.slice(1) : '—';
          document.getElementById('ledgerDrawerAccount').textContent = d.account.code + ' — ' + d.account.name;
          document.getElementById('ledgerDrawerDr').textContent = Number(d.debit) > 0 ? 'TZS ' + Number(d.debit).toLocaleString() : '—';
          document.getElementById('ledgerDrawerCr').textContent = Number(d.credit) > 0 ? 'TZS ' + Number(d.credit).toLocaleString() : '—';

          document.getElementById('ledgerLinesCount').textContent = d.allLines.length;
          var list = document.getElementById('ledgerLines');
          list.innerHTML = '';
          d.allLines.forEach(function(l){
            var isDr = Number(l.debit) > 0;
            var item = document.createElement('div');
            item.className = 'pay-item';
            item.innerHTML =
              '<div class="pay-ico" style="background:' + (isDr ? 'var(--success-bg)' : 'var(--danger-bg)') + ';color:' + (isDr ? 'var(--success)' : 'var(--red)') + '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>' +
              '<div class="pay-main"><div class="pm-name">' + (l.code ? l.code + ' — ' + l.account : '—') + '</div></div>' +
              '<div class="pay-amt" style="text-align:right"><div>' + (isDr ? 'Dr TZS ' + Number(l.debit).toLocaleString() : '') + '</div><div style="color:var(--red);font-size:11px">' + (!isDr && Number(l.credit) > 0 ? 'Cr TZS ' + Number(l.credit).toLocaleString() : '') + '</div></div>';
            list.appendChild(item);
          });
          openDrawerById('ledgerDetailDrawer');
        });
    });
  });
});
</script>
@endpush
