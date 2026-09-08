@extends('layouts.app')

@section('title', 'Permissions — OpenGate Camp Connect')
@section('crumb', 'System / Permissions')
@section('page_title', 'Permissions')

@php
    $permLabels = [
        'members.view'=>'View Members','members.manage'=>'Manage Members',
        'events.manage'=>'Manage Events','events.complete'=>'Complete Events',
        'pledges.manage'=>'Manage Pledges',
        'finance.view'=>'View Finance','finance.manage'=>'Manage Finance','finance.approve'=>'Approve Finance',
        'communication.send'=>'Send Communication',
        'documents.view'=>'View Documents','documents.manage'=>'Manage Documents',
        'reports.view'=>'View Reports','reports.export'=>'Export Reports',
        'users.manage'=>'Manage Users','roles.manage'=>'Manage Roles','settings.manage'=>'Manage Settings','audit.view'=>'View Audit Logs',
    ];
    $permLabel = fn($key) => $permLabels[$key] ?? $key;
    $rolesJson = json_encode($roles->map(fn($r) => [
        'id' => $r->id,
        'name' => $r->name,
        'users' => $r->users_count,
        'super' => $r->is_super ? 1 : 0,
        'perms' => $r->permissions ?? [],
    ])->values());
@endphp

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Permissions</h2><div class="sub">{{ count($permissions) }} permissions · {{ $roles->count() }} roles</div></div>
    <div class="flex gap-8">
      <a href="{{ route('users.index') }}" class="btn btn-secondary" style="text-decoration:none">Users</a>
      <a href="{{ route('users.roles') }}" class="btn btn-secondary" style="text-decoration:none">Roles</a>
    </div>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Permission</th><th>Key</th><th>Granted to</th><th style="width:90px">Details</th></tr></thead>
        <tbody>
          @foreach($permissions as $perm)
          @php
            $grantees = $roles->filter(fn($r) => $r->is_super || in_array($perm, $r->permissions ?? []));
          @endphp
          <tr style="cursor:pointer" data-perm-open data-perm-key="{{ $perm }}" data-perm-label="{{ $permLabel($perm) }}">
            <td><b style="font-size:13px">{{ $permLabel($perm) }}</b></td>
            <td><code style="font-size:11px">{{ $perm }}</code></td>
            <td>
              @if($grantees->isEmpty())
                <span class="badge badge-neutral badge-dotted">Not granted</span>
              @else
                <div class="perm-chips" style="max-width:600px">
                  @foreach($grantees as $r)
                  <code class="perm-chip" style="background:rgba(16,185,129,.08);color:var(--success);border-color:rgba(16,185,129,.18)">{{ $r->name }}</code>
                  @endforeach
                </div>
              @endif
            </td>
            <td><button type="button" class="btn btn-secondary btn-sm" data-perm-open>Details</button></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="permDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="permDrawerName">Permission Details</h3><p id="permDrawerKey" class="badge badge-purple badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="profile-detail">
        <div class="avatar avatar-lg" style="background:var(--blue-light);color:var(--blue-accent)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>
        <div>
          <div style="font-size:16px;font-weight:800" id="permDrawerNameVal">—</div>
          <div style="font-size:12.5px;color:var(--text-tertiary);font-weight:600;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace" id="permDrawerKeyVal">—</div>
        </div>
      </div>

      <div class="payments-head">
        <span>Roles granted this permission</span><span class="payments-count" id="permRoleCount">0</span>
      </div>
      <div class="field" style="margin-bottom:12px">
        <small style="color:var(--text-muted)">Tick or untick roles, then <b>Save</b>. The Super Administrator always has all permissions.</small>
      </div>
      <div id="permRoleList" class="payments-list"></div>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
      <button type="button" class="btn btn-accent" id="permSaveBtn">Save Permissions</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
var __permLabel = {
  'members.view':'View Members','members.manage':'Manage Members',
  'events.manage':'Manage Events','events.complete':'Complete Events',
  'pledges.manage':'Manage Pledges',
  'finance.view':'View Finance','finance.manage':'Manage Finance','finance.approve':'Approve Finance',
  'communication.send':'Send Communication',
  'documents.view':'View Documents','documents.manage':'Manage Documents',
  'reports.view':'View Reports','reports.export':'Export Reports',
  'users.manage':'Manage Users','roles.manage':'Manage Roles','settings.manage':'Manage Settings','audit.view':'View Audit Logs'
};
var __permsRoles = {!! $rolesJson !!};

function permLabel(key){ return __permLabel[key] || key; }

var __permOpenKey = null;

function openPermDrawer(key, label){
  __permOpenKey = key;
  document.getElementById('permDrawerName').textContent = label || permLabel(key);
  document.getElementById('permDrawerNameVal').textContent = label || permLabel(key);
  document.getElementById('permDrawerKey').textContent = key;
  document.getElementById('permDrawerKeyVal').textContent = key;

  var list = document.getElementById('permRoleList');
  list.innerHTML = '';
  var grantedCount = 0;
  __permsRoles.forEach(function(role, i){
    var granted = role.super === 1 || role.perms.indexOf(key) !== -1;
    if(granted) grantedCount++;
    var item = document.createElement('div');
    item.className = 'pay-item';
    item.dataset.role = role.id;
    item.dataset.perms = JSON.stringify(role.perms);
    item.dataset.super = role.super;
    item.innerHTML =
      '<div class="pay-ico" style="background:var(--purple-bg);color:var(--purple)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>' +
      '<div class="pay-main"><div class="pm-name">' + (role.super === 1 ? role.name + ' <span style="color:var(--text-tertiary);font-size:11px">(all access)</span>' : role.name) + '</div><div class="pm-sub">' + role.users + ' user(s)</div></div>' +
      '<div class="pay-amt"><label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:var(--text-secondary);cursor:pointer"><input type="checkbox" class="checkbox perm-role-box" ' + (granted ? 'checked' : '') + (role.super === 1 ? ' disabled' : '') + '> Granted</label></div>';
    list.appendChild(item);
  });
  document.getElementById('permRoleCount').textContent = grantedCount + ' of ' + __permsRoles.length;
  openDrawerById('permDetailDrawer');
}

document.addEventListener('click', function(e){
  var el = e.target.closest('[data-perm-open]');
  if(!el) return;
  if(e.target.closest('a') || e.target.closest('input')) return;
  e.stopPropagation();
  openPermDrawer(el.getAttribute('data-perm-key'), el.getAttribute('data-perm-label'));
});

document.getElementById('permSaveBtn').addEventListener('click', function(){
  var key = __permOpenKey;
  if(!key) return;
  var updates = [];
  document.querySelectorAll('#permRoleList .pay-item').forEach(function(item){
    if(item.dataset.super === '1') return;
    var cb = item.querySelector('.perm-role-box');
    var granted = cb.checked;
    var perms = [];
    try { perms = JSON.parse(item.dataset.perms); } catch(ex) { return; }
    var has = perms.indexOf(key) !== -1;
    if(granted === has) return;
    granted ? perms.push(key) : perms = perms.filter(function(p){ return p !== key; });
    updates.push({ role: item.dataset.role, permissions: perms });
  });
  if(updates.length === 0){ toast('No changes detected','info'); return; }
  var btn = this;
  btn.disabled = true;
  btn.textContent = 'Saving...';
  toast('Saving permissions for ' + updates.length + ' role(s)...','info');
  (function next(){
    var upd = updates.shift();
    if(upd === undefined){
      btn.disabled = false;
      btn.textContent = 'Save Permissions';
      setTimeout(function(){ location.reload(); }, 600);
      return;
    }
    fetch('{{ url('/roles') }}/' + upd.role + '/permissions', {
      method:'PUT',
      headers:{
        'Content-Type':'application/json',
        'X-CSRF-TOKEN':'{{ csrf_token() }}',
        'Accept':'application/json'
      },
      body: JSON.stringify({ permissions: upd.permissions })
    }).then(next).catch(function(){
      btn.disabled = false;
      btn.textContent = 'Save Permissions';
      toast('Failed to save permissions','error');
    });
  })();
});
</script>
@endpush