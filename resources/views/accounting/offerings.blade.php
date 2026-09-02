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
          <tr>
            <td><b>{{ $d->doc_no }}</b></td>
            <td>{{ $d->pay_date->format('d M Y') }}</td>
            <td>{{ $d->party }}</td>
            <td><span class="badge badge-success badge-dotted">{{ $d->categoryAccount?->name }}</span></td>
            <td>{{ ucfirst($d->method) }}</td>
            <td style="text-align:right"><b>TZS {{ number_format((float) $d->amount) }}</b></td>
            <td>{{ $d->journalEntry?->entry_no ?? '—' }}</td>
            <td>
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-rcp-{{ $d->id }}')">
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
