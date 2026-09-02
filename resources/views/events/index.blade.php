@extends('layouts.app')

@section('title', 'Events — Open Gate Camp Mission')
@section('crumb', 'Mission / Events')
@section('page_title', 'Events')

@php
    $typeColors = [
        'camp' => ['var(--purple-bg)', 'var(--purple)'],
        'conference' => ['var(--blue-light)', 'var(--blue-accent)'],
        'mission_trip' => ['var(--success-bg)', 'var(--success)'],
        'training' => ['var(--info-bg)', 'var(--info)'],
        'worship' => ['var(--warning-bg)', 'var(--warning)'],
        'other' => ['var(--neutral-bg, #F1F5F9)', 'var(--text-secondary)'],
    ];
@endphp

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Events</h2><div class="sub">{{ $events->total() }} events · {{ $upcomingCount }} upcoming · {{ $totalAttendees }} registrations</div></div>
    <div class="flex gap-8">
      <button type="button" class="btn btn-accent" data-modal-open="eventModal">+ Create Event</button>
    </div>
  </div>

  <form class="toolbar" method="GET" action="{{ url('/events') }}">
    <div class="tfield grow"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input name="q" value="{{ request('q') }}" placeholder="Search events by name, venue..."></div>
    <select class="filter-select" name="type" onchange="this.form.submit()">
      <option value="">All Types</option>
      @foreach($types as $k => $t)
        <option value="{{ $k }}" {{ request('type')===$k ? 'selected' : '' }}>{{ $t }}</option>
      @endforeach
    </select>
    <select class="filter-select" name="status" onchange="this.form.submit()">
      <option value="">All Status</option>
      @foreach($statuses as $k => $s)
        <option value="{{ $k }}" {{ request('status')===$k ? 'selected' : '' }}>{{ $s }}</option>
      @endforeach
    </select>
    <button type="button" class="btn btn-secondary btn-sm" onclick="toast('Export started','success')">Export</button>
  </form>

  <div class="card-grid">
    @forelse($events as $e)
    @php
        $tc = $typeColors[$e->event_type] ?? ['#F1F5F9', 'var(--text-secondary)'];
        $daysLeft = $e->start_date->diffInDays(now(), false);
        $daysLeftText = $daysLeft < 0 ? 'Ended' : ($daysLeft === 0 ? 'Today' : $daysLeft.'d left');
    @endphp
    <div class="entity-card">
      <div class="ec-top">
        <div class="ec-ico" style="background:{{ $tc[0] }};color:{{ $tc[1] }}">@include('partials.event-icon', ['type' => $e->event_type, 'size' => 22])</div>
        <div>
          <h4>{{ $e->title }}</h4>
          <div class="ec-sub">{{ $e->start_date?->format('d M Y') }} @if($e->end_date && $e->end_date->ne($e->start_date)) – {{ $e->end_date->format('d M Y') }}@endif · {{ $e->venue ?? $e->location ?? 'TBD' }}</div>
        </div>
      </div>
      <div class="ec-stats">
        <div class="ec-stat"><b>{{ $e->confirmed_count ?? 0 }}</b><span>Registered</span></div>
        <div class="ec-stat"><b>{{ $e->pledge_count ?? 0 }}</b><span>Pledges</span></div>
      </div>
      <div style="margin-top:10px;display:flex;align-items:center;gap:6px;flex-wrap:wrap">
        <span class="badge badge-{{ $e->getStatusColor() }} badge-dotted">{{ $e->getStatusLabel() }}</span>
        <span class="badge badge-{{ $e->getTypeColor() }}">{{ $e->getTypeLabel() }}</span>
        @if($daysLeft >= 0)
        <span class="badge badge-neutral badge-dotted">{{ $daysLeftText }}</span>
        @endif
      </div>
      <div class="flex gap-8" style="margin-top:14px">
        <a href="{{ route('events.show', $e) }}" class="btn btn-secondary btn-sm" style="flex:1">Details</a>
        <a href="{{ route('events.show', $e) }}#attendees" class="btn btn-ghost btn-sm">Registrations</a>
      </div>
    </div>
    @empty
    <div style="grid-column:1/-1">
      <div class="empty-state" style="padding:50px 20px">
        <h3>No events found</h3>
        <p>Create your first camp, conference or mission trip event to get started.</p>
        <button type="button" class="btn btn-accent" data-modal-open="eventModal">+ Create Event</button>
      </div>
    </div>
    @endforelse
  </div>

  <div class="table-footer" style="margin-top:16px">
    <span class="tf-info">Showing {{ $events->firstItem() ?? 0 }}–{{ $events->lastItem() ?? 0 }} of {{ $events->total() }} records</span>
    <div class="pagination">{{ $events->links() }}</div>
  </div>
</div>

<div class="modal-overlay" id="eventModal">
  <div class="modal-box md">
    <div class="modal-head">
      <div><h3>Create Event</h3><p>Set up a new camp, conference or mission trip</p></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('events.store') }}">
      @csrf
      <div class="modal-body">
        <div class="form-grid">
          <div class="field full"><label>Event Title</label><input name="title" placeholder="e.g. Open Gate Camp Season 3" value="{{ old('title') }}"></div>
          <div class="field"><label>Type</label><select name="event_type">
            @foreach($types as $k => $t)<option value="{{ $k }}" {{ old('event_type')===$k ? 'selected' : '' }}>{{ $t }}</option>@endforeach
          </select></div>
          <div class="field"><label>Status</label><select name="status">
            @foreach($statuses as $k => $s)<option value="{{ $k }}" {{ old('status')===$k ? 'selected' : '' }}>{{ $s }}</option>@endforeach
          </select></div>
          <div class="field"><label>Start Date</label><input type="date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}"></div>
          <div class="field"><label>End Date</label><input type="date" name="end_date" value="{{ old('end_date') }}"></div>
          <div class="field"><label>Venue</label><input name="venue" placeholder="e.g. Open Gate Grounds" value="{{ old('venue') }}"></div>
          <div class="field"><label>Location</label><input name="location" placeholder="City / Region" value="{{ old('location') }}"></div>
          <div class="field"><label>Capacity</label><input type="number" name="capacity" placeholder="e.g. 200" value="{{ old('capacity') }}"></div>
          <div class="field"><label>Registration Fee (TZS)</label><input type="number" step="0.01" name="registration_fee" placeholder="0" value="{{ old('registration_fee', 0) }}"></div>
          <div class="field"><label>Organizer</label><input name="organizer" placeholder="e.g. Youth Ministry" value="{{ old('organizer') }}"></div>
          <div class="field full"><label>Description</label><textarea name="description" placeholder="About this event...">{{ old('description') }}</textarea></div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Create Event</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="issueModal-hide" style="display:none"></div>
@endsection