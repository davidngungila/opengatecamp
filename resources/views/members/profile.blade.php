@extends('layouts.app')

@section('title', $member->name.' — Open Gate Camp Mission')
@section('crumb', 'People / Members / Profile')
@section('page_title', $member->name)

@php
    $initials = collect(explode(' ', $member->name))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('');
@endphp

@section('content')
<div class="fade-in">
  <a class="btn btn-ghost btn-sm" style="margin-bottom:14px" href="{{ route('members.index') }}">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/></svg>
    Back to Members
  </a>

  <div class="glass-card" style="margin-bottom:18px">
    <div class="profile-head">
      <div class="profile-avatar">{{ $initials }}</div>
      <div class="profile-meta" style="flex:1">
        <h2>{{ $member->name }}</h2>
        <div class="p-line">
          <span>{{ $member->member_no }}</span>
          <span class="badge badge-{{ $member->status==='Active' ? 'success' : ($member->status==='New' ? 'info' : 'neutral') }} badge-dotted">{{ $member->status }}</span>
          @if($member->phone)<span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg> {{ $member->phone }}</span>@endif
          @if($member->email)<span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> {{ $member->email }}</span>@endif
        </div>
      </div>
      <div class="flex gap-8">
        <button type="button" class="btn btn-secondary btn-sm" data-modal-open="messageModal">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
          Message
        </button>
        <a class="btn btn-accent btn-sm" href="{{ route('members.edit', $member) }}">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Edit Profile
        </a>
      </div>
    </div>

    <div class="tabs-bar">
      @foreach(['overview'=>'Overview','personal'=>'Personal','church'=>'Church Info','emergency'=>'Emergency','activity'=>'Activity'] as $key => $label)
        <button type="button" class="tab-btn {{ $loop->first ? 'active' : '' }}" data-tab-target="tab-{{ $key }}" data-tab-group="profileTabs">{{ $label }}</button>
      @endforeach
    </div>

    <div id="tab-overview" data-tab-pane="profileTabs">
      <div class="two-col" style="margin-bottom:0">
        <div class="solid-card">
          <h2 style="font-size:14.5px;margin:0 0 10px">Summary</h2>
          <div class="info-row"><span>Age</span><b>{{ $member->date_of_birth?->age ?? '—' }} {{ $member->date_of_birth ? 'years' : '' }}</b></div>
          <div class="info-row"><span>Gender</span><b>{{ $member->gender }}</b></div>
          <div class="info-row"><span>Group</span><b>{{ $member->group?->name ?? '—' }}</b></div>
          <div class="info-row"><span>Ministry</span><b>{{ $member->ministry?->name ?? '—' }}</b></div>
          <div class="info-row"><span>Address</span><b>{{ $member->address ?? '—' }}</b></div>
          <div class="info-row"><span>Joined</span><b>{{ $member->joined_on?->format('d M Y') ?? '—' }}</b></div>
        </div>
        <div class="solid-card">
          <h2 style="font-size:14.5px;margin:0 0 10px">Event Participation</h2>
          <div class="empty-state" style="padding:34px 16px">
            <h3>No event registrations yet</h3>
            <p>This member will appear in event attendee lists once registered for an event.</p>
            <a class="btn btn-secondary btn-sm" href="{{ route('events.index') }}">Browse Events</a>
          </div>
        </div>
      </div>
    </div>

    <div id="tab-personal" data-tab-pane="profileTabs" class="hidden">
      <div class="solid-card"><div class="form-grid">
        <div class="field"><label>Full Name</label><input value="{{ $member->name }}" readonly></div>
        <div class="field"><label>Member ID</label><input value="{{ $member->member_no }}" readonly></div>
        <div class="field"><label>Gender</label><input value="{{ $member->gender }}" readonly></div>
        <div class="field"><label>Date of Birth</label><input value="{{ $member->date_of_birth?->format('d M Y') }}" readonly></div>
        <div class="field"><label>Marital Status</label><input value="{{ $member->marital_status ?? '—' }}" readonly></div>
        <div class="field"><label>Phone</label><input value="{{ $member->phone }}" readonly></div>
        <div class="field full"><label>Address</label><input value="{{ $member->address }}" readonly></div>
      </div></div>
    </div>

    <div id="tab-church" data-tab-pane="profileTabs" class="hidden">
      <div class="card-grid">
        <div class="entity-card">
          <div class="ec-top"><div class="ec-ico" style="background:var(--blue-light);color:var(--blue-accent)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div><div><h4>{{ $member->family?->name ?? 'No family linked' }}</h4><div class="ec-sub">Head: {{ $member->family?->head ?? '—' }}</div></div></div>
        </div>
        <div class="entity-card">
          <div class="ec-top"><div class="ec-ico" style="background:var(--blue-light);color:var(--blue-accent)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></div><div><h4>{{ $member->group?->name ?? 'No group' }}</h4><div class="ec-sub">{{ $member->group?->meeting_schedule ?? '—' }}</div></div></div>
        </div>
        <div class="entity-card">
          <div class="ec-top"><div class="ec-ico" style="background:var(--purple-bg);color:var(--purple)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div><div><h4>{{ $member->ministry?->name ?? 'No ministry' }}</h4><div class="ec-sub">Member since {{ $member->joined_on?->format('M Y') ?? '—' }}</div></div></div>
        </div>
      </div>
    </div>

    <div id="tab-emergency" data-tab-pane="profileTabs" class="hidden">
      <div class="solid-card"><div class="form-grid">
        <div class="field"><label>Contact Name</label><input value="{{ $member->emergency_name ?? '—' }}" readonly></div>
        <div class="field"><label>Relationship</label><input value="{{ $member->emergency_relationship ?? '—' }}" readonly></div>
        <div class="field"><label>Phone Number</label><input value="{{ $member->emergency_phone ?? '—' }}" readonly></div>
      </div></div>
    </div>

    <div id="tab-activity" data-tab-pane="profileTabs" class="hidden">
      <div class="glass-card">
        <div class="mini-row"><div class="m-ico" style="background:var(--blue-light);color:var(--blue-accent)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg></div><div class="m-body"><p>Member registered as {{ $member->member_no }}</p><span>{{ $member->created_at?->diffForHumans() }}</span></div></div>
        @if($member->created_at?->ne($member->updated_at))
        <div class="mini-row"><div class="m-ico" style="background:var(--success-bg);color:var(--success)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></div><div class="m-body"><p>Profile last updated</p><span>{{ $member->updated_at?->diffForHumans() }}</span></div></div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="messageModal">
  <div class="modal-box sm">
    <div class="modal-head">
      <div><h3>Send Message</h3><p>To: {{ $member->name }}</p></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('messaging.store') }}">
      @csrf
      <div class="modal-body">
        <div class="form-grid">
          <div class="field full"><label>Channel</label>
            <select name="channel">
              <option value="sms">SMS to {{ $member->phone }}</option>
              @if($member->email)<option value="email">Email to {{ $member->email }}</option>@endif
            </select>
          </div>
          <input type="hidden" name="recipients" value="{{ $member->name }} ({{ $member->member_no }})">
          <div class="field full"><label>Message *</label><textarea name="message" placeholder="Type your message..." required></textarea></div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Send</button>
      </div>
    </form>
  </div>
</div>

@endsection
