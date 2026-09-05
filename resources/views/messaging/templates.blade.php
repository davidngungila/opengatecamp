@extends('layouts.app')
@section('title', 'Templates — OpenGate Camp Connect')
@section('crumb', 'Communication / Messaging / Templates')
@section('page_title', 'Message Templates')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <h2>Message Templates</h2>
    <span class="badge badge-neutral">{{ $templates->count() }} saved</span>
  </div>

  @if(session('error'))
  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#991b1b;font-size:13.5px">{{ session('error') }}</div>
  @endif
  @if(session('success'))
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#166534;font-size:13.5px">{{ session('success') }}</div>
  @endif

  <div class="glass-card" style="margin-bottom:20px">
    <h2 style="font-size:14.5px;margin:0 0 4px">New Template</h2>
    <p style="font-size:12.5px;color:var(--text-tertiary);margin:0 0 14px">Templates are saved to the database and can be reused for SMS or Email.</p>
    <form method="POST" action="{{ route('messaging.templates.store') }}" class="form-grid">
      @csrf
      <div class="field">
        <label>Template Name *</label>
        <input name="name" required maxlength="120" placeholder="e.g. Pledge Reminder" value="{{ old('name') }}">
      </div>
      <div class="field full">
        <label>Message *</label>
        <textarea name="message" required maxlength="2000" placeholder="Type your template here. You can use placeholders like {name}, {event}, {date}..." style="min-height:110px" id="newTplMsg" oninput="updateTplCount()">{{ old('message') }}</textarea>
        <div style="display:flex;justify-content:space-between;margin-top:4px">
          <small style="color:var(--text-muted)">Placeholders: {name} {event} {date} {venue} {campaign} {balance} {amount} {sacrament}</small>
          <small id="tplCount" style="font-weight:700;color:var(--text-secondary)">0 / 2000</small>
        </div>
      </div>
      <div class="field full" style="display:flex;justify-content:flex-end;gap:8px">
        <button type="submit" class="btn btn-accent">Save Template</button>
      </div>
    </form>
  </div>

  <div class="msg-templates">
    @forelse($templates as $t)
    <div class="tpl-card">
      <div class="flex gap-8" style="align-items:center;justify-content:space-between;gap:8px">
        <h5 style="margin:0;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $t->name }}">{{ $t->name }}</h5>
        <span class="badge badge-neutral badge-dotted" style="font-size:10px">{{ $t->created_at?->format('d M Y') }}</span>
      </div>
      <p style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden">{{ $t->message }}</p>
      <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
        <form method="POST" action="{{ route('messaging.use-template') }}" style="display:inline">
          @csrf
          <input type="hidden" name="template" value="{{ $t->message }}">
          <input type="hidden" name="name" value="{{ $t->name }}">
          <button type="submit" class="btn btn-ghost btn-sm" style="padding:6px 12px">Use in SMS</button>
        </form>
        <button type="button" class="btn btn-primary btn-sm" style="padding:6px 12px" onclick="openTplDetails({{ $t->id }})">Details</button>
        <button type="button" class="btn btn-ghost btn-sm" style="padding:6px 12px;color:var(--blue-accent)" onclick="copyTemplate(this)" data-text="{{ $t->message }}">Copy</button>
        <form method="POST" action="{{ route('messaging.templates.destroy', $t->id) }}" style="display:inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-ghost btn-sm" style="padding:6px 12px;color:var(--red)"
                  data-confirm data-confirm-title="Delete template?"
                  data-confirm-message="This template will be permanently removed. This cannot be undone."
                  data-confirm-label="Delete">Delete</button>
        </form>
      </div>
    </div>
    @empty
    <div class="empty-state" style="grid-column:1/-1;padding:30px 16px"><p>No templates yet. Create one above.</p></div>
    @endforelse
  </div>

  <p style="font-size:12px;color:var(--text-tertiary);margin-top:16px">Templates marked as saved by <b>System</b> are built-in defaults. Use <b>Use in SMS</b> to load a template into the SMS composer.</p>
</div>

{{-- Template detail drawer --}}
<div class="drawer-overlay" id="tplDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="tplDName">—</h3><p id="tplDMeta" class="cu-sub">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div style="font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--text-tertiary)">Message</div>
      <div style="margin-top:8px;background:var(--bg-muted,#f8fafc);border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:16px 18px;white-space:pre-wrap;word-break:break-word;line-height:1.7;font-size:14px" id="tplDBody">—</div>
      <div style="margin-top:18px">
        <div style="font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--text-tertiary)">Placeholders</div>
        <p style="font-size:12.5px;color:var(--text-secondary);margin:6px 0 0;line-height:1.7">You can use <code>{name}</code>, <code>{event}</code>, <code>{date}</code>, <code>{venue}</code>, <code>{campaign}</code>, <code>{balance}</code>, <code>{amount}</code> and <code>{sacrament}</code>. They are replaced with real member data when sending.</p>
      </div>
      <details style="margin-top:18px;border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:14px 18px">
        <summary style="cursor:pointer;font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--text-secondary)">Template info</summary>
        <div style="margin-top:12px;display:grid;grid-template-columns:auto 1fr;gap:8px 18px;font-size:13px">
          <div style="color:var(--text-tertiary)">Name</div><div id="tplDInfoName">—</div>
          <div style="color:var(--text-tertiary)">Created by</div><div id="tplDInfoBy">—</div>
          <div style="color:var(--text-tertiary)">Created</div><div id="tplDInfoAt">—</div>
        </div>
      </details>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
      <button type="button" class="btn btn-ghost" style="color:var(--blue-accent)" onclick="copyTplFromDrawer()">Copy</button>
      <form method="POST" action="{{ route('messaging.use-template') }}" style="display:inline" id="tplDForm">
        @csrf
        <input type="hidden" name="template" id="tplDFormMsg" value="">
        <input type="hidden" name="name" id="tplDFormName" value="">
        <button type="submit" class="btn btn-accent">Use in SMS</button>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
@php
    $tplData = $templates->mapWithKeys(fn ($t) => [$t->id => [
        'name'       => $t->name,
        'message'    => $t->message,
        'created_by' => $t->created_by ?? '—',
        'created_at' => $t->created_at?->format('d M Y, H:i') ?? '—',
    ]])->toArray();
    $tplDataJson = json_encode($tplData, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
@endphp
<script>
var TPL_DATA = {!! $tplDataJson !!};

function openTplDetails(id) {
  var d = TPL_DATA[id];
  if (!d) return;
  document.getElementById('tplDName').textContent = d.name;
  document.getElementById('tplDMeta').textContent = 'Saved by ' + d.created_by;
  document.getElementById('tplDBody').textContent = d.message;
  document.getElementById('tplDInfoName').textContent = d.name;
  document.getElementById('tplDInfoBy').textContent = d.created_by;
  document.getElementById('tplDInfoAt').textContent = d.created_at;
  document.getElementById('tplDFormMsg').value = d.message;
  document.getElementById('tplDFormName').value = d.name;
  openDrawerById('tplDetailDrawer');
}

function copyTemplate(btn) {
  var text = btn.getAttribute('data-text');
  navigator.clipboard.writeText(text).then(function() {
    btn.textContent = 'Copied!';
    setTimeout(function() { btn.textContent = 'Copy'; }, 1500);
  });
}

function copyTplFromDrawer() {
  var body = document.getElementById('tplDBody').textContent;
  navigator.clipboard.writeText(body).then(function() {
    toast('Template copied to clipboard', 'success');
  });
}

function updateTplCount() {
  var el = document.getElementById('newTplMsg');
  var out = document.getElementById('tplCount');
  if (el && out) out.textContent = el.value.length + ' / 2000';
}
document.addEventListener('DOMContentLoaded', updateTplCount);
</script>
@endpush