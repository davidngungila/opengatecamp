@extends('layouts.app')

@section('title', 'Journal Entries — OpenGate Camp Connect')
@section('crumb', 'Finance / Financial Accounting / Journal Entries')
@section('page_title', 'Journal Entries')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Journal Entries</h2><div class="sub">@if($fy) Period: {{ $fy->name }}. @else All periods. @endif Every entry must balance: Debits = Credits.</div></div>
    <a class="btn btn-accent" href="{{ route('accounting.journal.create') }}">+ New Journal Entry</a>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Entry No</th><th>Date</th><th>Description</th><th>Reference</th><th>Lines</th><th>Amount</th><th>Status</th><th style="width:60px">Actions</th></tr></thead>
        <tbody>
          @forelse($entries as $i => $e)
          @php $total = (float) $e->lines->sum('debit'); @endphp
          <tr style="cursor:pointer" data-view-je data-id="{{ $e->id }}">
            <td><b>{{ $e->entry_no }}</b></td>
            <td>{{ $e->entry_date->format('d M Y') }}</td>
            <td>{{ Str::limit($e->description ?? '—', 46) }}</td>
            <td>{{ $e->reference ?? '—' }}</td>
            <td>{{ $e->lines->count() }}</td>
            <td>TZS {{ number_format($total) }}</td>
            <td><span class="badge badge-{{ $e->status==='posted' ? 'success' : 'neutral' }} badge-dotted">{{ ucfirst($e->status) }}</span></td>
            <td>
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="event.stopPropagation();toggleActionMenu('am-je-{{ $e->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-je-{{ $e->id }}">
                  <button type="button" onclick="event.stopPropagation();showEntry({{ $e->id }})">View Lines</button>
                  @if(!$isCommittee)
                  <form method="POST" action="{{ route('accounting.journal.destroy', $e) }}"
                        data-confirm data-confirm-title="Delete this journal entry?"
                        data-confirm-message="{{ $e->entry_no }} will be removed and account balances updated."
                        data-confirm-label="Delete Entry">
                    @csrf @method('DELETE')
                    <button type="submit" class="danger">Delete</button>
                  </form>
                  @endif
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="8"><div class="empty-state"><h3>No journal entries yet</h3><p>Post your first double-entry transaction.</p><a class="btn btn-accent" href="{{ route('accounting.journal.create') }}">+ New Journal Entry</a></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="tf-info">Showing {{ $entries->firstItem() ?? 0 }}–{{ $entries->lastItem() ?? 0 }} of {{ $entries->total() }} entries</span>
      <div class="pagination">{{ $entries->links() }}</div>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="jeDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="jeDrawerTitle">Journal Entry</h3><p id="jeDrawerNo" class="badge badge-neutral badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="info-grid">
        <div class="info-row"><span>Date</span><b id="jeDrawerDate">—</b></div>
        <div class="info-row"><span>Description</span><b id="jeDrawerDesc" style="white-space:normal">—</b></div>
        <div class="info-row"><span>Reference</span><b id="jeDrawerRef">—</b></div>
        <div class="info-row"><span>Status</span><b id="jeDrawerStatus">—</b></div>
        <div class="info-row"><span>Created By</span><b id="jeDrawerBy">—</b></div>
        <div class="info-row"><span>Created At</span><b id="jeDrawerAt">—</b></div>
      </div>

      <div id="jeDrawerDocInfo" style="display:none">
        <div class="payments-head" style="margin-top:18px"><span>Source Document</span></div>
        <div class="info-grid">
          <div class="info-row"><span>Doc No</span><b id="jeDrawerDocNo">—</b></div>
          <div class="info-row"><span>Type</span><b id="jeDrawerDocType">—</b></div>
          <div class="info-row"><span>Party</span><b id="jeDrawerDocParty">—</b></div>
          <div class="info-row"><span>Amount</span><b id="jeDrawerDocAmount">—</b></div>
        </div>
      </div>

      <div class="payments-head" style="margin-top:18px">
        <span>Journal Lines</span><span class="payments-count" id="jeLinesCount">0</span>
      </div>
      <div id="jeLines" class="payments-list"></div>

      <div style="margin-top:14px;display:flex;justify-content:space-between;align-items:center">
        <span class="badge badge-success badge-dotted" id="jeBalancedBadge">Balanced</span>
        <div style="font-size:12px;color:var(--text-tertiary)">
          Dr: <b id="jeDrawerDr">0</b> · Cr: <b id="jeDrawerCr">0</b>
        </div>
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
function showEntry(id){
  var data=window.__jeData[id];
  if(!data) return;
  var rows=data.lines.map(function(l){
    return '<tr><td>'+l.code+'</td><td>'+l.name+'</td><td>'+(l.desc||'')+'</td><td style="text-align:right">'+(l.debit?Number(l.debit).toLocaleString():'—')+'</td><td style="text-align:right">'+(l.credit?Number(l.credit).toLocaleString():'—')+'</td></tr>';
  }).join('');
  document.getElementById('jeDrawerTitle').textContent = data.no;
  document.getElementById('jeDrawerNo').textContent = data.no;
  document.getElementById('jeDrawerDate').textContent = data.date;
  document.getElementById('jeDrawerDesc').textContent = data.desc || '—';
  document.getElementById('jeDrawerRef').textContent = '—';
  document.getElementById('jeDrawerStatus').textContent = '—';
  document.getElementById('jeDrawerBy').textContent = '—';
  document.getElementById('jeDrawerAt').textContent = '—';
  document.getElementById('jeDrawerDr').textContent = Number(data.dr).toLocaleString();
  document.getElementById('jeDrawerCr').textContent = Number(data.cr).toLocaleString();
  document.getElementById('jeLinesCount').textContent = data.lines.length;
  var list = document.getElementById('jeLines');
  list.innerHTML = '';
  data.lines.forEach(function(l){
    var isDr = Number(l.debit) > 0;
    var item = document.createElement('div');
    item.className = 'pay-item';
    item.innerHTML =
      '<div class="pay-ico" style="background:' + (isDr ? 'var(--success-bg)' : 'var(--danger-bg)') + ';color:' + (isDr ? 'var(--success)' : 'var(--red)') + '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>' +
      '<div class="pay-main"><div class="pm-name">' + l.code + ' — ' + l.name + '</div><div class="pm-sub">' + (l.desc || '') + '</div></div>' +
      '<div class="pay-amt" style="text-align:right"><div>' + (isDr ? 'Dr TZS ' + Number(l.debit).toLocaleString() : '') + '</div><div style="color:var(--red);font-size:11px">' + (!isDr && Number(l.credit) > 0 ? 'Cr TZS ' + Number(l.credit).toLocaleString() : '') + '</div></div>';
    list.appendChild(item);
  });
  document.getElementById('jeDrawerDocInfo').style.display = 'none';
  openDrawerById('jeDetailDrawer');
}
window.__jeData={};
@foreach($entries as $e)
window.__jeData[{{ $e->id }}]={
  no:'{{ $e->entry_no }}',
  date:'{{ $e->entry_date->format('d M Y') }}',
  desc:@js($e->description),
  ref:@js($e->reference),
  status:'{{ $e->status }}',
  createdBy:@js($e->created_by),
  createdAt:'{{ $e->created_at?->format('d M Y H:i') }}',
  dr:{{ (float) $e->lines->sum('debit') }},
  cr:{{ (float) $e->lines->sum('credit') }},
  lines:[ @foreach($e->lines as $l){code:'{{ $l->account->code }}',name:@js($l->account->name),desc:@js($l->description),debit:{{ (float) $l->debit }},credit:{{ (float) $l->credit }} }@if(!$loop->last),@endif @endforeach ]
};
@endforeach

document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('[data-view-je]').forEach(function(tr){
    tr.addEventListener('click', function(e){
      if(e.target.closest('a') || e.target.closest('button') || e.target.closest('form')) return;
      var id = tr.dataset.id;
      fetch('{{ url("/accounting/api/journal") }}/' + id)
        .then(function(r){ return r.json(); })
        .then(function(d){
          document.getElementById('jeDrawerTitle').textContent = d.entry.entry_no;
          document.getElementById('jeDrawerNo').textContent = d.entry.entry_no;
          document.getElementById('jeDrawerDate').textContent = d.entry.entry_date;
          document.getElementById('jeDrawerDesc').textContent = d.entry.description || '—';
          document.getElementById('jeDrawerRef').textContent = d.entry.reference || '—';
          document.getElementById('jeDrawerStatus').textContent = d.entry.status ? d.entry.status.charAt(0).toUpperCase() + d.entry.status.slice(1) : '—';
          document.getElementById('jeDrawerBy').textContent = d.entry.created_by || '—';
          document.getElementById('jeDrawerAt').textContent = d.entry.created_at || '—';
          document.getElementById('jeDrawerDr').textContent = Number(d.total_debit).toLocaleString();
          document.getElementById('jeDrawerCr').textContent = Number(d.total_credit).toLocaleString();

          if(d.doc){
            document.getElementById('jeDrawerDocInfo').style.display = '';
            document.getElementById('jeDrawerDocNo').textContent = d.doc.doc_no;
            document.getElementById('jeDrawerDocType').textContent = d.doc.type;
            document.getElementById('jeDrawerDocParty').textContent = d.doc.party;
            document.getElementById('jeDrawerDocAmount').textContent = 'TZS ' + Number(d.doc.amount).toLocaleString();
          } else {
            document.getElementById('jeDrawerDocInfo').style.display = 'none';
          }

          document.getElementById('jeLinesCount').textContent = d.lines.length;
          var list = document.getElementById('jeLines');
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
          openDrawerById('jeDetailDrawer');
        });
    });
  });
});
</script>
@endpush
