@extends('layouts.app')

@section('title', 'Transaction History — Open Gate Camp Mission')
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
            @endphp
            <tr>
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
@endsection
