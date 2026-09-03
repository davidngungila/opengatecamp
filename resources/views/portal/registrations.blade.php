@extends('layouts.portal')

@section('title', 'Registrations — Member Portal')
@section('content')
<div class="fade-in">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:18px">
    <div>
      <h1 style="font-size:20px;font-weight:800;margin:0 0 4px;color:var(--navy-900)">My Registrations</h1>
      <p style="margin:0;font-size:13px;color:var(--text-secondary)">Register yourself or group members for the camp.</p>
    </div>
    <span class="portal-badge info" style="font-size:12px">TZS {{ number_format($totalPaid ?? 0) }} paid</span>
  </div>

  <div class="stat-grid">
    <div class="stat-card blue"><div class="stat-value">{{ $registrations->total() }}</div><div class="stat-label">Total Registrations</div></div>
    <div class="stat-card green"><div class="stat-value">TZS {{ number_format($totalPaid ?? 0) }}</div><div class="stat-label">Total Paid</div></div>
    <div class="stat-card purple"><div class="stat-value">{{ $currentCamp?->title ?? \App\Models\Setting::get('event.name', 'Open Gate Camp Season 3') }}</div><div class="stat-label">Current Camp</div></div>
  </div>

  @if($currentCamp)
  <div class="portal-card">
    <h2>Register Attendee — {{ $currentCamp->title }}</h2>
    <p style="margin:-8px 0 16px;font-size:12.5px;color:var(--text-secondary)">
      {{ $currentCamp->start_date?->format('d M Y') }}@if($currentCamp->end_date) – {{ $currentCamp->end_date->format('d M Y') }}@endif
      @if($currentCamp->venue) · {{ $currentCamp->venue }}@endif
    </p>
    <form method="POST" action="{{ route('portal.registrations.store') }}" class="portal-form">
      @csrf
      <div class="form-row">
        <div class="field"><label>Full Name</label><input name="name" value="{{ old('name', $member->name) }}" placeholder="Full name" required></div>
        <div class="field"><label>Phone</label><input name="phone" value="{{ old('phone', $member->phone) }}" placeholder="+255 7XX XXX XXX" required></div>
      </div>
      <div class="form-row">
        <div class="field"><label>Email</label><input name="email" value="{{ old('email', $member->email) }}" placeholder="email@example.com"></div>
        <div class="field"><label>Pickup Location</label><select name="pickup_location" required>
          <option value="">— Select —</option>
          <option value="arusha" @if(old('pickup_location')==='arusha') selected @endif>Arusha</option>
          <option value="moshi" @if(old('pickup_location')==='moshi') selected @endif>Moshi</option>
        </select></div>
      </div>
      <div class="field" style="grid-column:1/-1">
        <label>Camp Fee (TZS) — automatic</label>
        <input type="number" value="10000" readonly style="background:var(--blue-light);font-weight:700;color:var(--navy-900)">
      </div>
      <div class="field"><label>Notes</label><textarea name="notes" placeholder="Dietary, transport, special needs...">{{ old('notes') }}</textarea></div>
      <button type="submit" class="btn btn-accent">Register Attendee</button>
    </form>
  </div>
  @endif

  <div class="portal-card" style="padding:0;overflow:hidden">
    <div style="padding:18px 24px 0">
      <h2 style="margin:0 0 12px">My Registrations</h2>
    </div>
    <div style="overflow-x:auto">
      <table class="portal-table">
        <thead><tr><th>Event</th><th>Name</th><th>Pickup</th><th>Fee</th><th>Paid</th><th>Balance</th><th>Status</th><th>Registered</th></tr></thead>
        <tbody>
          @forelse($registrations as $r)
          @php
            $bal = ($r->fee_amount !== null) ? max(0, $r->fee_amount - ($r->amount_paid ?? 0)) : 0;
            $pickup = $r->pickup_location === 'arusha' ? 'Arusha' : ($r->pickup_location === 'moshi' ? 'Moshi' : '—');
          @endphp
          <tr>
            <td>{{ $r->event?->title ?? '—' }}</td>
            <td>{{ $r->name }}</td>
            <td>{{ $pickup }}</td>
            <td>{{ $r->fee_amount !== null ? number_format($r->fee_amount) : '—' }}</td>
            <td style="color:{{ ($r->amount_paid ?? 0) > 0 ? 'var(--success)' : 'var(--text-tertiary)' }};font-weight:700">{{ number_format($r->amount_paid ?? 0) }}</td>
            <td style="color:{{ $bal > 0 ? 'var(--warning)' : 'var(--success)' }};font-weight:700">{{ number_format($bal) }}</td>
            <td><span class="portal-badge {{ $r->getStatusColor() === 'success' ? 'active' : ($r->getStatusColor() === 'danger' ? 'pending' : ($r->getStatusColor() === 'info' ? 'info' : 'pending')) }}">{{ $r->getStatusLabel() }}</span></td>
            <td>{{ $r->registered_on?->format('d M Y') }}</td>
          </tr>
          @empty
          <tr><td colspan="8" style="text-align:center;padding:36px 20px">You have not registered for any events yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div style="padding:14px 24px;border-top:1px solid var(--border);font-size:12.5px;color:var(--text-secondary)">
      Showing {{ $registrations->firstItem() ?? 0 }}–{{ $registrations->lastItem() ?? 0 }} of {{ $registrations->total() }}
      <div class="pagination" style="float:right">{{ $registrations->links() }}</div>
    </div>
  </div>
</div>
@endsection