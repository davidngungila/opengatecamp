@extends('layouts.app')

@section('title', 'My Profile — Open Gate Camp Mission')
@section('crumb', 'Account / My Profile')
@section('page_title', 'My Profile')

@php
    $initial = collect(explode(' ', $user->name ?? 'OG'))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('');
    $profileImg = $user->profile_image ?? null;
@endphp

@section('content')
<div class="fade-in">
  <div class="section-head"><h2>My Profile</h2></div>

  <div class="two-col" style="margin-bottom:0">
    <div class="glass-card">
      <div class="profile-head" style="margin-bottom:18px">
        <div class="profile-avatar" style="overflow:hidden;background-size:cover;background-position:center;{{ $profileImg ? "background-image:url('".$profileImg."');" : '' }}">
          @if(!$profileImg){{ $initial }}@endif
        </div>
        <div class="profile-meta" style="flex:1">
          <h2 style="margin:0 0 4px">{{ $user->name }}</h2>
          <div class="p-line">
            <span class="badge badge-{{ $user->role?->name === 'Super Administrator' ? 'success' : 'info' }} badge-dotted">{{ $user->role?->name ?? 'Member' }}</span>
            <span>{{ $user->email }}</span>
            @if($user->phone)<span>{{ $user->phone }}</span>@endif
          </div>
          @if($user->bio)<p style="color:var(--text-secondary);font-size:13px;margin:10px 0 0">{{ $user->bio }}</p>@endif
        </div>
      </div>

      <form method="POST" action="{{ route('account.profile.photo') }}" enctype="multipart/form-data" style="margin-bottom:16px">
        @csrf
        <div class="form-grid">
          <div class="field"><label>Profile Photo</label>
            <input type="file" name="profile_image" accept="image/*" required>
            <div class="field-hint">JPG / PNG / WebP, up to 4&nbsp;MB.</div>
          </div>
          <div class="field" style="align-self:end">
            <div class="flex gap-8">
              <button type="submit" class="btn btn-accent btn-sm">Upload Photo</button>
              @if($profileImg)
              <form method="POST" action="{{ route('account.profile.photo.remove') }}" data-confirm data-confirm-title="Remove photo?" data-confirm-message="Your profile photo will be removed." data-confirm-label="Remove">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm" style="width:auto">Remove</button>
              </form>
              @endif
            </div>
          </div>
        </div>
      </form>

      <h2 style="font-size:14px;margin:0 0 12px">Personal Details</h2>
      <form method="POST" action="{{ route('account.profile.update') }}">
        @csrf
        @method('PUT')
        <div class="form-grid">
          <div class="field"><label>Full Name *</label><input name="name" value="{{ old('name', $user->name) }}" required></div>
          <div class="field"><label>Email *</label><input name="email" type="email" value="{{ old('email', $user->email) }}" required></div>
          <div class="field"><label>Phone</label><input name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+255 7XX XXX XXX"></div>
          <div class="field full"><label>Bio</label><textarea name="bio" maxlength="500" placeholder="A short bio / role description">{{ old('bio', $user->bio) }}</textarea></div>
        </div>
        <div class="flex" style="justify-content:flex-end;margin-top:16px">
          <button type="submit" class="btn btn-accent">Save Changes</button>
        </div>
      </form>
    </div>

    <div>
      <div class="solid-card">
        <h2 style="font-size:14.5px;margin:0 0 14px">Change Password</h2>
        <form method="POST" action="{{ route('account.password.update') }}">
          @csrf
          @method('PUT')
          <div class="field"><label>Current Password *</label><input type="password" name="current_password" required></div>
          <div class="field"><label>New Password *</label><input type="password" name="password" minlength="6" required></div>
          <div class="field"><label>Confirm New Password *</label><input type="password" name="password_confirmation" required></div>
          <div class="field-hint" style="margin:8px 0 0">Use at least 6 characters.</div>
          <div class="flex" style="justify-content:flex-end;margin-top:16px">
            <button type="submit" class="btn btn-secondary btn-sm">Update Password</button>
          </div>
        </form>
      </div>

      <div class="solid-card" style="margin-top:18px">
        <h2 style="font-size:14.5px;margin:0 0 6px">Account Info</h2>
        <div class="info-row"><span>Role</span><b>{{ $user->role?->name ?? 'Member' }}</b></div>
        <div class="info-row"><span>Member Since</span><b>{{ $user->created_at?->format('d M Y') }}</b></div>
        <div class="info-row"><span>Last Login</span><b>{{ $user->last_login_at?->format('d M Y H:i') ?? '—' }}</b></div>
        <div class="info-row"><span>Status</span><b>{{ $user->status }}</b></div>
      </div>
    </div>
  </div>
</div>
@endsection