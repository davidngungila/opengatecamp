@extends('layouts.app')

@section('title', 'General Ledger â€” Open Gate Camp Mission')
@section('crumb', 'Finance / Financial Accounting / General Ledger')
@section('page_title', 'General Ledger')

@section('content')
<div class="fade-in">
  <div class="section-head"><div><h2>General Ledger</h2><div class="sub">@if($fy) Period: {{ $fy->name }}. @else All periods. @endif</div></div></div>

  <form class="toolbar" method="GET" action="{{ url('/accounting/ledger') }}">
    <select class="filter-select" name="account" style="min-width:280px" onchange="this.form.submit()">
      <option value="">â€” Select an account â€”</option>
      @foreach($accounts as $a)
        <option value="{{ $a->id }}" {{ $account?->id===$a->id ? 'selected' : '' }}>{{ $a->code }} â€” {{ $a->name }}</option>
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
          <tr>
            <td>{{ $l->entry->entry_date->format('d M Y') }}</td>
            <td><b>{{ $l->entry->entry_no }}</b></td>
            <td>{{ $l->description ?? $l->entry->description ?? 'â€”' }}</td>
            <td style="text-align:right">{{ $l->debit > 0 ? number_format($l->debit, 2) : 'â€”' }}</td>
            <td style="text-align:right">{{ $l->credit > 0 ? number_format($l->credit, 2) : 'â€”' }}</td>
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
@endsection
