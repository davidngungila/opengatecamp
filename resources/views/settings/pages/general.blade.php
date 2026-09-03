@extends('layouts.app')

@section('title', 'General / Church Profile — Settings — Open Gate Camp Mission')
@section('crumb', 'System / Settings / General')
@section('page_title', 'General / Church Profile')

@php
    $s = fn($key, $default = '') => old($key, \App\Models\Setting::get($key, $default));
@endphp

@section('content')
<div class="fade-in">
  <div class="section-head"><h2>Settings</h2><p style="color:var(--text-secondary);font-size:13px;margin:4px 0 0">General / Church Profile</p></div>

  <div class="solid-card">
    <h2 style="font-size:14.5px;margin:0 0 16px">Church Profile</h2>
    <form method="POST" action="{{ route('settings.general') }}">
      @csrf
      <div class="form-grid">
        <div class="field"><label>Organization Name *</label><input name="church_name" value="{{ $s('church.name') }}" required></div>
        <div class="field"><label>Event Name</label><input name="event_name" value="{{ $s('event.name', 'Open Gate Camp Season 3') }}" placeholder="Open Gate Camp Season 3"></div>
        <div class="field"><label>Chaplain *</label><input name="chaplain" value="{{ $s('church.chaplain') }}" required></div>
        <div class="field"><label>Phone</label><input name="church_phone" value="{{ $s('church.phone') }}"></div>
        <div class="field"><label>Email</label><input name="church_email" value="{{ $s('church.email') }}"></div>
        <div class="field"><label>Website</label><input name="church_website" value="{{ $s('church.website') }}"></div>
        <div class="field full"><label>Address</label><input name="church_address" value="{{ $s('church.address') }}"></div>
      </div>
      <div class="flex" style="justify-content:flex-end;margin-top:16px">
        <button type="submit" class="btn btn-accent">Save Changes</button>
      </div>
    </form>
  </div>
</div>
@endsection
