@extends('layouts.portal')

@section('title', 'Fellowship Delegation — Member Portal')
@section('content')
<div class="fade-in">
  <div style="margin-bottom:20px">
    <h1 style="font-size:22px;font-weight:800;margin:0 0 4px;color:var(--navy-900)">My Fellowship Delegation</h1>
    <p style="margin:0;font-size:13px;color:var(--text-secondary)">
      Register and manage the members of your fellowship for
      <b>{{ $currentCamp?->title ?? \App\Models\Setting::get('event.name', 'Open Gate Camp Season 3') }}</b>.
      Payments, rooms and transport are handled by the camp committee.
    </p>
  </div>

  @if($fellowships->isEmpty())
  <div class="portal-card" style="text-align:center;padding:56px 28px">
    <div style="font-size:15px;font-weight:800;color:var(--navy-900);margin-bottom:6px">You are not assigned to any fellowship yet</div>
    <p style="font-size:13px;color:var(--text-secondary);margin:0">The committee assigns fellowship leaders here — once assigned, you will be able to build your delegation.</p>
  </div>
  @else
  <div class="stat-grid">
    <div class="stat-card blue"><div class="stat-value">{{ $fellowships->sum(fn($f) => $f->attendees_count) }}</div><div class="stat-label">Total Delegates</div></div>
    <div class="stat-card green"><div class="stat-value">{{ $fellowships->sum(fn($f) => $f->confirmed_count) }}</div><div class="stat-label">Confirmed</div></div>
    <div class="stat-card orange"><div class="stat-value">TZS {{ number_format($fellowships->sum(fn($f) => $f->attendees_sum_amount_paid)) }}</div><div class="stat-label">Paid by Delegation</div></div>
  </div>

  @foreach($fellowships as $f)
  <div class="portal-card">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap">
      <div>
        <h2 style="margin:0 0 4px">{{ $f->name }}</h2>
        @if($f->university)<p style="margin:0 0 10px;font-size:12.5px;color:var(--text-secondary)">{{ $f->university }}</p>@endif
        @php $primary = $f->primaryLeader(); @endphp
        @if($primary)
        <p style="margin:0 0 10px;font-size:12.5px;color:var(--text-secondary)">
          <b>Primary leader:</b> {{ $primary->name }}@if($primary->phone) · {{ $primary->phone }}@endif
        </p>
        @endif
      </div>
      <a href="{{ route('portal.fellowship.members', $f) }}" class="btn btn-accent">Manage Delegation</a>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:6px">
      <span class="portal-badge info">{{ $f->attendees_count }} registered</span>
      <span class="portal-badge active">{{ $f->confirmed_count }} confirmed</span>
      @php $attended = (int) $f->attendees_sum_amount_paid ?? 0; @endphp
      <span class="portal-badge info">TZS {{ number_format($f->attendees_sum_amount_paid ?? 0) }} paid</span>
    </div>
  </div>
  @endforeach
  @endif
</div>
@endsection