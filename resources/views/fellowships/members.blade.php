@extends('layouts.app')

@section('title', $fellowship->name.' — Delegation — OpenGate Camp Connect')
@section('crumb', 'Events / Fellowships / '.$fellowship->name.' Delegation')
@section('page_title', $fellowship->name.' Delegation')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div>
      <h2>{{ $fellowship->name }} — Delegation</h2>
      <div class="sub">
        <a href="{{ route('fellowships.index') }}" style="color:var(--blue-accent);text-decoration:none">← Back to Fellowships</a>
        @if($fellowship->university) · {{ $fellowship->university }}@endif
        · {{ $stats['registered'] }} registered · {{ $stats['confirmed'] }} confirmed · {{ $stats['attended'] }} attended
      </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;border-radius:10px;padding:8px 16px;font-size:13px;font-weight:700">Paid TZS {{ number_format($stats['paid']) }}</div>
    </div>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Member</th><th>Pickup</th><th>Paid (TZS)</th><th>Balance</th><th>Status</th><th>Arrived</th><th>Registered</th></tr></thead>
        <tbody>
          @forelse($attendees as $a)
          @php
            $bal = ($a->fee_amount !== null) ? max(0, $a->fee_amount - ($a->amount_paid ?? 0)) : null;
          @endphp
          <tr>
            <td>
              <div class="cell-user">
                <div class="cell-avatar">{{ collect(explode(' ', $a->name ?? '?'))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('') }}</div>
                <div>
                  <div class="cu-name">{{ $a->name ?? '—' }}</div>
                  <div class="cu-sub">{{ $a->phone }}@if($a->member?->member_no) · {{ $a->member->member_no }}@endif</div>
                </div>
              </div>
            </td>
            <td>{{ $a->getRegionLabel() }}</td>
            <td><b style="color:{{ ($a->amount_paid ?? 0) > 0 ? 'var(--success)' : 'var(--text-tertiary)' }}">{{ number_format($a->amount_paid ?? 0) }}</b></td>
            <td>
              @if($bal !== null)
                <b style="color:{{ $bal > 0 ? 'var(--warning)' : 'var(--success)' }}">{{ number_format($bal) }}</b>
              @else
                <span class="badge badge-neutral badge-dotted">—</span>
              @endif
            </td>
            <td><span class="badge badge-{{ $a->getStatusColor() }} badge-dotted">{{ $a->getStatusLabel() }}</span></td>
            <td>
              @if($a->checked_in_at)
                <span class="badge badge-success badge-dotted">Yes</span>
              @else
                <span class="badge badge-neutral badge-dotted">No</span>
              @endif
            </td>
            <td>{{ $a->registered_on?->format('d M Y') }}@if($a->registered_by)<div class="badge badge-neutral badge-dotted" style="margin-top:3px">by {{ $a->registered_by }}</div>@endif</td>
          </tr>
          @empty
          <tr><td colspan="7"><div class="empty-state" style="padding:40px 20px"><h3>No delegates yet</h3><p>Leaders add members through the Member Portal, or register them here under this fellowship.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="tf-info">Showing {{ $attendees->firstItem() ?? 0 }}–{{ $attendees->lastItem() ?? 0 }} of {{ $attendees->total() }} delegates</span>
      <div class="pagination">{{ $attendees->links() }}</div>
    </div>
  </div>
</div>
@endsection