@extends('layouts.app')
@section('title', 'Settings — Open Gate Camp Mission')
@section('crumb', 'Communication / Messaging / Settings')
@section('page_title', 'Messaging Settings')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <h2>Messaging Settings</h2>
  </div>
  @include('messaging._tabs', ['active' => 'settings'])
  @if(session('error'))
  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#991b1b;font-size:13.5px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> {{ session('error') }}</div>
  @endif
  @if(session('success'))
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#166534;font-size:13.5px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M20 6L9 17l-5-5"/></svg> {{ session('success') }}</div>
  @endif

  <div class="glass-card" style="max-width:600px">
    <h2 style="font-size:14.5px;margin:0 0 18px">SMS API Settings</h2>
    @if($smsConfigured)
    <div style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:var(--green-light);border:1px solid rgba(22,163,74,.15);border-radius:8px;margin-bottom:18px">
      <span style="color:var(--green-accent);font-size:13px;font-weight:700"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px"><path d="M20 6L9 17l-5-5"/></svg> API Configured</span>
    </div>
    @else
    <div style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;margin-bottom:18px">
      <span style="color:#991b1b;font-size:13px;font-weight:700"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Not configured</span>
    </div>
    @endif
    <p style="color:var(--text-muted);font-size:13px;margin-bottom:18px">
      Configure your <b>messaging-service.co.tz</b> API credentials. Your token is stored securely in the database.
    </p>
    <form method="POST" action="{{ route('messaging.token') }}">
      @csrf
      <div class="form-grid">
        <div class="field full">
          <label>API Token *</label>
          <input name="api_token" required value="{{ $smsToken }}" placeholder="Paste your API token here" style="font-family:monospace;font-size:13px">
        </div>
        <div class="field full">
          <label>Sender ID</label>
          <input name="sender_id" value="{{ $smsSenderId }}" placeholder="e.g. TMCS MoCU">
          <small style="color:var(--text-muted);margin-top:4px;display:block">Registered sender ID on the SMS gateway. Default: TMCS MoCU</small>
        </div>
      </div>
      <div style="margin-top:16px">
        <button type="submit" class="btn btn-accent">Save Settings</button>
      </div>
    </form>
  </div>

  <div class="glass-card" style="max-width:600px;margin-top:18px">
    <h2 style="font-size:14.5px;margin:0 0 14px">Test SMS</h2>
    <p style="color:var(--text-muted);font-size:13px;margin-bottom:14px">Send a test SMS to verify your API configuration is working.</p>
    <form method="POST" action="{{ route('messaging.store') }}">
      @csrf
      <input type="hidden" name="channel" value="sms">
      <input type="hidden" name="recipients" value="Test">
      <div class="form-grid">
        <div class="field full">
          <label>Phone Number</label>
          <input name="phone" required placeholder="e.g. 0622239304" style="font-family:monospace;font-size:15px;letter-spacing:0.5px">
        </div>
        <div class="field full">
          <label>Message</label>
          <input name="message" required value="Test SMS from Open Gate Camp Mission Management System" placeholder="Test message">
        </div>
      </div>
      <div style="margin-top:12px">
        <button type="submit" name="action" value="send" class="btn btn-secondary">Send Test SMS</button>
      </div>
    </form>
  </div>
</div>
@endsection
