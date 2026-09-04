@extends('layouts.app')

@section('title', 'Maintenance — OpenGate Camp Connect')
@section('crumb', 'Error / 503')
@section('page_title', 'Maintenance')

@section('content')
<div class="fade-in">
  <div class="error-wrap">
    <div class="error-code">503</div>
    <div class="confirm-icon" style="margin:0 auto 18px;background:linear-gradient(135deg,#e0e7ff,#c7d2fe)">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>
      </svg>
    </div>
    <h2 style="font-size:22px;margin:0 0 10px">Under maintenance</h2>
    <p style="color:var(--text-secondary);font-size:14px;max-width:460px;margin:0 auto 26px">
      The system is temporarily unavailable for maintenance. Please check back shortly.
    </p>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
      <a class="btn btn-primary" href="javascript:location.reload()">Try again</a>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .error-wrap{text-align:center;padding:70px 20px;display:flex;flex-direction:column;align-items:center}
  .error-code{font-size:84px;font-weight:900;line-height:1;letter-spacing:-3px;color:var(--text-tertiary);opacity:.35;margin-bottom:8px}
  .confirm-icon{width:60px;height:60px;border-radius:16px;display:flex;align-items:center;justify-content:center;color:var(--danger);box-shadow:0 8px 24px rgba(79,70,229,.12)}
</style>
@endpush
