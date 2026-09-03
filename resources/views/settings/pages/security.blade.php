@extends('layouts.app')

@section('title', 'Security — Settings — Open Gate Camp Mission')
@section('crumb', 'System / Settings / Security')
@section('page_title', 'Security')

@section('content')
<div class="fade-in">
  <div class="section-head"><h2>Settings</h2><p style="color:var(--text-secondary);font-size:13px;margin:4px 0 0">Security</p></div>

  <div class="settings-layout">
    @include('settings.partials.nav', ['active' => 'security'])

    <div>
      <div class="solid-card" style="margin-bottom:18px">
        <h2 style="font-size:14.5px;margin:0 0 6px">Change Administrator Password</h2>
        <p style="font-size:12.5px;color:var(--text-tertiary);margin:0 0 12px">Updates the password for the Super Administrator account.</p>
        <form method="POST" action="{{ route('settings.security') }}">
          @csrf
          <div class="form-grid">
            <div class="field full"><label>Current Password *</label><input type="password" name="current_password" required></div>
            <div class="field"><label>New Password *</label><input type="password" name="new_password" required minlength="8"></div>
            <div class="field"><label>Confirm New Password *</label><input type="password" name="new_password_confirmation" required minlength="8"></div>
          </div>
          <div class="flex" style="justify-content:flex-end;margin-top:12px">
            <button type="submit" class="btn btn-accent">Change Password</button>
          </div>
        </form>
      </div>
      <div class="solid-card">
        <h2 style="font-size:14.5px;margin:0 0 6px">Policies</h2>
        @foreach([['security.2fa','Two-Factor Authentication','Require a verification code at login'],['security.strong_password','Strong Password Policy','Minimum 8 characters with mixed case and numbers'],['security.auto_timeout','Auto Session Timeout','Log out automatically after 30 minutes idle']] as [$key,$title,$sub])
        <div class="settings-row">
          <div class="sr-text"><p>{{ $title }}</p><span>{{ $sub }}</span></div>
          <label class="switch"><input type="checkbox" checked disabled><span class="slider"></span></label>
        </div>
        @endforeach
      </div>
    </div>
  </div>
</div>
@endsection
