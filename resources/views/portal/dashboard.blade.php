@extends('layouts.portal')

@section('title', 'Dashboard — Member Portal')
@section('content')
<div class="fade-in">
  <div style="margin-bottom:20px">
    <h1 style="font-size:22px;font-weight:800;margin:0 0 4px;color:var(--navy-900)">Karibu, {{ $member->name }}!</h1>
    <p style="margin:0;font-size:13px;color:var(--text-secondary)">
      @if($group) {{ $group->name }}@endif @if($ministry) · {{ $ministry->name }}@endif
      @if($currentCamp) · Currently registering: <b>{{ $currentCamp->title }}</b>@endif
    </p>
  </div>

  <div class="stat-grid">
    <div class="stat-card blue"><div class="stat-value">{{ $myRegistrations->count() }}</div><div class="stat-label">My Registrations</div></div>
    <div class="stat-card purple"><div class="stat-value">TZS {{ number_format($pledgeTotal) }}</div><div class="stat-label">Total Pledged</div></div>
    <div class="stat-card green"><div class="stat-value">TZS {{ number_format($pledgePaid) }}</div><div class="stat-label">Total Paid</div></div>
    <div class="stat-card orange"><div class="stat-value">TZS {{ number_format($pledgeOutstanding) }}</div><div class="stat-label">Outstanding</div></div>
  </div>

  <div class="portal-card">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
      <h2 style="margin:0">My Recent Registrations</h2>
      <a href="{{ route('portal.registrations') }}" class="btn btn-secondary btn-sm">View all / Register</a>
    </div>
    @if($myRegistrations->isNotEmpty())
    <div style="overflow-x:auto;margin-top:6px">
      <table class="portal-table">
        <thead><tr><th>Event</th><th>Name</th><th>Pickup</th><th>Paid</th><th>Status</th></tr></thead>
        <tbody>
          @foreach($myRegistrations as $r)
          @php
            $pickup = $r->pickup_location === 'arusha' ? 'Arusha' : ($r->pickup_location === 'moshi' ? 'Moshi' : '—');
          @endphp
          <tr>
            <td>{{ $r->event?->title ?? '—' }}</td>
            <td>{{ $r->name }}</td>
            <td>{{ $pickup }}</td>
            <td style="color:var(--success);font-weight:700">{{ number_format($r->amount_paid ?? 0) }}</td>
            <td><span class="portal-badge {{ $r->getStatusColor() === 'success' ? 'active' : ($r->getStatusColor() === 'info' ? 'info' : 'pending') }}">{{ $r->getStatusLabel() }}</span></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @else
    <p style="color:var(--text-secondary);font-size:13px;margin:12px 0 4px">No registrations yet. <a href="{{ route('portal.registrations') }}">Register for the camp now.</a></p>
    @endif
  </div>

  <div class="portal-card">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
      <h2 style="margin:0">My Recent Pledges</h2>
      <a href="{{ route('portal.pledges') }}" class="btn btn-secondary btn-sm">View all / Add pledge</a>
    </div>
    @if($myPledges->isNotEmpty())
    <div style="overflow-x:auto;margin-top:6px">
      <table class="portal-table">
        <thead><tr><th>Pledge No</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
        <tbody>
          @foreach($myPledges as $p)
          <tr>
            <td><b>{{ $p->pledge_no }}</b></td>
            <td>{{ number_format($p->amount) }}</td>
            <td style="color:var(--success);font-weight:700">{{ number_format($p->paid_amount ?? 0) }}</td>
            <td style="color:var(--warning);font-weight:700">{{ number_format($p->getRemainingAttribute()) }}</td>
            <td><span class="portal-badge {{ $p->getStatusColor() === 'success' ? 'active' : ($p->getStatusColor() === 'info' ? 'info' : 'pending') }}">{{ $p->getStatusLabel() }}</span></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @else
    <p style="color:var(--text-secondary);font-size:13px;margin:12px 0 4px">No pledges yet. <a href="{{ route('portal.pledges') }}">Make a pledge today.</a></p>
    @endif
  </div>

  <div class="portal-card">
    <h2 style="margin:0 0 6px">My Profile</h2>
    <div class="info-row"><span class="label">Member No</span><span class="value">{{ $member->member_no }}</span></div>
    <div class="info-row"><span class="label">Phone</span><span class="value">{{ $member->phone }}</span></div>
    <div class="info-row"><span class="label">Email</span><span class="value">{{ $member->email ?? '—' }}</span></div>
    <div class="info-row"><span class="label">Group / Ministry</span><span class="value">{{ $group?->name ?? '—' }} / {{ $ministry?->name ?? '—' }}</span></div>
    <div class="info-row"><span class="label">Total Contributions</span><span class="value">TZS {{ number_format($totalContributions) }}</span></div>
    @if($fy)
    <div class="info-row"><span class="label">Contributions ({{ $fy->name }})</span><span class="value">TZS {{ number_format($fyContributions) }}</span></div>
    @endif
  </div>
</div>
@endsection