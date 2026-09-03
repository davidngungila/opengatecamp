<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:16px">
  <a href="{{ route('messaging.settings') }}" class="btn {{ ($active ?? '') === 'sms' ? 'btn-accent' : 'btn-secondary' }} btn-sm">SMS Settings</a>
  <a href="{{ route('messaging.settings.email') }}" class="btn {{ ($active ?? '') === 'email' ? 'btn-accent' : 'btn-secondary' }} btn-sm">Email Settings</a>
  @if(array_key_exists('configured', get_defined_vars()))
    @php $label = ($active ?? '') === 'email' ? 'SMTP Configured' : 'API Configured'; @endphp
    @if($configured)
    <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:var(--green-light);border:1px solid rgba(22,163,74,.15);border-radius:8px">
      <span style="color:var(--green-accent);font-size:13px;font-weight:700"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px"><path d="M20 6L9 17l-5-5"/></svg> {{ $label }}</span>
    </span>
    @else
    <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px">
      <span style="color:#991b1b;font-size:13px;font-weight:700"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Not configured</span>
    </span>
    @endif
  @endif
</div>
