@extends('layouts.app')
@section('title', 'Templates — OpenGate Camp Connect')
@section('crumb', 'Communication / Messaging / Templates')
@section('page_title', 'Message Templates')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Message Templates</h2><div class="sub">{{ $templates->count() }} saved · reusable for SMS or Email</div></div>
    <button type="button" class="btn btn-accent" data-drawer-open="tplNewDrawer">+ New Template</button>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Template</th><th>Message</th><th>Created</th><th style="width:90px">Actions</th></tr></thead>
        <tbody>
          @forelse($templates as $t)
          <tr style="cursor:pointer" data-view-tpl data-id="{{ $t->id }}">
            <td>
              <div class="cell-user">
                <div class="cell-avatar">{{ collect(explode(' ', $t->name ?? '?'))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}</div>
                <div>
                  <div class="cu-name">{{ $t->name }}</div>
                  <div class="cu-sub">Saved by {{ $t->created_by ?? 'System' }}@if($t->key) · <code style="font-size:11px">{{ $t->key }}</code>@endif</div>
                </div>
              </div>
            </td>
            <td>
              <div style="max-width:520px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;color:var(--text-secondary);font-size:12.5px;line-height:1.6">{{ $t->message }}</div>
            </td>
            <td><span class="badge badge-neutral badge-dotted">{{ $t->created_at?->format('d M Y') }}</span></td>
            <td onclick="event.stopPropagation()">
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-tpl-{{ $t->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-tpl-{{ $t->id }}">
                  <form method="POST" action="{{ route('messaging.use-template') }}" style="display:contents">@csrf
                    <input type="hidden" name="template" value="{{ $t->message }}">
                    <input type="hidden" name="name" value="{{ $t->name }}">
                    <button type="submit">Use in SMS</button>
                  </form>
                  <button type="button" data-tpl-details data-id="{{ $t->id }}">Details</button>
                  <button type="button" data-tpl-copy data-text="{{ $t->message }}">Copy</button>
                  <button type="button" data-tpl-edit data-id="{{ $t->id }}">Edit</button>
                  <form method="POST" action="{{ route('messaging.templates.destroy', $t->id) }}" data-confirm
                        data-confirm-title="Delete template?"
                        data-confirm-message="This template will be permanently removed. This cannot be undone."
                        data-confirm-label="Delete">@csrf @method('DELETE')
                    <button type="submit" class="danger">Delete</button>
                  </form>
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="4"><div class="empty-state" style="padding:40px 20px"><h3>No templates yet</h3><p>Create your first reusable message template.</p><button type="button" class="btn btn-accent" data-drawer-open="tplNewDrawer">+ New Template</button></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="tf-info">{{ $templates->count() }} template(s) total · Templates saved by <b>System</b> are built-in defaults. Use <b>Use in SMS</b> to load a template into the SMS composer.</span>
    </div>
  </div>
</div>

{{-- New template drawer --}}
<div class="drawer-overlay" id="tplNewDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>New Template</h3><p>Templates are saved to the database and reused for SMS or Email.</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('messaging.templates.store') }}">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><label>Template Name *</label>
            <input name="name" required maxlength="120" placeholder="e.g. Pledge Reminder" value="{{ old('name') }}">
          </div>
          <div class="field full"><label>Message *</label>
            <textarea name="message" required maxlength="2000" placeholder="Type your template here. You can use placeholders like {name}, {event}, {date}..." style="min-height:110px" id="newTplMsg" oninput="updateTplCount()">{{ old('message') }}</textarea>
            <div style="display:flex;justify-content:space-between;margin-top:4px">
              <small style="color:var(--text-muted)">Placeholders: {name} {event} {year} {venue} {amount} {paid} {remaining} {link}</small>
              <small id="tplCount" style="font-weight:700;color:var(--text-secondary)">0 / 2000</small>
            </div>
          </div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Save Template</button>
      </div>
    </form>
  </div>
</div>

{{-- Edit template drawer --}}
<div class="drawer-overlay" id="tplEditDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Edit Template</h3><p>Update the name and message. Saving updates all future messages sent from this template.</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="tplEditForm">
      @csrf
      @method('PUT')
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><label>Template Name *</label>
            <input name="name" required maxlength="120" id="tplEditName">
          </div>
          <div class="field full"><label>Message *</label>
            <textarea name="message" required maxlength="2000" style="min-height:110px" id="tplEditMsg">{{ old('message') }}</textarea>
            <div style="display:flex;justify-content:space-between;margin-top:4px">
              <small style="color:var(--text-muted)">Placeholders: {name} {event} {year} {venue} {amount} {paid} {remaining} {link}</small>
              <small id="tplEditCount" style="font-weight:700;color:var(--text-secondary)">0 / 2000</small>
            </div>
          </div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Save Changes</button>
      </div>
    </form>
  </div>
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
        <p style="font-size:12.5px;color:var(--text-secondary);margin:6px 0 0;line-height:1.7">You can use <code>{name}</code>, <code>{event}</code>, <code>{year}</code>, <code>{venue}</code>, <code>{amount}</code>, <code>{paid}</code>, <code>{remaining}</code> and <code>{link}</code>. They are replaced with real member data when sending.</p>
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
        'url'        => route('messaging.templates.update', $t->id),
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

function openTplEdit(id) {
  var d = TPL_DATA[id];
  if (!d) return;
  document.getElementById('tplEditName').value = d.name;
  document.getElementById('tplEditMsg').value = d.message;
  document.getElementById('tplEditForm').action = d.url;
  updateTplEditCount();
  openDrawerById('tplEditDrawer');
}

function updateTplEditCount() {
  var el = document.getElementById('tplEditMsg');
  var out = document.getElementById('tplEditCount');
  if (el && out) out.textContent = el.value.length + ' / 2000';
}

document.addEventListener('DOMContentLoaded', function(){
  updateTplCount();
  updateTplEditCount();

  document.querySelectorAll('[data-tpl-edit]').forEach(function(btn){
    btn.addEventListener('click', function(){
      openTplEdit(btn.dataset.id);
    });
  });

  document.querySelectorAll('[data-view-tpl]').forEach(function(tr){
    tr.addEventListener('click', function(e){
      if(e.target.closest('.action-menu-wrap') || e.target.closest('form') || e.target.closest('button') || e.target.closest('a')) return;
      openTplDetails(tr.dataset.id);
    });
  });

  document.querySelectorAll('[data-tpl-details]').forEach(function(btn){
    btn.addEventListener('click', function(){
      openTplDetails(btn.dataset.id);
    });
  });

  document.querySelectorAll('[data-tpl-copy]').forEach(function(btn){
    btn.addEventListener('click', function(){
      copyTemplate(this);
    });
  });
});
</script>
@endpush