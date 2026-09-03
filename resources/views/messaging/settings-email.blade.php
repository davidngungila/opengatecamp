@extends('layouts.app')
@section('title', 'Email Settings — Open Gate Camp Mission')
@section('crumb', 'Communication / Messaging / Email Settings')
@section('page_title', 'Email Settings')

@php
    $s = fn($key, $default = '') => old($key, \App\Models\Setting::get($key, $default));
@endphp

@section('content')
<div class="fade-in">
  <div class="section-head">
    <h2>Email Settings</h2>
    <p style="color:var(--text-secondary);font-size:13px;margin:4px 0 0">Configure your SMTP server for outbound emails.</p>
  </div>
  @include('messaging.partials.settings-nav', ['active' => 'email'])

  @if(session('error'))
  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#991b1b;font-size:13.5px">⚠ {{ session('error') }}</div>
  @endif
  @if(session('success'))
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#166534;font-size:13.5px">✓ {{ session('success') }}</div>
  @endif

  <div class="glass-card" style="max-width:640px">
    <h2 style="font-size:14.5px;margin:0 0 6px">SMTP Server Details</h2>

    @php $mailConfigured = (string) $s('mail.host') !== ''; @endphp
    @if($mailConfigured)
    <div style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:var(--green-light);border:1px solid rgba(22,163,74,.15);border-radius:8px;margin:0 0 18px">
      <span style="color:var(--green-accent);font-size:13px;font-weight:700">✓ SMTP Configured — {{ $s('mail.host') }}:{{ $s('mail.port') }}</span>
    </div>
    @else
    <div style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;margin:0 0 18px">
      <span style="color:#991b1b;font-size:13px;font-weight:700">⌾ Not configured</span>
    </div>
    @endif

    <form method="POST" action="{{ route('messaging.settings.email.save') }}">
      @csrf
      <div class="form-grid">
        <div class="field">
          <label>SMTP Host *</label>
          <input name="mail_host" value="{{ $s('mail.host') }}" placeholder="e.g. smtp.gmail.com">
        </div>
        <div class="field">
          <label>Port</label>
          <input name="mail_port" type="number" value="{{ $s('mail.port') ?? 587 }}" placeholder="587">
        </div>
        <div class="field">
          <label>Encryption</label>
          <select name="mail_encryption">
            @foreach(['tls'=>'TLS','ssl'=>'SSL','none'=>'None'] as $val => $label)
            <option value="{{ $val }}" {{ $s('mail.encryption') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="field">
          <label>SMTP Username</label>
          <input name="mail_username" value="{{ $s('mail.username') }}" placeholder="you@example.com">
        </div>
        <div class="field">
          <label>SMTP Password</label>
          <input name="mail_password" type="password" value="{{ $s('mail.password') }}" placeholder="••••••••">
        </div>
        <div class="field full">
          <label>From Email Address</label>
          <input name="mail_from_address" value="{{ $s('mail.from_address') }}" placeholder="info@opengatecamp.org">
        </div>
        <div class="field full">
          <label>From Name</label>
          <input name="mail_from_name" value="{{ $s('mail.from_name') }}" placeholder="Open Gate Camp Mission">
        </div>
      </div>
      <div style="margin-top:16px">
        <button type="submit" class="btn btn-accent">Save SMTP Settings</button>
      </div>
    </form>
  </div>
</div>
@endsection
