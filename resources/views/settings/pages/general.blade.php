@extends('layouts.app')

@section('title', 'Event Settings — Settings — Open Gate Camp Mission')
@section('crumb', 'System / Settings / Event Settings')
@section('page_title', 'Event Settings')

@php
    $s = fn($key, $default = '') => old($key, \App\Models\Setting::get($key, $default));
    $statuses = ['draft' => 'Draft', 'planned' => 'Planned', 'open_registration' => 'Open Registration', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
@endphp

@section('content')
<div class="fade-in">
  <div class="section-head"><h2>Settings</h2><p style="color:var(--text-secondary);font-size:13px;margin:4px 0 0">Event Settings</p></div>

  @if(session('error'))
  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#991b1b;font-size:13.5px">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> {{ session('error') }}
  </div>
  @endif
  @if(session('success'))
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#166534;font-size:13.5px">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M20 6L9 17l-5-5"/></svg> {{ session('success') }}
  </div>
  @endif

  <div class="solid-card">
    <h2 style="font-size:14.5px;margin:0 0 4px;display:flex;align-items:center;gap:8px">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Event Settings
    </h2>
    <p style="font-size:12.5px;color:var(--text-tertiary);margin:0 0 16px">Configure the single current event this system manages.</p>
    <form method="POST" action="{{ route('settings.general') }}">
      @csrf
      <div class="form-grid">
        <div class="field"><label>Event Name *</label><input name="event_name" value="{{ $s('event.name', 'Open Gate Camp Season 3') }}" required placeholder="Open Gate Camp Season 3"></div>
        <div class="field"><label>Status</label>
          <select name="event_status">
            @foreach($statuses as $k => $label)
              <option value="{{ $k }}" @if($s('event.status', 'planned') === $k) selected @endif>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="field"><label>Venue</label><input name="event_venue" value="{{ $s('event.venue') }}" placeholder="e.g. MoCU Grounds"></div>
        <div class="field"><label>Location</label><input name="event_location" value="{{ $s('event.location') }}" placeholder="e.g. Moshi, Tanzania"></div>
        <div class="field"><label>Start Date</label><input type="date" name="event_start_date" value="{{ $s('event.start_date') }}"></div>
        <div class="field"><label>End Date</label><input type="date" name="event_end_date" value="{{ $s('event.end_date') }}"></div>
        <div class="field"><label>Start Time</label><input type="time" name="event_start_time" value="{{ $s('event.start_time') }}"></div>
        <div class="field"><label>End Time</label><input type="time" name="event_end_time" value="{{ $s('event.end_time') }}"></div>
        <div class="field"><label>Capacity</label><input type="number" min="0" name="event_capacity" value="{{ $s('event.capacity') }}" placeholder="e.g. 500"></div>
        <div class="field"><label>Registration Fee</label><input type="number" min="0" step="0.01" name="event_registration_fee" value="{{ $s('event.registration_fee', '0') }}" placeholder="e.g. 10000"></div>
        <div class="field"><label>Organizer</label><input name="event_organizer" value="{{ $s('event.organizer') }}" placeholder="e.g. Daniel Mwinuka"></div>
        <div class="field full"><label>Description</label><textarea name="event_description" rows="3" placeholder="Short description of the event">{{ $s('event.description') }}</textarea></div>
      </div>
      <div class="flex" style="justify-content:flex-end;margin-top:16px">
        <button type="submit" class="btn btn-accent">Save Event Settings</button>
      </div>
    </form>
  </div>

  <div class="solid-card" style="margin-top:18px">
    <h2 style="font-size:14.5px;margin:0 0 4px;display:flex;align-items:center;gap:8px">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Organization
    </h2>
    <p style="font-size:12.5px;color:var(--text-tertiary);margin:0 0 16px">Organisation / church identity used on tickets and communications.</p>
    <form method="POST" action="{{ route('settings.organization') }}">
      @csrf
      <div class="form-grid">
        <div class="field"><label>Organization Name *</label><input name="church_name" value="{{ $s('church.name') }}" required></div>
        <div class="field"><label>Chaplain *</label><input name="chaplain" value="{{ $s('church.chaplain') }}" required></div>
        <div class="field"><label>Phone</label><input name="church_phone" value="{{ $s('church.phone') }}"></div>
        <div class="field"><label>Email</label><input name="church_email" value="{{ $s('church.email') }}"></div>
        <div class="field"><label>Website</label><input name="church_website" value="{{ $s('church.website') }}"></div>
        <div class="field full"><label>Address</label><input name="church_address" value="{{ $s('church.address') }}"></div>
      </div>
      <div class="flex" style="justify-content:flex-end;margin-top:16px">
        <button type="submit" class="btn btn-accent">Save Organization</button>
      </div>
    </form>
  </div>
</div>
@endsection
