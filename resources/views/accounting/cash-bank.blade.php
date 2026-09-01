@extends('layouts.app')

@section('title', 'Cash & Bank â€” Open Gate Camp Mission')
@section('crumb', 'Finance / Financial Accounting / Cash & Bank')
@section('page_title', 'Cash & Bank Management')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Cash &amp; Bank Management</h2><div class="sub">@if($fy) Period: {{ $fy->name }}. @else All periods. @endif Real-time cash position across all money accounts.</div></div>
  </div>

  <div class="stat-grid" style="grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));margin-bottom:24px">
    @foreach($balances as $b)
    <div class="glass-card" style="padding:18px 20px;text-align:center">
      <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:6px">{{ $b['account']->code }} â€” {{ $b['account']->name }}</div>
      <div style="font-size:26px;font-weight:700;color:var(--blue-accent)">TZS {{ number_format($b['balance']) }}</div>
    </div>
    @endforeach
    <div class="glass-card" style="padding:18px 20px;text-align:center;border:2px solid var(--blue-accent)">
      <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:6px">Total Cash Position</div>
      <div style="font-size:28px;font-weight:800;color:var(--blue-accent)">TZS {{ number_format($totalCash) }}</div>
    </div>
  </div>

  <div class="two-col" style="grid-template-columns:1fr 1fr">
    <div class="glass-card">
      <h2 style="font-size:14px;margin:0 0 14px">Recent Cash Movements</h2>
      @forelse($movements as $m)
      <div class="mini-row">
        <div class="m-ico" style="background:{{ $m->debit > 0 ? 'var(--green-light)' : '#fef2f2' }};color:{{ $m->debit > 0 ? 'var(--green-accent)' : 'var(--red)' }}">
          {{ $m->debit > 0 ? 'IN' : 'OUT' }}
        </div>
        <div class="m-body">
          <p>{{ $m->entry->entry_no }} â€” {{ Str::limit($m->entry->description, 40) }}</p>
          <span>{{ $m->account->code }} Â· {{ $m->entry->entry_date->format('d M Y') }}</span>
        </div>
        <span style="font-weight:700;color:{{ $m->debit > 0 ? 'var(--green-accent)' : 'var(--red)' }}">
          {{ $m->debit > 0 ? '+' : '-' }} TZS {{ number_format(max($m->debit, $m->credit)) }}
        </span>
      </div>
      @empty
      <div class="empty-state" style="padding:30px 16px"><p>No movements recorded yet.</p></div>
      @endforelse
    </div>

    <div class="glass-card">
      <h2 style="font-size:14px;margin:0 0 14px">Quick Actions</h2>
      <div style="display:flex;flex-direction:column;gap:10px">
        <a href="{{ route('accounting.offerings') }}" class="btn btn-secondary" style="justify-content:center;text-align:center">+ Record Receipt</a>
        <a href="{{ route('accounting.payments') }}" class="btn btn-secondary" style="justify-content:center;text-align:center">+ Record Payment</a>
        <a href="{{ route('accounting.reconciliation') }}" class="btn btn-secondary" style="justify-content:center;text-align:center">Bank Reconciliation</a>
        <a href="{{ route('accounting.ledger') }}" class="btn btn-secondary" style="justify-content:center;text-align:center">View Ledger</a>
      </div>
    </div>
  </div>
</div>
@endsection
