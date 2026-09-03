@extends('layouts.app')

@section('title', 'Offerings, Contributions & Donations')
@section('crumb', 'Finance / Financial Accounting / Offerings & Donations')
@section('page_title', 'Offerings, Contributions & Donations')

@php
    $presetCategories = [
        ['4000','Tithes'], ['4010','Offerings'], ['4020','Donations'],
        ['4030','Fundraising'], ['4040','Other Income'],
    ];
    $totalIn = (float) $docs->getCollection()->sum(fn($d) => $d->type === 'receipt' ? $d->amount : 0);
@endphp

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Offerings, Contributions &amp; Donations</h2><div class="sub">@if($fy) Period: {{ $fy->name }}. @else All periods. @endif Every receipt posts a balanced double entry automatically.</div></div>
    <button type="button" class="btn btn-accent" data-modal-open="receiptModal">+ Record Receipt</button>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Doc No</th><th>Date</th><th>From / Source</th><th>Category</th><th>Method</th><th style="text-align:right">Amount (TZS)</th><th>Journal</th><th style="width:60px">Actions</th></tr></thead>
        <tbody>
          @forelse($docs as $i => $d)
          <tr style="cursor:pointer" data-view-receipt data-id="{{ $d->id }}"
              data-doc-no="{{ $d->doc_no }}" data-date="{{ $d->pay_date->format('d M Y') }}"
              data-party="{{ $d->party }}" data-category="{{ $d->categoryAccount?->name }}"
              data-method="{{ ucfirst($d->method) }}" data-amount="{{ number_format((float) $d->amount) }}"
              data-reference="{{ $d->reference ?? '—' }}" data-description="{{ $d->description ?? '—' }}"
              data-journal="{{ $d->journalEntry?->entry_no ?? '—' }}">
            <td><b>{{ $d->doc_no }}</b></td>
            <td>{{ $d->pay_date->format('d M Y') }}</td>
            <td>{{ $d->party }}</td>
            <td><span class="badge badge-success badge-dotted">{{ $d->categoryAccount?->name }}</span></td>
            <td>{{ ucfirst($d->method) }}</td>
            <td style="text-align:right"><b>TZS {{ number_format((float) $d->amount) }}</b></td>
            <td>{{ $d->journalEntry?->entry_no ?? '—' }}</td>
            <td>
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="event.stopPropagation();toggleActionMenu('am-rcp-{{ $d->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-rcp-{{ $d->id }}">
                  <a href="{{ route('accounting.ledger', ['account' => $d->category_account_id]) }}">View Category Ledger</a>
                  @if(!$isCommittee)
                  <form method="POST" action="{{ route('accounting.documents.destroy', $d) }}"
                        data-confirm data-confirm-title="Delete this receipt?"
                        data-confirm-message="{{ $d->doc_no }} and its linked journal entry will be removed."
                        data-confirm-label="Delete Receipt">
                    @csrf @method('DELETE')
                    <button type="submit" class="danger">Delete</button>
                  </form>
                  @endif
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="8"><div class="empty-state"><h3>No receipts yet</h3><p>Record the first offering or donation.</p><button type="button" class="btn btn-accent" data-modal-open="receiptModal">+ Record Receipt</button></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="tf-info">Showing {{ $docs->firstItem() ?? 0 }}–{{ $docs->lastItem() ?? 0 }} of {{ $docs->total() }} receipts</span>
      <div class="pagination">{{ $docs->links() }}</div>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="rcpDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Receipt Details</h3><p id="rcpDrawerDoc" class="badge badge-success badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="info-grid">
        <div class="info-row"><span>Date</span><b id="rcpDrawerDate">—</b></div>
        <div class="info-row"><span>From / Source</span><b id="rcpDrawerParty">—</b></div>
        <div class="info-row"><span>Category</span><b id="rcpDrawerCategory">—</b></div>
        <div class="info-row"><span>Method</span><b id="rcpDrawerMethod">—</b></div>
        <div class="info-row"><span>Amount</span><b id="rcpDrawerAmount" style="color:var(--success)">—</b></div>
        <div class="info-row"><span>Reference</span><b id="rcpDrawerReference" style="white-space:normal">—</b></div>
        <div class="info-row full"><span>Description</span><b id="rcpDrawerDescription" style="white-space:normal">—</b></div>
        <div class="info-row"><span>Journal Entry</span><b id="rcpDrawerJournal">—</b></div>
      </div>

      <div class="payments-head" style="margin-top:18px">
        <span>Journal Lines</span><span class="payments-count" id="rcpLinesCount">0</span>
      </div>
      <div id="rcpLines" class="payments-list"></div>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
      <button type="button" class="btn btn-accent" id="rcpReceiptBtn" style="display:none">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>
        Download Receipt (PDF)
      </button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="receiptModal">
  <div class="modal-box md">
    <div class="modal-head">
      <div><h3>Record Receipt</h3><p>Dr Cash/Bank · Cr Income — auto-balanced</p></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('accounting.offerings.store') }}">
      @csrf
      <input type="hidden" name="type" value="receipt">
      <div class="modal-body">
        <div class="form-grid">
          <div class="field"><label>Date *</label><input type="date" name="pay_date" value="{{ old('pay_date', now()->toDateString()) }}" required></div>
          <div class="field"><label>Amount (TZS) *</label><input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00"></div>
          <div class="field full"><label>From / Source *</label><input name="party" required placeholder="e.g. Sunday congregation, donor name"></div>
          <div class="field full"><label>Income Category *</label>
            <select name="category_account_id" required>
              @foreach($presetCategories as [$code,$label])
                @php $acc = \App\Models\Account::where('code', $code)->first(); @endphp
                @if($acc)<option value="{{ $acc->id }}">{{ $code }} — {{ $label }}</option>@endif
              @endforeach
              @foreach($categoryAccounts as $ca)
                @if(! in_array($ca->code, ['4000','4010','4020','4030','4040']))
                  <option value="{{ $ca->id }}">{{ $ca->code }} — {{ $ca->name }}</option>
                @endif
              @endforeach
            </select>
          </div>
          <div class="field"><label>Received Into *</label>
            <select name="money_account_id" required>
              @foreach($moneyAccounts as $ma)<option value="{{ $ma->id }}">{{ $ma->code }} — {{ $ma->name }}</option>@endforeach
            </select>
          </div>
          <div class="field"><label>Method</label>
            <select name="method"><option value="cash">Cash</option><option value="bank">Bank</option><option value="mobile">Mobile Money</option></select>
          </div>
          <div class="field full"><label>Reference</label><input name="reference" placeholder="e.g. MP-88121 / cheque no"></div>
          <div class="field full"><label>Description</label><textarea name="description" rows="2"></textarea></div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-accent"
                data-confirm data-confirm-title="Record this receipt?"
                data-confirm-message="A balanced journal entry will be posted to the ledger."
                data-confirm-label="Post Receipt">Save Receipt</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('[data-view-receipt]').forEach(function(tr){
    tr.addEventListener('click', function(e){
      if(e.target.closest('a') || e.target.closest('button') || e.target.closest('form')) return;
      var id = tr.dataset.id;
      fetch('{{ url("/accounting/api/receipts") }}/' + id)
        .then(function(r){ return r.json(); })
        .then(function(d){
          document.getElementById('rcpDrawerDoc').textContent = d.doc_no || '—';
          document.getElementById('rcpDrawerDate').textContent = d.pay_date || '—';
          document.getElementById('rcpDrawerParty').textContent = d.party || '—';
          document.getElementById('rcpDrawerCategory').textContent = (d.category_account ? d.category_account.code + ' — ' + d.category_account.name : '—');
          document.getElementById('rcpDrawerMethod').textContent = d.method || '—';
          document.getElementById('rcpDrawerAmount').textContent = 'TZS ' + Number(d.amount || 0).toLocaleString();
          document.getElementById('rcpDrawerReference').textContent = d.reference || '—';
          document.getElementById('rcpDrawerDescription').textContent = d.description || '—';
          document.getElementById('rcpDrawerJournal').textContent = d.journal_entry ? d.journal_entry.entry_no : '—';

          var lines = d.journal_entry ? d.journal_entry.lines : [];
          document.getElementById('rcpLinesCount').textContent = lines.length;
          var list = document.getElementById('rcpLines');
          list.innerHTML = '';
          if(lines.length === 0){
            list.innerHTML = '<div class="pay-empty">No lines</div>';
          } else {
            lines.forEach(function(l){
              var isDr = Number(l.debit) > 0;
              var item = document.createElement('div');
              item.className = 'pay-item';
              item.innerHTML =
                '<div class="pay-ico" style="background:' + (isDr ? 'var(--success-bg)' : 'var(--danger-bg)') + ';color:' + (isDr ? 'var(--success)' : 'var(--danger)') + '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>' +
                '<div class="pay-main"><div class="pm-name">' + (l.account ? l.account.code + ' — ' + l.account.name : '—') + '</div><div class="pm-sub">' + (l.description || '') + '</div></div>' +
                '<div class="pay-amt" style="text-align:right"><div>' + (isDr ? 'Dr TZS ' + Number(l.debit).toLocaleString() : '') + '</div><div style="color:var(--red);font-size:11px">' + (!isDr && Number(l.credit) > 0 ? 'Cr TZS ' + Number(l.credit).toLocaleString() : '') + '</div></div>';
              list.appendChild(item);
            });
          }

          var receiptBtn = document.getElementById('rcpReceiptBtn');
          if(d.journal_entry){
            receiptBtn.style.display = 'inline-flex';
            receiptBtn.onclick = function(){ window.open('{{ url("/accounting/transactions") }}/' + d.journal_entry_id + '/receipt', '_blank'); };
          } else {
            receiptBtn.style.display = 'none';
          }

          openDrawerById('rcpDetailDrawer');
        });
    });
  });
});
</script>
@endpush
