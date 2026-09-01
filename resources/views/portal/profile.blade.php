@extends('layouts.portal')

@section('title', 'Profile — Member Portal')
@section('content')
<div class="fade-in">
  <h1 style="font-size:20px;font-weight:800;margin:0 0 18px;color:var(--navy-900)">My Profile</h1>

  <div class="portal-card">
    <h2>Personal Details</h2>
    <div class="info-row"><span class="label">Full Name</span><span class="value">{{ $member->name }}</span></div>
    <div class="info-row"><span class="label">Member No</span><span class="value">{{ $member->member_no }}</span></div>
    <div class="info-row"><span class="label">Gender</span><span class="value">{{ $member->gender ?? '—' }}</span></div>
    <div class="info-row"><span class="label">Member Type</span><span class="value">{{ $member->member_type ?? '—' }}</span></div>
    <div class="info-row"><span class="label">Group</span><span class="value">{{ $member->group?->name ?? '—' }}</span></div>
    <div class="info-row"><span class="label">Ministry</span><span class="value">{{ $member->ministry?->name ?? '—' }}</span></div>
    <div class="info-row"><span class="label">Family</span><span class="value">{{ $member->family?->name ?? '—' }}</span></div>
    <div class="info-row"><span class="label">Joined</span><span class="value">{{ $member->joined_on?->format('d M Y') ?? '—' }}</span></div>
  </div>

  <div class="portal-card">
    <h2>Update Contact Details</h2>
    <form method="POST" action="{{ route('portal.profile.update') }}" class="portal-form">
      @csrf
      @method('PUT')
      <div class="field"><label>Email</label><input name="email" value="{{ old('email', $member->email) }}" placeholder="email@example.com"></div>
      <div class="field"><label>Address</label><input name="address" value="{{ old('address', $member->address) }}" placeholder="Home address"></div>
      <div class="form-row">
        <div class="field"><label>Emergency Contact Name</label><input name="emergency_name" value="{{ old('emergency_name', $member->emergency_name) }}"></div>
        <div class="field"><label>Relationship</label><input name="emergency_relationship" value="{{ old('emergency_relationship', $member->emergency_relationship) }}"></div>
      </div>
      <div class="field"><label>Emergency Phone</label><input name="emergency_phone" value="{{ old('emergency_phone', $member->emergency_phone) }}" placeholder="+255 7XX XXX XXX"></div>
      <button type="submit" class="btn btn-accent">Save Changes</button>
    </form>
  </div>
</div>
@endsection