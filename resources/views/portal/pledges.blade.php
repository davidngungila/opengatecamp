@extends('layouts.portal')

@section('title', 'Pledges — Member Portal')
@section('content')
<div class="fade-in">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:18px">
    <div>
      <h1 style="font-size:20px;font-weight:800;margin:0 0 4px;color:var(--navy-900)">My Pledges</h1>
      <p style="margin:0;font-size:13px;color:var(--text-secondary)">Make and track your pledges to the camp.</p>
    </div>
  </div>

  <div class="stat-grid">
    <div class="stat-card purple"><div class="stat-value">TZS {{ number_format($totals['pledged']) }}</div><div class="stat-label">Pledged</div></div>
    <div class="stat-card green"><div class="stat-value">TZS {{ number_format($totals['paid']) }}</div><div class="stat-label">Paid</div></div>
    <div class="stat-card orange"><div class="stat-value">TZS {{ number_format($totals['outstanding']) }}</div><div class="stat-label">Outstanding</div></div>
  </div>

  <div class="portal-card">
    <h2>Add Pledge — {{ $currentCamp?->title ?? \App\Models\Setting::get('event.name', 'Open Gate Camp Season 3') }}</h2>
    <form method="POST" action="{{ route('portal.pledges.store') }}" class="portal-form">
      @csrf
      <div class="form-row">
        <div class="field"><label>Amount (TZS)</label><input type="number" min="1" step="1" name="amount" value="{{ old('amount') }}" placeholder="e.g. 50000" required></div>
        <div class="field"><label>Frequency</label><select name="frequency" required>
          <option value="one_time" @if(old('frequency')==='one_time') selected @endif>One-time</option>
          <option value="monthly" @if(old('frequency')==='monthly') selected @endif>Monthly</option>
          <option value="weekly" @if(old('frequency')==='weekly') selected @endif>Weekly</option>
        </select></div>
      </div>
      <div class="form-row">
        <div class="field"><label>Pledge Date</label><input type="date" name="pledge_date" value="{{ old('pledge_date', now()->format('Y-m-d')) }}" required></div>
        <div class="field"><label>Due Date</label><input type="date" name="due_date" value="{{ old('due_date') }}"></div>
      </div>
      <div class="field"><label>Notes</label><textarea name="notes" placeholder="Anything about this pledge...">{{ old('notes') }}</textarea></div>
      <button type="submit" class="btn btn-accent">Add Pledge</button>
    </form>
  </div>

  <div class="portal-card" style="padding:0;overflow:hidden">
    <div style="padding:18px 24px 0">
      <h2 style="margin:0 0 12px">My Pledges</h2>
    </div>
    <div style="overflow-x:auto">
      <table class="portal-table">
        <thead><tr><th>Pledge No</th><th>Event</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Frequency</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
          @forelse($pledges as $p)
          <tr>
            <td><b>{{ $p->pledge_no }}</b></td>
            <td>{{ $p->event?->title ?? '—' }}</td>
            <td>{{ number_format($p->amount) }}</td>
            <td style="color:{{ ($p->paid_amount ?? 0) > 0 ? 'var(--success)' : 'var(--text-tertiary)' }};font-weight:700">{{ number_format($p->paid_amount ?? 0) }}</td>
            <td style="color:{{ $p->getRemainingAttribute() > 0 ? 'var(--warning)' : 'var(--success)' }};font-weight:700">{{ number_format($p->getRemainingAttribute()) }}</td>
            <td>{{ \App\Models\Pledge::frequencies()[$p->frequency] ?? $p->frequency }}</td>
            <td><span class="portal-badge {{ $p->getStatusColor() === 'success' ? 'active' : ($p->getStatusColor() === 'info' ? 'info' : 'pending') }}">{{ $p->getStatusLabel() }}</span></td>
            <td>{{ $p->pledge_date?->format('d M Y') }}</td>
          </tr>
          @empty
          <tr><td colspan="8" style="text-align:center;padding:36px 20px">You have not made any pledges yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div style="padding:14px 24px;border-top:1px solid var(--border);font-size:12.5px;color:var(--text-secondary)">
      Showing {{ $pledges->firstItem() ?? 0 }}–{{ $pledges->lastItem() ?? 0 }} of {{ $pledges->total() }}
      <div class="pagination" style="float:right">{{ $pledges->links() }}</div>
    </div>
  </div>
</div>
@endsection