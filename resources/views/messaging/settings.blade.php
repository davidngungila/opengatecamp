@extends('layouts.app')
@section('title', 'Settings — Open Gate Camp Mission')
@section('crumb', 'Communication / Messaging / Settings')
@section('page_title', 'Messaging Settings')

@push('styles')
<style>
  .led{width:11px;height:11px;border-radius:50%;display:inline-block;flex:none;position:relative}
  .led-green{background:#22c55e;animation:ledPulse 1.8s ease-in-out infinite}
  .led-red{background:#ef4444;animation:ledRed 1.6s ease-in-out infinite}
  @keyframes ledPulse{
    0%{box-shadow:0 0 0 0 rgba(34,197,94,.55)}
    70%{box-shadow:0 0 0 10px rgba(34,197,94,0)}
    100%{box-shadow:0 0 0 0 rgba(34,197,94,0)}
  }
  @keyframes ledRed{
    0%{box-shadow:0 0 0 0 rgba(239,68,68,.5)}
    70%{box-shadow:0 0 0 8px rgba(239,68,68,0)}
    100%{box-shadow:0 0 0 0 rgba(239,68,68,0)}
  }
</style>
@endpush

@section('content')
<div class="fade-in">
  <div class="section-head">
    <h2>Messaging Settings</h2>
  </div>
  @if(session('error'))
  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#991b1b;font-size:13.5px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> {{ session('error') }}</div>
  @endif
  @if(session('success'))
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#166534;font-size:13.5px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M20 6L9 17l-5-5"/></svg> {{ session('success') }}</div>
  @endif

  <div class="glass-card">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px">
      <div>
        <h2 style="font-size:14.5px;margin:0 0 4px">SMS Providers</h2>
        <p style="color:var(--text-muted);font-size:13px;margin:0">Manage your <b>messaging-service.co.tz</b> API credentials. Add multiple providers and mark one as <b>Primary</b> — the primary provider is used when sending SMS.</p>
      </div>
      <button type="button" class="btn btn-accent" data-sms-open-btn data-mode="add">Add Provider</button>
    </div>

    <div style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-radius:12px;background:#0f172a;border:1px solid rgba(255,255,255,.08);margin-bottom:16px">
      <span class="led {{ $smsConfigured ? 'led-green' : 'led-red' }}"></span>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <span style="font-size:13px;font-weight:800;color:#fff">API Configured</span>
        <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;padding:2px 10px;border-radius:999px;{{ $smsConfigured ? 'color:#4ade80;background:rgba(34,197,94,.12)' : 'color:#f87171;background:rgba(239,68,68,.12)' }}">
          {{ $smsConfigured ? 'Connected · Live' : 'Not connected' }}
        </span>
      </div>
    </div>

    <div class="table-card" style="box-shadow:none;border:1px solid var(--border,#e5e7eb)">
      <div class="table-scroll">
        <table class="data-table">
          <thead><tr><th>Provider</th><th>Sender ID</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
          <tbody>
            @forelse($providers as $p)
            <tr style="cursor:pointer" data-sms-open-row
                data-mode="edit"
                data-name="{{ $p['name'] }}"
                data-sender_id="{{ $p['sender_id'] ?? '' }}"
                data-api_token="{{ $p['api_token'] ?? '' }}"
                data-key="{{ $p['key'] }}">
              <td>
                <div class="cell-user">
                  <div class="cell-avatar">{{ Str::limit($p['name'], 2, '') }}</div>
                  <div><div class="cu-name">{{ $p['name'] }}</div></div>
                </div>
              </td>
              <td style="font-family:monospace;font-size:13px">{{ $p['sender_id'] ?? '—' }}</td>
              <td>
                @if($p['is_primary'] ?? false)
                <span class="badge badge-success">Primary</span>
                @else
                <span class="badge badge-neutral">Standby</span>
                @endif
              </td>
              <td style="text-align:right">
                <div class="flex gap-8" style="align-items:center;justify-content:flex-end;gap:8px">
                  <button type="button" class="btn btn-ghost btn-sm" data-sms-open-btn
                    data-mode="edit"
                    data-name="{{ $p['name'] }}"
                    data-sender_id="{{ $p['sender_id'] ?? '' }}"
                    data-api_token="{{ $p['api_token'] ?? '' }}"
                    data-key="{{ $p['key'] }}">View / Edit</button>
                  <button type="button" class="btn btn-secondary btn-sm" data-sms-test-open
                    data-key="{{ $p['key'] }}" data-name="{{ $p['name'] }}">Test</button>
                  @unless($p['is_primary'] ?? false)
                  <form method="POST" action="{{ route('messaging.settings.sms.provider.primary', $p['key']) }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">Set Primary</button>
                  </form>
                  @endunless
                  <form method="POST" action="{{ route('messaging.settings.sms.provider.delete', $p['key']) }}"
                        data-confirm data-confirm-title="Remove provider?" data-confirm-message="This will remove '{{ $p['name'] }}'. Any other provider marked Primary will be used for sending instead.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
            @empty
            <tr><td colspan="4"><div class="empty-state"><h3>No SMS providers yet</h3><p>Click <b>Add Provider</b> to configure your first SMS API credential.</p></div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="smsProviderDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="spTitle">Add SMS Provider</h3><p class="cu-sub" id="spSub">SMS API credentials</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="spForm">
      @csrf
      <input type="hidden" name="key" id="spKey">
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full">
            <label>Provider Name *</label>
            <input name="name" id="spName" required placeholder="e.g. Primary Gateway">
          </div>
          <div class="field full">
            <label>API Token *</label>
            <input name="api_token" id="spToken" required placeholder="Paste your API token here" style="font-family:monospace;font-size:13px">
          </div>
          <div class="field full">
            <label>Sender ID</label>
            <input name="sender_id" id="spSender" placeholder="e.g. TMCS MoCU">
            <small style="color:var(--text-muted);margin-top:4px;display:block">Registered sender ID on the SMS gateway. Default: TMCS MoCU</small>
          </div>
          <div class="field full" id="spPrimaryWrap">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
              <input type="checkbox" name="set_primary" value="1" id="spPrimary">
              <span>Set as Primary provider</span>
            </label>
            <small style="color:var(--text-muted);margin-top:4px;display:block">Primary is used for all outgoing SMS. If this is the only/first provider it becomes Primary automatically.</small>
          </div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-ghost" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent" id="spSubmit">Save Provider</button>
      </div>
    </form>
  </div>
</div>

<div class="drawer-overlay" id="testSmsDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="tsTitle">Test SMS</h3><p class="cu-sub" id="tsSub">Send a test via the selected provider</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('messaging.settings.sms.provider.test', '__KEY__') }}" id="tsForm">
      @csrf
      <input type="hidden" name="key" id="tsKey">
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full">
            <label>Phone Number</label>
            <input name="phone" required placeholder="e.g. 0622239304" style="font-family:monospace;font-size:15px;letter-spacing:0.5px">
          </div>
          <div class="field full">
            <label>Message</label>
            <textarea name="message" rows="4" required placeholder="Test message" style="width:100%;padding:12px;border:1px solid var(--border,#e5e7eb);border-radius:10px;background:#fff;color:var(--text-primary);font-size:14px;resize:vertical">Test SMS from Open Gate Camp Mission Management System</textarea>
          </div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-ghost" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent" id="tsSubmit">Send Test SMS</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
function spawnSmsFrom(el){
  var isEdit = el.getAttribute('data-mode') === 'edit';
  var form = document.getElementById('spForm');

  document.getElementById('spTitle').textContent = isEdit ? 'Edit SMS Provider' : 'Add SMS Provider';
  document.getElementById('spSub').textContent = isEdit ? 'SMS API credentials' : 'New SMS API credentials';
  document.getElementById('spSubmit').textContent = isEdit ? 'Update Provider' : 'Save Provider';
  document.getElementById('spKey').value = isEdit ? (el.getAttribute('data-key') || '') : '';
  document.getElementById('spName').value = isEdit ? (el.getAttribute('data-name') || '') : '';
  document.getElementById('spToken').value = isEdit ? (el.getAttribute('data-api_token') || '') : '';
  document.getElementById('spSender').value = isEdit ? (el.getAttribute('data-sender_id') || '') : '';
  document.getElementById('spPrimary').checked = false;
  document.getElementById('spPrimaryWrap').style.display = isEdit ? 'none' : '';

  form.action = isEdit
    ? '{{ route("messaging.settings.sms.provider.update", "__KEY__") }}'.replace('__KEY__', encodeURIComponent(document.getElementById('spKey').value))
    : '{{ route("messaging.settings.sms.provider.store") }}';

  openDrawerById('smsProviderDrawer');
}

document.addEventListener('click', function(e){
  var btn = e.target.closest('[data-sms-open-btn]');
  if(btn){ e.preventDefault(); spawnSmsFrom(btn); return; }
  if(e.target.closest('button, form, a, input, select, textarea')) return;
  var row = e.target.closest('[data-sms-open-row]');
  if(row){ spawnSmsFrom(row); }
});

document.addEventListener('click', function(e){
  var btn = e.target.closest('[data-sms-test-open]');
  if(!btn) return;
  e.preventDefault();
  var key = btn.getAttribute('data-key') || '';
  var name = btn.getAttribute('data-name') || 'provider';
  document.getElementById('tsKey').value = key;
  document.getElementById('tsSub').textContent = 'Send a test via ' + name;
  document.getElementById('tsTitle').textContent = 'Test SMS — ' + name;
  document.getElementById('tsForm').action =
    '{{ route("messaging.settings.sms.provider.test", "__KEY__") }}'.replace('__KEY__', encodeURIComponent(key));
  openDrawerById('testSmsDrawer');
});
</script>
@endpush
