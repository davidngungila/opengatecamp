@extends('layouts.portal')

@section('title', 'Settings — Member Portal')
@section('content')
<div class="fade-in">
  <h1 style="font-size:20px;font-weight:800;margin:0 0 18px;color:var(--navy-900)">Account Settings</h1>

  <div class="portal-card">
    <h2>Change Password</h2>
    <form method="POST" action="{{ route('portal.password.update') }}" class="portal-form">
      @csrf
      @method('PUT')
      <div class="field"><label>Current Password</label><input type="password" name="current_password" required></div>
      <div class="form-row">
        <div class="field"><label>New Password</label><input type="password" name="password" minlength="6" required></div>
        <div class="field"><label>Confirm New Password</label><input type="password" name="password_confirmation" required></div>
      </div>
      <button type="submit" class="btn btn-accent">Update Password</button>
    </form>
  </div>

  <div class="portal-card">
    <h2>Account Details</h2>
    <div class="info-row"><span class="label">Name</span><span class="value">{{ $user->name }}</span></div>
    <div class="info-row"><span class="label">Email</span><span class="value">{{ $user->email }}</span></div>
    <div class="info-row"><span class="label">Phone</span><span class="value">{{ $user->phone ?? '—' }}</span></div>
    <div class="info-row"><span class="label">Member Since</span><span class="value">{{ \Carbon\Carbon::parse($user->created_at)->format('d M Y') }}</span></div>
    <div class="info-row"><span class="label">Last Login</span><span class="value">{{ $user->last_login_at?->format('d M Y H:i') ?? '—' }}</span></div>
  </div>
</div>
@endsection