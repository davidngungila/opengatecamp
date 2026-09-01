@extends('layouts.app')

@section('title', 'Trial Balance — Open Gate Camp Mission')
@section('crumb', 'Finance / Financial Accounting / Trial Balance')
@section('page_title', 'Trial Balance')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Trial Balance</h2><div class="sub">@if($fy) Period: {{ $fy->name }}. @else All periods. @endif Debits must equal credits.</div></div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">Print</button>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table" style="min-width:0">
        <thead><tr><th>Code</th><th>Account</th><th>Type</th><th style="text-align:right">Debit (TZS)</th><th style="text-align:right">Credit (TZS)</th></tr></thead>
        <tbody>
          @forelse($rows as $r)
          <tr>
            <td>{{ $r['account']->code }}</td>
            <td>{{ $r['account']->name }}</td>
            <td><span class="badge badge-neutral badge-dotted">{{ ucfirst($r['account']->type) }}</span></td>
            <td style="text-align:right">{{ $r['debit'] > 0 ? number_format($r['debit'], 2) : '—' }}</td>
            <td style="text-align:right">{{ $r['credit'] > 0 ? number_format($r['credit'], 2) : '—' }}</td>
          </tr>
          @empty
          <tr><td colspan="5"><div class="empty-state"><h3>No postings yet</h3><p>Post journal entries to build the trial balance.</p></div></td></tr>
          @endforelse
        </tbody>
        <tfoot>
          <tr style="font-weight:800;background:var(--blue-light)">
            <td colspan="3">TOTALS</td>
            <td style="text-align:right">TZS {{ number_format($totals['debit'], 2) }}</td>
            <td style="text-align:right">TZS {{ number_format($totals['credit'], 2) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  @if($totals['debit'] != $totals['credit'])
    <p class="badge badge-danger badge-dotted" style="margin-top:10px">Out of balance by TZS {{ number_format(abs($totals['debit'] - $totals['credit']), 2) }}</p>
  @else
    <p class="badge badge-success badge-dotted" style="margin-top:10px">Balanced <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px"><path d="M20 6L9 17l-5-5"/></svg></p>
  @endif
</div>
@endsection
