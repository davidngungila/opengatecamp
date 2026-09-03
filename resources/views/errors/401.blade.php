@extends('layouts.app')

@section('title', '401 Unauthorized — Open Gate Camp Mission')
@section('crumb', 'Error / 401')
@section('page_title', 'Unauthorized')

@section('content')
<div class="fade-in">
  <div class="error-wrap">
    <div class="error-code">401</div>
    <div class="confirm-icon" style="margin:0 auto 18px;background:linear-gradient(135deg,#fef3c7,#fde68a)">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/>
      </svg>
    </div>
    <h2 style="font-size:22px;margin:0 0 10px">Please sign in</h2>
    <p style="color:var(--text-secondary);font-size:14px;max-width:460px;margin:0 auto 26px">
      Your session is not authenticated. Sign in to continue.
    </p>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
      <a class="btn btn-primary" href="{{ route('login') }}">Sign in</a>
      <a class="btn btn-secondary" href="{{ url('/') }}">Back to Home</a>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .error-wrap{text-align:center;padding:70px 20px;display:flex;flex-direction:column;align-items:center}
  .error-code{font-size:84px;font-weight:900;line-height:1;letter-spacing:-3px;color:var(--text-tertiary);opacity:.35;margin-bottom:8px}
  .confirm-icon{width:60px;height:60px;border-radius:16px;display:flex;align-items:center;justify-content:center;color:var(--danger);box-shadow:0 8px 24px rgba(217,119,6,.12)}
</style>
@endpush
