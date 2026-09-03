<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
  <a href="{{ route('messaging.settings') }}" class="btn {{ ($active ?? '') === 'sms' ? 'btn-accent' : 'btn-secondary' }} btn-sm">SMS Settings</a>
  <a href="{{ route('messaging.settings.email') }}" class="btn {{ ($active ?? '') === 'email' ? 'btn-accent' : 'btn-secondary' }} btn-sm">Email Settings</a>
</div>
