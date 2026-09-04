@extends('layouts.app')

@section('title', 'Transaction History — OpenGate Camp Connect')
@section('crumb', 'Finance / Financial Accounting / Transactions')
@section('page_title', 'Transaction History')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Transaction History</h2><div class="sub">@if($fy) Period: {{ $fy->name }}. @else All periods. @endif Every line is a posted journal entry — sorted by most recent.</div></div>
  </div>

  <div class="glass-card" style="margin-bottom:20px;padding:16px 20px">
    <form method="GET" action="{{ route('accounting.transactions') }}" class="form-grid" style="grid-template-columns:2fr 1fr auto;align-items:end;gap:12px">
      <div class="field"><label>Search</label><input name="q" value="{{ $q }}" placeholder="Search description, reference, or entry number..."></div>
      <div class="field"><label>Filter by Account</label>
        <select name="account">
          <option value="">All Accounts</option>
          @foreach($accounts as $a)<option value="{{ $a->id }}" {{ $accountId==$a->id ? 'selected' : '' }}>{{ $a->code }} — {{ $a->name }}</option>@endforeach
        </select>
      </div>
      <button type="submit" class="btn btn-secondary" style="height:38px">Filter</button>
    </form>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Entry No</th><th>Date</th><th>Type / Source</th><th>Account</th><th>Description</th><th style="text-align:right">Debit</th><th style="text-align:right">Credit</th><th style="width:70px">Receipt</th></tr></thead>
        <tbody>
          @forelse($lines as $l)
          @php
                $src = $sources[$l->entry->id] ?? null;
                $entryLines = $l->entry->lines?->map(fn ($el) => [
                    'account' => ($el->account?->code ?? '').' — '.($el->account?->name ?? ''),
                    'desc' => $el->description ?: $l->entry->description,
                    'debit' => (float) $el->debit,
                    'credit' => (float) $el->credit,
                ])->values()->all() ?? [];
                $entryLinesJson = json_encode($entryLines, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
            @endphp
            <tr style="cursor:pointer" data-view-txn
            data-entry="{{ $l->entry->entry_no }}"
            data-date="{{ $l->entry->entry_date->format('d M Y') }}"
            data-time="{{ $l->entry->created_at ? $l->entry->created_at->format('H:i') : '' }}"
            data-reference="{{ $l->entry->reference ?: '—' }}"
            data-description="{{ $l->entry->description }}"
            data-source="{{ $src['type'] ?? 'Journal entry' }}"
            data-source-label="{{ $src['label'] ?? '' }}"
            data-source-amount="{{ $src['amount'] ?? '' }}"
            data-account="{{ $l->account->code }} — {{ $l->account->name }}"
            data-line-desc="{{ $l->description ?: $l->entry->description }}"
            data-debit="{{ $l->debit }}"
            data-credit="{{ $l->credit }}"
            data-receipt-url="{{ route('accounting.transactions.receipt', $l->entry) }}"
            data-lines="{{ $entryLinesJson }}">
            <td><a href="{{ route('accounting.journal') }}" style="color:var(--blue-accent);font-weight:600">{{ $l->entry->entry_no }}</a></td>
            <td>{{ $l->entry->entry_date->format('d M Y') }}</td>
            <td>
              @if($src && $src['type'])
                <span class="badge badge-{{ match($src['type']) {
                    'Registration payment' => 'success',
                    'Pledge payment' => 'info',
                    'Contribution/Income' => 'purple',
                    'Expense' => 'danger',
                    default => 'neutral',
                } }} badge-dotted">{{ $src['type'] }}</span>
                @if($src['label'])
                <div style="font-size:11px;color:var(--text-tertiary);margin-top:3px;font-weight:600">{{ $src['label'] }}</div>
                @endif
              @else
                <span class="badge badge-neutral badge-dotted">Journal entry</span>
              @endif
            </td>
            <td><span class="badge badge-{{ $l->debit > 0 ? 'success' : 'danger' }} badge-dotted">{{ $l->account->code }}</span></td>
            <td>{{ $l->entry->description }}{{ $l->description ? ' — '.$l->description : '' }}</td>
            <td style="text-align:right;font-weight:600;color:{{ $l->debit > 0 ? 'var(--green-accent)' : 'var(--text-muted)' }}">
              {{ $l->debit > 0 ? 'TZS '.number_format($l->debit) : '—' }}
            </td>
            <td style="text-align:right;font-weight:600;color:{{ $l->credit > 0 ? 'var(--red)' : 'var(--text-muted)' }}">
              {{ $l->credit > 0 ? 'TZS '.number_format($l->credit) : '—' }}
            </td>
            <td><a href="{{ route('accounting.transactions.receipt', $l->entry) }}" class="btn btn-ghost btn-sm" style="padding:6px 10px" title="Download receipt PDF">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>
              <span>PDF</span>
            </a></td>
          </tr>
          @empty
          <tr><td colspan="8"><div class="empty-state"><h3>No transactions found</h3><p>No journal entries match your filter.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="tf-info">Showing {{ $lines->firstItem() ?? 0 }}“{{ $lines->lastItem() ?? 0 }} of {{ $lines->total() }} transactions</span>
      <div class="pagination">{{ $lines->links() }}</div>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="txnDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Transaction Details</h3><p id="txnEntry" class="badge badge-neutral badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="info-grid">
        <div class="info-row"><span>Date</span><b id="txnDate">—</b></div>
        <div class="info-row"><span>Time</span><b id="txnTime">—</b></div>
        <div class="info-row"><span>Reference</span><b id="txnReference" style="white-space:normal">—</b></div>
        <div class="info-row full"><span>Description</span><b id="txnDescription" style="white-space:normal">—</b></div>
        <div class="info-row full"><span>Source</span><b id="txnSource" style="white-space:normal">—</b></div>
      </div>

      <div class="payments-head" style="margin-top:18px">
        <span>Journal Lines</span><span class="payments-count" id="txnLinesCount">0</span>
      </div>
      <div id="txnLines" class="payments-list"></div>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
      <button type="button" class="btn btn-accent" id="txnReceiptBtn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>
        Download Receipt (PDF)
      </button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function decodeEntities(s){
  if(s.indexOf('&') === -1) return s;
  var ta = document.createElement('textarea');
  ta.innerHTML = s;
  return ta.value;
}
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('[data-view-txn]').forEach(function(tr){
    tr.addEventListener('click', function(e){
      if(e.target.closest('a') || e.target.closest('button')) return;
      var d = tr.dataset;
      document.getElementById('txnEntry').textContent = d.entry || '—';
      document.getElementById('txnDate').textContent = d.date || '—';
      document.getElementById('txnTime').textContent = d.time || '—';
      document.getElementById('txnReference').textContent = d.reference || '—';
      document.getElementById('txnDescription').textContent = d.description || '—';
      var srcTxt = d.source || 'Journal entry';
      if(d.sourceLabel){ srcTxt += ' · ' + d.sourceLabel; }
      if(d.sourceAmount){ srcTxt += ' · TZS ' + Number(d.sourceAmount).toLocaleString(); }
      document.getElementById('txnSource').textContent = srcTxt;

      var lines = [];
      try { lines = JSON.parse(decodeEntities(d.lines || '[]')); } catch(e){ lines = []; }
      document.getElementById('txnLinesCount').textContent = lines.length;
      var list = document.getElementById('txnLines');
      list.innerHTML = '';
      if(lines.length === 0){
        list.innerHTML = '<div class="pay-empty">No lines</div>';
      } else {
        lines.forEach(function(l, i){
          var fmt = function(n){ return 'TZS ' + Number(n||0).toLocaleString(); };
          var item = document.createElement('div');
          item.className = 'pay-item';
          item.innerHTML =
            '<div class="pay-ico" style="color:' + (Number(l.debit)>0 ? 'var(--success)' : 'var(--red)') + '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>' +
            '<div class="pay-main"><div class="pm-name">' + (l.account||'—') + '</div><div class="pm-sub">' + (l.desc||'') + '</div></div>' +
            '<div class="pay-amt" style="text-align:right"><div>' + (Number(l.debit)>0 ? fmt(l.debit) : '') + '</div><div style="color:var(--red);font-size:11px">' + (Number(l.credit)>0 ? 'CR '+fmt(l.credit) : '') + '</div></div>';
          list.appendChild(item);
        });
      }

      document.getElementById('txnReceiptBtn').onclick = function(){ window.open(d.receiptUrl, '_blank'); };
      openDrawerById('txnDetailDrawer');
    });
  });
});
</script>
@endpush
