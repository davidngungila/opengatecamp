@extends('layouts.app')

@section('title', 'Income Statement â€” Open Gate Camp Mission')
@section('crumb', 'Finance / Financial Accounting / Income Statement')
@section('page_title', 'Income Statement')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Income Statement</h2><div class="sub">@if($fy) For {{ $fy->name }}. @else For all periods. @endif Accrual, double-entry basis.</div></div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">Print</button>
  </div>

  <div class="two-col" style="grid-template-columns:1fr 1fr;margin-bottom:0">
    <div class="solid-card">
      <h2 style="font-size:15px;margin:0 0 12px">Income</h2>
      @forelse($income['accounts'] as $r)
        <div class="info-row"><span>{{ $r['account']->code }} â€” {{ $r['account']->name }}</span><b>TZS {{ number_format($r['amount'], 2) }}</b></div>
      @empty
        <p class="text-muted" style="font-size:13px">No income recorded.</p>
      @endforelse
      <div class="info-row" style="border-top:2px solid var(--border-strong);margin-top:8px"><span style="font-weight:800">Total Income</span><b>TZS {{ number_format($totalIncome, 2) }}</b></div>
    </div>

    <div class="solid-card">
      <h2 style="font-size:15px;margin:0 0 12px">Expenses</h2>
      @forelse($expense['accounts'] as $r)
        <div class="info-row"><span>{{ $r['account']->code }} â€” {{ $r['account']->name }}</span><b>TZS {{ number_format($r['amount'], 2) }}</b></div>
      @empty
        <p class="text-muted" style="font-size:13px">No expenses recorded.</p>
      @endforelse
      <div class="info-row" style="border-top:2px solid var(--border-strong);margin-top:8px"><span style="font-weight:800">Total Expenses</span><b>TZS {{ number_format($totalExpense, 2) }}</b></div>
    </div>
  </div>

  <div class="glass-card" style="margin-top:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
    <h2 style="font-size:16px;margin:0">{{ ($totalIncome - $totalExpense) >= 0 ? 'Surplus' : 'Deficit' }} for the period</h2>
    <div style="font-size:24px;font-weight:800;color:{{ ($totalIncome - $totalExpense) >= 0 ? 'var(--success)' : 'var(--danger)' }}">
      TZS {{ number_format($totalIncome - $totalExpense, 2) }}
    </div>
  </div>
</div>
@endsection
