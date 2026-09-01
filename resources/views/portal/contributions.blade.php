@extends('layouts.portal')

@section('title', 'Contributions — Member Portal')
@section('content')
<div class="fade-in">
  <h1 style="font-size:20px;font-weight:800;margin:0 0 18px;color:var(--navy-900)">My Contributions</h1>

  <div class="stat-grid">
    <div class="stat-card green"><div class="stat-value">TZS {{ number_format($totalAll) }}</div><div class="stat-label">All Time</div></div>
    @if($fy)
    <div class="stat-card blue"><div class="stat-value">TZS {{ number_format($totalFY) }}</div><div class="stat-label">{{ $fy->name }}</div></div>
    @endif
  </div>

  <div class="portal-card" style="padding:0;overflow:hidden">
    <div style="padding:18px 24px 0"><h2 style="margin:0 0 12px">Contribution History</h2></div>
    <div style="overflow-x:auto">
      <table class="portal-table">
        <thead><tr><th>Date</th><th>Reference</th><th>Method</th><th>Amount (TZS)</th></tr></thead>
        <tbody>
          @forelse($contributions as $c)
          <tr>
            <td>{{ \Carbon\Carbon::parse($c->pay_date)->format('d M Y') }}</td>
            <td>{{ $c->reference ?? $c->receipt_no ?? '—' }}</td>
            <td>{{ ucfirst($c->method ?? $c->payment_method ?? '—') }}</td>
            <td style="font-weight:700">{{ number_format($c->amount) }}</td>
          </tr>
          @empty
          <tr><td colspan="4" style="text-align:center;padding:36px 20px">No contributions recorded yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div style="padding:14px 24px;border-top:1px solid var(--border);font-size:12.5px;color:var(--text-secondary)">
      Showing {{ $contributions->firstItem() ?? 0 }}–{{ $contributions->lastItem() ?? 0 }} of {{ $contributions->total() }}
      <div class="pagination" style="float:right">{{ $contributions->links() }}</div>
    </div>
  </div>
</div>
@endsection