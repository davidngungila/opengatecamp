@extends('layouts.app')

@section('title', 'Payments — OpenGate Camp Connect')
@section('crumb', 'Finance / Financial Accounting / Payments')
@section('page_title', 'Payments & Expenses')

@php
    $totalPay = (float) $docs->sum('amount');
@endphp

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Payments &amp; Expense Management</h2><div class="sub">@if($fy) Period: {{ $fy->name }}. @else All periods. @endif Every payment posts Dr Expense · Cr Cash/Bank.</div></div>
    <button type="button" class="btn btn-accent" data-drawer-open="paymentModal">+ Record Payment</button>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Voucher</th><th>Date</th><th>Paid To</th><th>Expense Account</th><th>Paid From</th><th style="text-align:right">Amount (TZS)</th><th>Journal</th><th style="width:60px">Actions</th></tr></thead>
        <tbody>
          @forelse($docs as $i => $d)
          <tr style="cursor:pointer" data-view-payment data-id="{{ $d->id }}">
            <td><b>{{ $d->doc_no }}</b></td>
            <td>{{ $d->pay_date->format('d M Y') }}</td>
            <td>{{ $d->party }}</td>
            <td><span class="badge badge-danger badge-dotted">{{ $d->categoryAccount?->name }}</span></td>
            <td>{{ $d->moneyAccount?->name }}</td>
            <td style="text-align:right"><b>TZS {{ number_format((float) $d->amount) }}</b></td>
            <td>{{ $d->journalEntry?->entry_no ?? '—' }}</td>
            <td>
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="event.stopPropagation();toggleActionMenu('am-pay-{{ $d->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-pay-{{ $d->id }}">
                  <a href="{{ route('accounting.ledger', ['account' => $d->category_account_id]) }}">View Expense Ledger</a>
                  @if(!$isCommittee)
                  <form method="POST" action="{{ route('accounting.documents.destroy', $d) }}"
                        data-confirm data-confirm-title="Delete this payment?"
                        data-confirm-message="{{ $d->doc_no }} and its linked journal entry will be removed."
                        data-confirm-label="Delete Payment">
                    @csrf @method('DELETE')
                    <button type="submit" class="danger">Delete</button>
                  </form>
                  @endif
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="8"><div class="empty-state"><h3>No payments yet</h3><p>Record the first expense payment.</p><button type="button" class="btn btn-accent" data-drawer-open="paymentModal">+ Record Payment</button></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="tf-info">Showing {{ $docs->firstItem() ?? 0 }}–{{ $docs->lastItem() ?? 0 }} of {{ $docs->total() }} payments · Total TZS {{ number_format($totalPay) }}</span>
      <div class="pagination">{{ $docs->links() }}</div>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="payDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Payment Details</h3><p id="payDrawerDoc" class="badge badge-danger badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="info-grid">
        <div class="info-row"><span>Date</span><b id="payDrawerDate">—</b></div>
        <div class="info-row"><span>Paid To</span><b id="payDrawerParty">—</b></div>
        <div class="info-row"><span>Expense Account</span><b id="payDrawerCategory">—</b></div>
        <div class="info-row"><span>Paid From</span><b id="payDrawerMoney">—</b></div>
        <div class="info-row"><span>Amount</span><b id="payDrawerAmount" style="color:var(--danger)">—</b></div>
        <div class="info-row"><span>Method</span><b id="payDrawerMethod">—</b></div>
        <div class="info-row full"><span>Description</span><b id="payDrawerDescription" style="white-space:normal">—</b></div>
        <div class="info-row"><span>Journal Entry</span><b id="payDrawerJournal">—</b></div>
      </div>

      <div class="payments-head" style="margin-top:18px">
        <span>Journal Lines</span><span class="payments-count" id="payLinesCount">0</span>
      </div>
      <div id="payLines" class="payments-list"></div>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="paymentModal">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Record Payment</h3><p>Dr Expense · Cr Cash/Bank — auto-balanced</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('accounting.payments.store') }}">
      @csrf
      <input type="hidden" name="type" value="payment">
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field"><label>Date *</label><input type="date" name="pay_date" value="{{ old('pay_date', now()->toDateString()) }}" required></div>
          <div class="field"><label>Amount (TZS) *</label><input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00"></div>
          <div class="field full"><label>Paid To *</label><input name="party" required placeholder="Vendor, payee, or beneficiary"></div>
          <div class="field full"><label>Expense Account *</label>
            <select name="category_account_id" required>
              @foreach($categoryAccounts as $ca)<option value="{{ $ca->id }}">{{ $ca->code }} — {{ $ca->name }}</option>@endforeach
            </select>
          </div>
          <div class="field"><label>Paid From *</label>
            <select name="money_account_id" required>
              @foreach($moneyAccounts as $ma)<option value="{{ $ma->id }}">{{ $ma->code }} — {{ $ma->name }}</option>@endforeach
            </select>
          </div>
          <div class="field"><label>Method</label>
            <select name="method"><option value="cash">Cash</option><option value="bank">Bank</option><option value="mobile">Mobile Money</option></select>
          </div>
          <div class="field full"><label>Description</label><textarea name="description" rows="2"></textarea></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent"
                data-confirm data-confirm-title="Record this payment?"
                data-confirm-message="A balanced journal entry will be posted to the ledger."
                data-confirm-label="Post Payment">Save Payment</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('[data-view-payment]').forEach(function(tr){
    tr.addEventListener('click', function(e){
      if(e.target.closest('a') || e.target.closest('button') || e.target.closest('form')) return;
      var id = tr.dataset.id;
      fetch('{{ url("/accounting/api/payments") }}/' + id)
        .then(function(r){ return r.json(); })
        .then(function(d){
          document.getElementById('payDrawerDoc').textContent = d.doc_no || '—';
          document.getElementById('payDrawerDate').textContent = d.pay_date || '—';
          document.getElementById('payDrawerParty').textContent = d.party || '—';
          document.getElementById('payDrawerCategory').textContent = (d.category_account ? d.category_account.code + ' — ' + d.category_account.name : '—');
          document.getElementById('payDrawerMoney').textContent = (d.money_account ? d.money_account.code + ' — ' + d.money_account.name : '—');
          document.getElementById('payDrawerAmount').textContent = 'TZS ' + Number(d.amount || 0).toLocaleString();
          document.getElementById('payDrawerMethod').textContent = d.method || '—';
          document.getElementById('payDrawerDescription').textContent = d.description || '—';
          document.getElementById('payDrawerJournal').textContent = d.journal_entry ? d.journal_entry.entry_no : '—';

          var lines = d.journal_entry ? d.journal_entry.lines : [];
          document.getElementById('payLinesCount').textContent = lines.length;
          var list = document.getElementById('payLines');
          list.innerHTML = '';
          if(lines.length === 0){
            list.innerHTML = '<div class="pay-empty">No lines</div>';
          } else {
            lines.forEach(function(l){
              var isDr = Number(l.debit) > 0;
              var item = document.createElement('div');
              item.className = 'pay-item';
              item.innerHTML =
                '<div class="pay-ico" style="background:' + (isDr ? 'var(--danger-bg)' : 'var(--success-bg)') + ';color:' + (isDr ? 'var(--danger)' : 'var(--success)') + '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>' +
                '<div class="pay-main"><div class="pm-name">' + (l.account ? l.account.code + ' — ' + l.account.name : '—') + '</div><div class="pm-sub">' + (l.description || '') + '</div></div>' +
                '<div class="pay-amt" style="text-align:right"><div>' + (isDr ? 'Dr TZS ' + Number(l.debit).toLocaleString() : '') + '</div><div style="color:var(--red);font-size:11px">' + (!isDr && Number(l.credit) > 0 ? 'Cr TZS ' + Number(l.credit).toLocaleString() : '') + '</div></div>';
              list.appendChild(item);
            });
          }

          openDrawerById('payDetailDrawer');
        });
    });
  });
});
</script>
@endpush
