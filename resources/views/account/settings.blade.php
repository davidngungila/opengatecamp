@extends('layouts.app')

@section('title', 'Account Settings — OpenGate Camp Connect')
@section('crumb', 'Account / Settings')
@section('page_title', 'Account Settings')

@section('content')
<div class="fade-in">
  <div class="section-head"><h2>Account Settings</h2></div>

  <div class="two-col" style="margin-bottom:0">
    <div>
      <div class="solid-card">
        <h2 style="font-size:14.5px;margin:0 0 6px">Notification Preferences</h2>
        <p style="color:var(--text-secondary);font-size:12.5px;margin:0 0 12px">Choose how {{ $user->name }} receives system updates. These are account-level preferences managed alongside organisation defaults.</p>
        <form method="POST" action="{{ route('settings.notifications') }}">
          @csrf
          @foreach([['email','Email notifications'],['sms','SMS notifications']] as [$key, $label])
          <div class="settings-row">
            <div class="sr-text"><p>{{ $label }}</p><span>Enable {{ strtolower($label) }} for account activity</span></div>
            <label class="switch"><input type="checkbox" name="{{ $key }}" {{ old($key, \App\Models\Setting::get("notify.$key")) === '1' ? 'checked' : '' }}><span class="slider"></span></label>
          </div>
          @endforeach
          <div class="flex" style="justify-content:flex-end;margin-top:14px">
            <button type="submit" class="btn btn-accent btn-sm">Save Preferences</button>
          </div>
        </form>
      </div>

      <div class="solid-card" style="margin-top:18px">
        <h2 style="font-size:14.5px;margin:0 0 6px">Security</h2>
        <p style="color:var(--text-secondary);font-size:12.5px;margin:0 0 12px">Manage your password and account security.</p>
        <div class="settings-row">
          <div class="sr-text"><p>Change password</p><span>Update the password used to sign in to your account.</span></div>
          <a class="btn btn-secondary btn-sm" href="{{ route('account.profile') }}">Go to Profile</a>
        </div>
      </div>
    </div>

    <div>
      <div class="solid-card">
        <h2 style="font-size:14.5px;margin:0 0 12px">Account Summary</h2>
        <div class="info-row"><span>Name</span><b>{{ $user->name }}</b></div>
        <div class="info-row"><span>Email</span><b>{{ $user->email }}</b></div>
        <div class="info-row"><span>Phone</span><b>{{ $user->phone ?? '—' }}</b></div>
        <div class="info-row"><span>Role</span><b>{{ $user->role?->name ?? 'Member' }}</b></div>
        <div class="info-row"><span>Status</span><b>{{ $user->status }}</b></div>
        <div class="info-row"><span>Last Login</span><b>{{ $user->last_login_at?->format('d M Y H:i') ?? '—' }}</b></div>
      </div>

      <div class="glass-card" style="margin-top:18px">
        <div class="flex" style="justify-content:space-between;align-items:center;margin-bottom:10px">
          <h2 style="font-size:14.5px;margin:0">Recent Activity</h2>
          <a class="link-btn" href="{{ route('account.audit-logs') }}">View all logs</a>
        </div>
        <div class="mini-row"><div class="m-ico" style="background:var(--blue-light);color:var(--blue-accent)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div class="m-body"><p>Joined OpenGate Camp Connect</p><span>{{ $user->created_at?->diffForHumans() }}</span></div></div>
        @forelse($lastEntries as $log)
        <div class="mini-row"><div class="m-ico" style="background:var(--success-bg);color:var(--success)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div class="m-body"><p>{{ $log->action }} @if($log->module)<span style="color:var(--text-tertiary)">· {{ $log->module }}</span>@endif</p><span>{{ $log->created_at?->diffForHumans() }}</span></div></div>
        @empty
        <div class="empty-state" style="padding:24px 16px"><p style="margin:0;color:var(--text-secondary)">No recent activity found for this account.</p></div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection