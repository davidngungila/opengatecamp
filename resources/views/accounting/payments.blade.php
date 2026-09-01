@extends('layouts.app')

@section('title', 'Payments â€” Open Gate Camp Mission')
@section('crumb', 'Finance / Financial Accounting / Payments')
@section('page_title', 'Payments & Expenses')

@php
    $totalPay = (float) $docs->sum('amount');
@endphp

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Payments &amp; Expense Management</h2><div class="sub">@if($fy) Period: {{ $fy->name }}. @else All periods. @endif Every payment posts Dr Expense Â· Cr Cash/Bank.</div></div>
    <button type="button" class="btn btn-accent" data-modal-open="paymentModal">+ Record Payment</button>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Voucher</th><th>Date</th><th>Paid To</th><th>Expense Account</th><th>Paid From</th><th style="text-align:right">Amount (TZS)</th><th>Journal</th><th style="width:60px">Actions</th></tr></thead>
        <tbody>
          @forelse($docs as $i => $d)
          <tr>
            <td><b>{{ $d->doc_no }}</b></td>
            <td>{{ $d->pay_date->format('d M Y') }}</td>
            <td>{{ $d->party }}</td>
            <td><span class="badge badge-danger badge-dotted">{{ $d->categoryAccount?->name }}</span></td>
            <td>{{ $d->moneyAccount?->name }}</td>
            <td style="text-align:right"><b>TZS {{ number_format((float) $d->amount) }}</b></td>
            <td>{{ $d->journalEntry?->entry_no ?? 'â€”' }}</td>
            <td>
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-pay-{{ $d->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-pay-{{ $d->id }}">
                  <a href="{{ route('accounting.ledger', ['account' => $d->category_account_id]) }}">View Expense Ledger</a>
                  <form method="POST" action="{{ route('accounting.documents.destroy', $d) }}"
                        data-confirm data-confirm-title="Delete this payment?"
                        data-confirm-message="{{ $d->doc_no }} and its linked journal entry will be removed."
                        data-confirm-label="Delete Payment">
                    @csrf @method('DELETE')
                    <button type="submit" class="danger">Delete</button>
                  </form>
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="8"><div class="empty-state"><h3>No payments yet</h3><p>Record the first expense payment.</p><button type="button" class="btn btn-accent" data-modal-open="paymentModal">+ Record Payment</button></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="tf-info">Showing {{ $docs->firstItem() ?? 0 }}â€“{{ $docs->lastItem() ?? 0 }} of {{ $docs->total() }} payments Â· Total TZS {{ number_format($totalPay) }}</span>
      <div class="pagination">{{ $docs->links() }}</div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="paymentModal">
  <div class="modal-box md">
    <div class="modal-head">
      <div><h3>Record Payment</h3><p>Dr Expense Â· Cr Cash/Bank â€” auto-balanced</p></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('accounting.payments.store') }}">
      @csrf
      <input type="hidden" name="type" value="payment">
      <div class="modal-body">
        <div class="form-grid">
          <div class="field"><label>Date *</label><input type="date" name="pay_date" value="{{ old('pay_date', now()->toDateString()) }}" required></div>
          <div class="field"><label>Amount (TZS) *</label><input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00"></div>
          <div class="field full"><label>Paid To *</label><input name="party" required placeholder="Vendor, payee, or beneficiary"></div>
          <div class="field full"><label>Expense Account *</label>
            <select name="category_account_id" required>
              @foreach($categoryAccounts as $ca)<option value="{{ $ca->id }}">{{ $ca->code }} â€” {{ $ca->name }}</option>@endforeach
            </select>
          </div>
          <div class="field"><label>Paid From *</label>
            <select name="money_account_id" required>
              @foreach($moneyAccounts as $ma)<option value="{{ $ma->id }}">{{ $ma->code }} â€” {{ $ma->name }}</option>@endforeach
            </select>
          </div>
          <div class="field"><label>Method</label>
            <select name="method"><option value="cash">Cash</option><option value="bank">Bank</option><option value="mobile">Mobile Money</option></select>
          </div>
          <div class="field full"><label>Description</label><textarea name="description" rows="2"></textarea></div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-accent"
                data-confirm data-confirm-title="Record this payment?"
                data-confirm-message="A balanced journal entry will be posted to the ledger."
                data-confirm-label="Post Payment">Save Payment</button>
      </div>
    </form>
  </div>
</div>
@endsection
