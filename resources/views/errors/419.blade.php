@extends('layouts.app')

@section('title', 'Session Expired — Open Gate Camp Mission')
@section('crumb', 'Error / 419')
@section('page_title', 'Session Expired')

@section('content')
<div class="fade-in">
  <div class="error-wrap">
    <div class="error-code">419</div>
    <div class="confirm-icon" style="margin:0 auto 18px;background:linear-gradient(135deg,#fee2e2,#fecaca)">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 8v4l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    </div>
    <h2 style="font-size:22px;margin:0 0 10px">Session expired</h2>
    <p style="color:var(--text-secondary);font-size:14px;max-width:460px;margin:0 auto 26px">
      Your session has expired. Please refresh the page and try again.
    </p>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
      <a class="btn btn-primary" href="{{ url('/') }}">Refresh &amp; continue</a>
      <a class="btn btn-secondary" href="{{ route('login') }}">Sign in again</a>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .error-wrap{text-align:center;padding:70px 20px;display:flex;flex-direction:column;align-items:center}
  .error-code{font-size:84px;font-weight:900;line-height:1;letter-spacing:-3px;color:var(--text-tertiary);opacity:.35;margin-bottom:8px}
  .confirm-icon{width:60px;height:60px;border-radius:16px;display:flex;align-items:center;justify-content:center;color:var(--danger);box-shadow:0 8px 24px rgba(220,38,38,.12)}
</style>
@endpush
