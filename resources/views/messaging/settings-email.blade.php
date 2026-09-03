@extends('layouts.app')
@section('title', 'Email Settings — Open Gate Camp Mission')
@section('crumb', 'Communication / Messaging / Email Settings')
@section('page_title', 'Email Settings')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <h2>Email Settings</h2>
    <p style="color:var(--text-secondary);font-size:13px;margin:4px 0 0">Configure SMTP providers for outbound emails.</p>
  </div>
  @include('messaging.partials.settings-nav', ['active' => 'email'])

  @if(session('error'))
  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#991b1b;font-size:13.5px">⚠ {{ session('error') }}</div>
  @endif
  @if(session('success'))
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#166534;font-size:13.5px">✓ {{ session('success') }}</div>
  @endif

  <div class="glass-card">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px">
      <div>
        <h2 style="font-size:14.5px;margin:0 0 4px">Email (SMTP) Providers</h2>
        <p style="color:var(--text-muted);font-size:13px;margin:0">Manage SMTP server credentials. Add multiple providers and mark one as <b>Primary</b> — the primary provider is used for outbound email.</p>
      </div>
      <button type="button" class="btn btn-accent" data-ep-open-btn data-mode="add">Add Provider</button>
    </div>

    @php $mailConfigured = !empty($providers) && collect($providers)->firstWhere('is_primary', true); @endphp
    @if($mailConfigured)
    <div style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:var(--green-light);border:1px solid rgba(22,163,74,.15);border-radius:8px;margin:0 0 18px">
      <span style="color:var(--green-accent);font-size:13px;font-weight:700">✓ SMTP Configured — {{ $mailConfigured['host'] }}:{{ $mailConfigured['port'] }}</span>
    </div>
    @else
    <div style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;margin:0 0 18px">
      <span style="color:#991b1b;font-size:13px;font-weight:700">⌾ Not configured</span>
    </div>
    @endif

    <div class="table-card" style="box-shadow:none;border:1px solid var(--border,#e5e7eb)">
      <div class="table-scroll">
        <table class="data-table">
          <thead><tr><th>Provider</th><th>Host</th><th>From</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
          <tbody>
            @forelse($providers as $p)
            <tr style="cursor:pointer" data-ep-open-row
                data-mode="edit"
                data-name="{{ $p['name'] }}"
                data-host="{{ $p['host'] ?? '' }}"
                data-port="{{ $p['port'] ?? '' }}"
                data-username="{{ $p['username'] ?? '' }}"
                data-password="{{ $p['password'] ?? '' }}"
                data-encryption="{{ $p['encryption'] ?? 'tls' }}"
                data-from_address="{{ $p['from_address'] ?? '' }}"
                data-from_name="{{ $p['from_name'] ?? '' }}"
                data-key="{{ $p['key'] }}">
              <td>
                <div class="cell-user">
                  <div class="cell-avatar">{{ Str::limit($p['name'], 2, '') }}</div>
                  <div><div class="cu-name">{{ $p['name'] }}</div></div>
                </div>
              </td>
              <td style="font-family:monospace;font-size:13px">{{ $p['host'] ?? '—' }}{{ !empty($p['port']) ? ':'.$p['port'] : '' }}</td>
              <td style="font-size:13px">{{ $p['from_address'] ?: '—' }}</td>
              <td>
                @if($p['is_primary'] ?? false)
                <span class="badge badge-success">Primary</span>
                @else
                <span class="badge badge-neutral">Standby</span>
                @endif
              </td>
              <td style="text-align:right">
                <div class="flex gap-8" style="align-items:center;justify-content:flex-end;gap:8px">
                  <button type="button" class="btn btn-ghost btn-sm" data-ep-open-btn
                    data-mode="edit"
                    data-name="{{ $p['name'] }}"
                    data-host="{{ $p['host'] ?? '' }}"
                    data-port="{{ $p['port'] ?? '' }}"
                    data-username="{{ $p['username'] ?? '' }}"
                    data-password="{{ $p['password'] ?? '' }}"
                    data-encryption="{{ $p['encryption'] ?? 'tls' }}"
                    data-from_address="{{ $p['from_address'] ?? '' }}"
                    data-from_name="{{ $p['from_name'] ?? '' }}"
                    data-key="{{ $p['key'] }}">View / Edit</button>
                  @unless($p['is_primary'] ?? false)
                  <form method="POST" action="{{ route('messaging.settings.email.provider.primary', $p['key']) }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">Set Primary</button>
                  </form>
                  @endunless
                  <form method="POST" action="{{ route('messaging.settings.email.provider.delete', $p['key']) }}"
                        data-confirm data-confirm-title="Remove provider?" data-confirm-message="This will remove '{{ $p['name'] }}'. Any other provider marked Primary will be used for sending instead.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
            @empty
            <tr><td colspan="5"><div class="empty-state"><h3>No email providers yet</h3><p>Click <b>Add Provider</b> to configure your first SMTP server.</p></div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="epDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="epTitle">Add Email Provider</h3><p class="cu-sub" id="epSub">SMTP server credentials</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="epForm">
      @csrf
      <input type="hidden" name="key" id="epKey">
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full">
            <label>Provider Name *</label>
            <input name="name" id="epName" required placeholder="e.g. Gmail SMTP">
          </div>
          <div class="field">
            <label>SMTP Host *</label>
            <input name="host" id="epHost" required placeholder="e.g. smtp.gmail.com">
          </div>
          <div class="field">
            <label>Port</label>
            <input name="port" id="epPort" type="number" placeholder="587">
          </div>
          <div class="field">
            <label>Encryption</label>
            <select name="encryption" id="epEncryption">
              @foreach(['tls'=>'TLS','ssl'=>'SSL','none'=>'None'] as $val => $label)
              <option value="{{ $val }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label>SMTP Username</label>
            <input name="username" id="epUsername" placeholder="you@example.com">
          </div>
          <div class="field">
            <label>SMTP Password</label>
            <input name="password" id="epPassword" type="password" placeholder="••••••••">
          </div>
          <div class="field full">
            <label>From Email Address</label>
            <input name="from_address" id="epFromAddress" placeholder="info@opengatecamp.org">
          </div>
          <div class="field full">
            <label>From Name</label>
            <input name="from_name" id="epFromName" placeholder="Open Gate Camp Mission">
          </div>
          <div class="field full" id="epPrimaryWrap">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
              <input type="checkbox" name="set_primary" value="1" id="epPrimary">
              <span>Set as Primary provider</span>
            </label>
            <small style="color:var(--text-muted);margin-top:4px;display:block">Primary is used for all outbound email. If this is the only/first provider it becomes Primary automatically.</small>
          </div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-ghost" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent" id="epSubmit">Save Provider</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
function spawnEpFrom(el){
  var isEdit = el.getAttribute('data-mode') === 'edit';
  var form = document.getElementById('epForm');

  document.getElementById('epTitle').textContent = isEdit ? 'Edit Email Provider' : 'Add Email Provider';
  document.getElementById('epSub').textContent = isEdit ? 'SMTP server credentials' : 'New SMTP server credentials';
  document.getElementById('epSubmit').textContent = isEdit ? 'Update Provider' : 'Save Provider';
  document.getElementById('epKey').value = isEdit ? (el.getAttribute('data-key') || '') : '';
  document.getElementById('epName').value = isEdit ? (el.getAttribute('data-name') || '') : '';
  document.getElementById('epHost').value = isEdit ? (el.getAttribute('data-host') || '') : '';
  document.getElementById('epPort').value = isEdit ? (el.getAttribute('data-port') || '') : '';
  document.getElementById('epUsername').value = isEdit ? (el.getAttribute('data-username') || '') : '';
  document.getElementById('epPassword').value = isEdit ? (el.getAttribute('data-password') || '') : '';
  document.getElementById('epEncryption').value = isEdit ? (el.getAttribute('data-encryption') || 'tls') : 'tls';
  document.getElementById('epFromAddress').value = isEdit ? (el.getAttribute('data-from_address') || '') : '';
  document.getElementById('epFromName').value = isEdit ? (el.getAttribute('data-from_name') || '') : '';
  document.getElementById('epPrimary').checked = false;
  document.getElementById('epPrimaryWrap').style.display = isEdit ? 'none' : '';

  form.action = isEdit
    ? '{{ route("messaging.settings.email.provider.update", "__KEY__") }}'.replace('__KEY__', encodeURIComponent(document.getElementById('epKey').value))
    : '{{ route("messaging.settings.email.provider.store") }}';

  openDrawerById('epDrawer');
}

document.addEventListener('click', function(e){
  var btn = e.target.closest('[data-ep-open-btn]');
  if(btn){ e.preventDefault(); spawnEpFrom(btn); return; }
  if(e.target.closest('button, form, a, input, select, textarea')) return;
  var row = e.target.closest('[data-ep-open-row]');
  if(row){ spawnEpFrom(row); }
});
</script>
@endpush
