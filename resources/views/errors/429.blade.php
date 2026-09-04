@extends('layouts.app')

@section('title', 'Too Many Requests — OpenGate Camp Connect')
@section('crumb', 'Error / 429')
@section('page_title', 'Too Many Requests')

@section('content')
<div class="fade-in">
  <div class="error-wrap">
    <div class="error-code">429</div>
    <div class="confirm-icon" style="margin:0 auto 18px;background:linear-gradient(135deg,#ede9fe,#ddd6fe)">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="9"/>
        <path d="M12 7v5l3 2"/>
      </svg>
    </div>
    <h2 style="font-size:22px;margin:0 0 10px">Too many requests</h2>
    <p style="color:var(--text-secondary);font-size:14px;max-width:460px;margin:0 auto 26px">
      You're sending too many requests at once. Please wait a moment and try again.
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
  .confirm-icon{width:60px;height:60px;border-radius:16px;display:flex;align-items:center;justify-content:center;color:var(--danger);box-shadow:0 8px 24px rgba(124,58,237,.12)}
</style>
@endpush
