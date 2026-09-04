@extends('layouts.app')

@section('title', 'Server Error — OpenGate Camp Connect')
@section('crumb', 'Error / 500')
@section('page_title', 'Server Error')

@section('content')
<div class="fade-in">
  <div class="error-wrap">
    <div class="error-code">500</div>
    <div class="confirm-icon" style="margin:0 auto 18px;background:linear-gradient(135deg,#fee2e2,#fecaca)">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
      </svg>
    </div>
    <h2 style="font-size:22px;margin:0 0 10px">Something went wrong</h2>
    <p style="color:var(--text-secondary);font-size:14px;max-width:460px;margin:0 auto 26px">
      An unexpected error occurred on our end. Please try again in a moment.
    </p>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
      <a class="btn btn-primary" href="{{ url('/') }}">Back to Dashboard</a>
      <a class="btn btn-secondary" href="javascript:location.reload()">Retry</a>
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
