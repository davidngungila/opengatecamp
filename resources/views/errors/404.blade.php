@extends('layouts.app')

@section('title', '404 Not Found — OpenGate Camp Connect')
@section('crumb', 'Error / 404')
@section('page_title', 'Not Found')

@section('content')
<div class="fade-in">
  <div class="error-wrap">
    <div class="error-code">404</div>
    <div class="confirm-icon" style="margin:0 auto 18px;background:linear-gradient(135deg,#dbeafe,#bfdbfe)">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/>
        <path d="M21 21l-4.35-4.35M8 11h6"/>
      </svg>
    </div>
    <h2 style="font-size:22px;margin:0 0 10px">Page not found</h2>
    <p style="color:var(--text-secondary);font-size:14px;max-width:460px;margin:0 auto 26px">
      The page you're looking for doesn't exist or may have been moved.
    </p>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
      <a class="btn btn-primary" href="{{ url('/') }}">Back to Dashboard</a>
      <a class="btn btn-secondary" href="javascript:history.back()">Go back</a>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .error-wrap{text-align:center;padding:70px 20px;display:flex;flex-direction:column;align-items:center}
  .error-code{font-size:84px;font-weight:900;line-height:1;letter-spacing:-3px;color:var(--text-tertiary);opacity:.35;margin-bottom:8px}
  .confirm-icon{width:60px;height:60px;border-radius:16px;display:flex;align-items:center;justify-content:center;color:var(--danger);box-shadow:0 8px 24px rgba(37,99,235,.12)}
</style>
@endpush
