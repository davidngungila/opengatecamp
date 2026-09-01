@extends('layouts.app')

@section('title', 'Journal Entries — Open Gate Camp Mission')
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
          <tr>
            <td><b>{{ $e->entry_no }}</b></td>
            <td>{{ $e->entry_date->format('d M Y') }}</td>
            <td>{{ Str::limit($e->description ?? '—', 46) }}</td>
            <td>{{ $e->reference ?? '—' }}</td>
            <td>{{ $e->lines->count() }}</td>
            <td>TZS {{ number_format($total) }}</td>
            <td><span class="badge badge-{{ $e->status==='posted' ? 'success' : 'neutral' }} badge-dotted">{{ ucfirst($e->status) }}</span></td>
            <td>
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-je-{{ $e->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-je-{{ $e->id }}">
                  <button type="button" onclick="showEntry({{ $e->id }})">View Lines</button>
                  <form method="POST" action="{{ route('accounting.journal.destroy', $e) }}"
                        data-confirm data-confirm-title="Delete this journal entry?"
                        data-confirm-message="{{ $e->entry_no }} will be removed and account balances updated."
                        data-confirm-label="Delete Entry">
                    @csrf @method('DELETE')
                    <button type="submit" class="danger">Delete</button>
                  </form>
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
      <span class="tf-info">Showing {{ $entries->firstItem() ?? 0 }}“{{ $entries->lastItem() ?? 0 }} of {{ $entries->total() }} entries</span>
      <div class="pagination">{{ $entries->links() }}</div>
    </div>
  </div>
</div>

<div id="entryModalRoot"></div>

@endsection

@push('scripts')
<script>
function showEntry(id){
  var data=window.__jeData[id];
  if(!data) return;
  var rows=data.lines.map(function(l){
    return '<tr><td>'+l.code+'</td><td>'+l.name+'</td><td>'+(l.desc||'')+'</td><td style="text-align:right">'+(l.debit?Number(l.debit).toLocaleString():'—')+'</td><td style="text-align:right">'+(l.credit?Number(l.credit).toLocaleString():'—')+'</td></tr>';
  }).join('');
  document.getElementById('entryModalRoot').innerHTML=
    '<div class="modal-overlay open" id="jeView"><div class="modal-box md"><div class="modal-head"><div><h3>'+data.no+'</h3><p>'+data.date+' — '+(data.desc||'')+'</p></div>'+
    '<button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button></div>'+
    '<div class="modal-body"><div class="table-scroll"><table class="data-table" style="min-width:0"><thead><tr><th>Code</th><th>Account</th><th>Description</th><th style="text-align:right">Debit</th><th style="text-align:right">Credit</th></tr></thead><tbody>'+rows+'</tbody>'+
    '<tfoot><tr style="font-weight:800;background:var(--blue-light)"><td colspan="3">TOTALS</td><td style="text-align:right">'+Number(data.dr).toLocaleString()+'</td><td style="text-align:right">'+Number(data.cr).toLocaleString()+'</td></tr></tfoot></table></div></div>'+
    '<div class="modal-foot"><span class="foot-left">Balanced <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px"><path d="M20 6L9 17l-5-5"/></svg></span><button type="button" class="btn btn-secondary" data-modal-close>Close</button></div></div></div>';
}
window.__jeData={};
@foreach($entries as $e)
window.__jeData[{{ $e->id }}]={
  no:'{{ $e->entry_no }}',
  date:'{{ $e->entry_date->format('d M Y') }}',
  desc:@js($e->description),
  dr:{{ (float) $e->lines->sum('debit') }},
  cr:{{ (float) $e->lines->sum('credit') }},
  lines:[ @foreach($e->lines as $l){code:'{{ $l->account->code }}',name:@js($l->account->name),desc:@js($l->description),debit:{{ (float) $l->debit }},credit:{{ (float) $l->credit }} }@if(!$loop->last),@endif @endforeach ]
};
@endforeach
</script>
@endpush
