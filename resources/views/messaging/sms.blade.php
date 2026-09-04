@extends('layouts.app')
@section('title', 'SMS — OpenGate Camp Connect')
@section('crumb', 'Communication / Messaging / SMS')
@section('page_title', 'SMS Messaging')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <h2>SMS Messaging</h2>
    @if($smsConfigured)
      <span class="badge badge-success" style="font-size:11px">API Ready</span>
    @else
      <a href="{{ route('messaging.settings') }}" class="badge badge-danger" style="font-size:11px;text-decoration:none">API not configured — go to Settings</a>
    @endif
  </div>
  @if(session('error'))
  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#991b1b;font-size:13.5px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> {{ session('error') }}</div>
  @endif
  @if(session('success'))
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#166534;font-size:13.5px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M20 6L9 17l-5-5"/></svg> {{ session('success') }}</div>
  @endif
  @include('messaging._compose_sms')
</div>
@endsection
