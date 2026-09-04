@extends('layouts.app')

@section('title', 'Notifications — Settings — OpenGate Camp Connect')
@section('crumb', 'System / Settings / Notifications')
@section('page_title', 'Notifications')

@section('content')
<div class="fade-in">
  <div class="section-head"><h2>Settings</h2><p style="color:var(--text-secondary);font-size:13px;margin:4px 0 0">Notification preferences</p></div>

  <div class="solid-card">
    <h2 style="font-size:14.5px;margin:0 0 6px">Notification Preferences</h2>
    <form method="POST" action="{{ route('settings.notifications') }}">
      @csrf
      @foreach([['email','Email notifications'],['sms','SMS notifications'],['push','Push notifications'],['digest','Weekly digest email'],['payment_alerts','Payment alerts']] as [$key, $label])
      <div class="settings-row">
        <div class="sr-text"><p>{{ $label }}</p><span>Receive updates about this activity</span></div>
        <label class="switch"><input type="checkbox" name="{{ $key }}" {{ old($key, \App\Models\Setting::get("notify.$key")) === '1' ? 'checked' : '' }}><span class="slider"></span></label>
      </div>
      @endforeach
      <div class="flex" style="justify-content:flex-end;margin-top:16px">
        <button type="submit" class="btn btn-accent">Save Preferences</button>
      </div>
    </form>
  </div>
</div>
@endsection
