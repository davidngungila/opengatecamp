@extends('layouts.app')

@section('title', 'Roles — OpenGate Camp Connect')
@section('crumb', 'System / Roles')
@section('page_title', 'Roles')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Roles &amp; Permissions</h2><div class="sub">{{ $roles->count() }} roles · {{ count($permissions) }} permissions · {{ $roles->sum('users_count') }} users</div></div>
    <div class="flex gap-8">
      <a href="{{ route('users.index') }}" class="btn btn-secondary" style="text-decoration:none">Users</a>
      <a href="{{ route('users.permissions') }}" class="btn btn-secondary" style="text-decoration:none">Permission Matrix</a>
    </div>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Role</th><th>Permissions</th><th>Users</th><th>Access</th><th style="width:80px">Actions</th></tr></thead>
        <tbody>
          @foreach($roles as $r)
          <tr style="cursor:pointer" data-view-role
              data-id="{{ $r->id }}"
              data-name="{{ $r->name }}"
              data-super="{{ $r->is_super ? 1 : 0 }}"
              data-permissions="{{ json_encode($r->permissions ?? []) }}"
              data-users="{{ json_encode($r->users->pluck('name')->values()) }}">
            <td>
              <div class="cell-user">
                <div class="cell-avatar" style="background:var(--purple-bg);color:var(--purple)"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                <div><div class="cu-name">{{ $r->name }}</div><div class="cu-sub">Role #{{ $r->id }}</div></div>
              </div>
            </td>
            <td>
              @if($r->is_super)
                <span class="badge badge-success badge-dotted">All access</span>
              @elseif(empty($r->permissions))
                <span class="badge badge-neutral badge-dotted">No permissions</span>
              @else
                <div class="perm-chips">
                  @foreach($r->permissions as $perm)
                  <code class="perm-chip">{{ $perm }}</code>
                  @endforeach
                </div>
              @endif
            </td>
            <td><span class="badge badge-purple badge-dotted">{{ $r->users_count }}</span></td>
            <td><b>{{ $r->is_super ? count($permissions).' / '.count($permissions) : count($r->permissions ?? []).' / '.count($permissions) }}</b></td>
            <td>
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-roles-{{ $r->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-roles-{{ $r->id }}">
                  <button type="button" data-view-role
                          data-id="{{ $r->id }}"
                          data-name="{{ $r->name }}"
                          data-super="{{ $r->is_super ? 1 : 0 }}"
                          data-permissions="{{ json_encode($r->permissions ?? []) }}"
                          data-users="{{ json_encode($r->users->pluck('name')->values()) }}">View Details</button>
                  <a href="{{ route('users.permissions') }}">Edit Permissions</a>
                </div>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="roleDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="roleDrawerName">Role Details</h3><p id="roleDrawerSuper" class="badge badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="profile-detail">
        <div class="avatar avatar-lg" id="roleDrawerAvatar" style="background:var(--purple-bg);color:var(--purple)">—</div>
        <div>
          <div style="font-size:16px;font-weight:800" id="roleDrawerFullName">—</div>
          <div style="font-size:12.5px;color:var(--text-tertiary);font-weight:600" id="roleDrawerCount">—</div>
        </div>
      </div>

      <div class="payments-head">
        <span>Permissions</span><span class="payments-count" id="rolePermCount">0</span>
      </div>
      <div id="rolePermList" class="payments-list"></div>

      <div class="payments-head" style="margin-top:18px">
        <span>Users with this role</span><span class="payments-count" id="roleUserCount">0</span>
      </div>
      <div id="roleUserList" class="payments-list"></div>
    </div>
    <div class="drawer-foot">
      <a id="roleEditPerms" href="{{ route('users.permissions') }}" class="btn btn-secondary">Edit Permissions</a>
      <button type="button" class="btn btn-accent" data-drawer-close>Close</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
var __rolePermLabel = {
  'members.view':'View Members','members.manage':'Manage Members',
  'events.manage':'Manage Events','events.complete':'Complete Events',
  'pledges.manage':'Manage Pledges',
  'finance.view':'View Finance','finance.manage':'Manage Finance','finance.approve':'Approve Finance',
  'communication.send':'Send Communication',
  'documents.view':'View Documents','documents.manage':'Manage Documents',
  'reports.view':'View Reports','reports.export':'Export Reports',
  'users.manage':'Manage Users','roles.manage':'Manage Roles','settings.manage':'Manage Settings','audit.view':'View Audit Logs'
};
function rolePermLabel(key){ return __rolePermLabel[key] || key; }

function openRoleDetail(el){
  var d = el.dataset;
  document.getElementById('roleDrawerName').textContent = d.name || 'Role';
  document.getElementById('roleDrawerFullName').textContent = d.name || '—';
  var superEl = document.getElementById('roleDrawerSuper');
  if (String(d.super) === '1') {
    superEl.textContent = 'All access';
    superEl.className = 'badge badge-success badge-dotted';
  } else {
    superEl.textContent = 'Limited access';
    superEl.className = 'badge badge-neutral badge-dotted';
  }
  document.getElementById('roleDrawerAvatar').textContent = (d.name || '?').split(' ').slice(0,2).map(function(w){ return w.charAt(0); }).join('').toUpperCase();

  var perms = [];
  try { perms = JSON.parse(d.permissions || '[]'); } catch(e) {}
  if (String(d.super) === '1') perms = [];
  document.getElementById('rolePermCount').textContent = String(d.super) === '1' ? 'All' : perms.length;
  var plist = document.getElementById('rolePermList');
  plist.innerHTML = '';
  var keys = String(d.super) === '1' ? [] : perms;
  if (String(d.super) === '1') {
    document.querySelectorAll('.role-perms-super').forEach(function(x){ x.remove(); });
    var all = document.createElement('div');
    all.className = 'pay-item role-perms-super';
    all.innerHTML = '<div class="pay-ico" style="background:var(--success-bg);color:var(--success)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div><div class="pay-main"><div class="pm-name">Full access</div><div class="pm-sub">All ' + '{{ count($permissions) }}' + ' permissions</div></div><div class="pay-amt" style="color:var(--success)">Granted</div>';
    plist.appendChild(all);
  }
  keys.forEach(function(key){
    var item = document.createElement('div');
    item.className = 'pay-item';
    item.innerHTML = '<div class="pay-ico" style="background:var(--success-bg);color:var(--success)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div><div class="pay-main"><div class="pm-name">' + rolePermLabel(key) + '</div><div class="pm-sub">' + key + '</div></div><div class="pay-amt" style="color:var(--success)">Granted</div>';
    plist.appendChild(item);
  });
  if (String(d.super) !== '1' && keys.length === 0) {
    plist.innerHTML = '<div class="empty-state" style="padding:24px 0"><h3>No permissions</h3><p>This role has no permissions assigned yet.</p></div>';
  }

  var users = [];
  try { users = JSON.parse(d.users || '[]'); } catch(e) {}
  document.getElementById('roleUserCount').textContent = users.length;
  var ulist = document.getElementById('roleUserList');
  ulist.innerHTML = '';
  users.forEach(function(name){
    var item = document.createElement('div');
    item.className = 'pay-item';
    item.innerHTML = '<div class="pay-ico" style="background:var(--purple-bg);color:var(--purple)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><div class="pay-main"><div class="pm-name">' + (name || '—') + '</div></div>';
    ulist.appendChild(item);
  });
  if (users.length === 0) {
    ulist.innerHTML = '<div class="empty-state" style="padding:24px 0"><h3>No users</h3><p>No active users currently hold this role.</p></div>';
  }

  document.getElementById('roleDrawerCount').textContent = users.length + ' user(s) · ' + (String(d.super) === '1' ? 'all permissions' : perms.length + ' of ' + '{{ count($permissions) }}' + ' permissions');
  document.getElementById('roleEditPerms').href = '{{ route('users.permissions') }}';
  openDrawerById('roleDetailDrawer');
}

document.addEventListener('click', function(e){
  var el = e.target.closest('[data-view-role]');
  if (!el) return;
  e.stopPropagation();
  openRoleDetail(el);
});
</script>
@endpush