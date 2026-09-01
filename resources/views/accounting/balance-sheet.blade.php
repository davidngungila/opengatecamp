@extends('layouts.app')

@section('title', 'Balance Sheet â€” Open Gate Camp Mission')
@section('crumb', 'Finance / Financial Accounting / Balance Sheet')
@section('page_title', 'Balance Sheet')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Balance Sheet</h2><div class="sub">As of {{ $asOf }}. @if($fy) {{ $fy->name }}. @endif</div></div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">Print</button>
  </div>

  <div class="two-col" style="grid-template-columns:1fr 1fr;margin-bottom:0">
    <div class="solid-card">
      <h2 style="font-size:15px;margin:0 0 12px">Assets</h2>
      @forelse($assets['accounts'] as $r)
        <div class="info-row"><span>{{ $r['account']->code }} â€” {{ $r['account']->name }}</span><b>TZS {{ number_format($r['amount'], 2) }}</b></div>
      @empty
        <p class="text-muted" style="font-size:13px">No asset accounts with activity.</p>
      @endforelse
      <div class="info-row" style="border-top:2px solid var(--border-strong);margin-top:8px"><span style="font-weight:800">Total Assets</span><b>TZS {{ number_format($totals['assets'], 2) }}</b></div>
    </div>

    <div>
      <div class="solid-card" style="margin-bottom:18px">
        <h2 style="font-size:15px;margin:0 0 12px">Liabilities</h2>
        @forelse($liabilities['accounts'] as $r)
          <div class="info-row"><span>{{ $r['account']->code }} â€” {{ $r['account']->name }}</span><b>TZS {{ number_format($r['amount'], 2) }}</b></div>
        @empty
          <p class="text-muted" style="font-size:13px">No liability accounts with activity.</p>
        @endforelse
        <div class="info-row" style="border-top:2px solid var(--border-strong);margin-top:8px"><span style="font-weight:800">Total Liabilities</span><b>TZS {{ number_format($totals['liabilities'], 2) }}</b></div>
      </div>

      <div class="solid-card">
        <h2 style="font-size:15px;margin:0 0 12px">Equity</h2>
        @forelse($equity['accounts'] as $r)
          <div class="info-row"><span>{{ $r['account']->code }} â€” {{ $r['account']->name }}</span><b>TZS {{ number_format($r['amount'], 2) }}</b></div>
        @empty
          <p class="text-muted" style="font-size:13px">No equity accounts with activity.</p>
        @endforelse
        <div class="info-row"><span>Surplus / (Deficit) for period</span><b>TZS {{ number_format($netResult, 2) }}</b></div>
        <div class="info-row" style="border-top:2px solid var(--border-strong);margin-top:8px"><span style="font-weight:800">Total Equity</span><b>TZS {{ number_format($totals['equity'], 2) }}</b></div>
      </div>

      <div class="glass-card" style="margin-top:14px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <span style="font-weight:800">Liabilities + Equity</span>
        <b>TZS {{ number_format($totals['liabilities'] + $totals['equity'], 2) }}</b>
      </div>
    </div>
  </div>
</div>
@endsection
