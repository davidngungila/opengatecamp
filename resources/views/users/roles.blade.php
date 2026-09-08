@extends('layouts.app')

@section('title', 'Roles — OpenGate Camp Connect')
@section('crumb', 'System / Roles')
@section('page_title', 'Roles')

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
@endphp

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
        <thead><tr><th>Role</th><th>Permissions</th><th>Users</th><th>Access</th><th style="width:90px">Details</th></tr></thead>
        <tbody>
          @foreach($roles as $r)
          <tr style="cursor:pointer" data-role-open="roleBody{{ $r->id }}">
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
            <td><b>{{ $r->is_super ? count($permissions) : count($r->permissions ?? []) }} / {{ count($permissions) }}</b></td>
            <td>
              <button type="button" class="btn btn-secondary btn-sm" data-role-open="roleBody{{ $r->id }}">Details</button>
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
      <div><h3 id="roleDrawerName">Role Details</h3><p id="roleDrawerBadge" class="badge badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body" id="roleDrawerBody"></div>
    <div class="drawer-foot">
      <a id="roleEditPerms" href="{{ route('users.permissions') }}" class="btn btn-secondary" style="text-decoration:none">Edit Permissions</a>
      <button type="button" class="btn btn-accent" data-drawer-close>Close</button>
    </div>
  </div>
</div>

@foreach($roles as $r)
<div style="display:none" id="roleBody{{ $r->id }}"
     data-name="{{ $r->name }}"
     data-super="{{ $r->is_super ? 1 : 0 }}"
     data-perms="{{ $r->is_super ? count($permissions) : count($r->permissions ?? []) }}"
     data-total="{{ count($permissions) }}">
  <div class="profile-detail">
    <div class="avatar avatar-lg" style="background:var(--purple-bg);color:var(--purple)"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
    <div>
      <div style="font-size:16px;font-weight:800">{{ $r->name }}</div>
      <div style="font-size:12.5px;color:var(--text-tertiary);font-weight:600">{{ $r->users_count }} user(s) · {{ count($r->permissions ?? []) }} of {{ count($permissions) }} permissions</div>
    </div>
  </div>

  <div class="payments-head">
    <span>Permissions granted</span><span class="payments-count">{{ $r->is_super ? 'All '.count($permissions) : count($r->permissions ?? []).' / '.count($permissions) }}</span>
  </div>
  <div class="table-scroll" style="border:1px solid var(--border);border-radius:12px;max-height:300px;overflow:auto">
    <table class="data-table compact">
      @if($r->is_super)
      <tbody>
        <tr>
          <td><span class="badge badge-success badge-dotted">Full access</span></td>
          <td style="font-size:12.5px;color:var(--text-secondary)">All {{ count($permissions) }} permissions are granted to this role.</td>
        </tr>
      </tbody>
      @else
      <thead><tr><th>Permission</th><th style="text-align:right">Key</th></tr></thead>
      <tbody>
        @forelse($r->permissions ?? [] as $perm)
        <tr>
          <td><b style="font-size:12.5px">{{ $permLabel($perm) }}</b></td>
          <td style="text-align:right"><code style="font-size:11px">{{ $perm }}</code></td>
        </tr>
        @empty
        <tr><td colspan="2"><div class="empty-state" style="padding:24px 0"><h3>No permissions</h3><p>This role has no permissions assigned yet.</p></div></td></tr>
        @endforelse
      </tbody>
      @endif
    </table>
  </div>

  <div class="payments-head" style="margin-top:18px">
    <span>Users with this role</span><span class="payments-count">{{ $r->users_count }}</span>
  </div>
  <div class="table-scroll" style="border:1px solid var(--border);border-radius:12px;max-height:300px;overflow:auto">
    <table class="data-table compact">
      <thead><tr><th>User</th><th style="text-align:right">Status</th></tr></thead>
      <tbody>
        @forelse($r->users as $u)
        <tr>
          <td><div class="cu-name">{{ $u->name }}</div><div class="cu-sub">{{ $u->email }}</div></td>
          <td style="text-align:right"><span class="badge badge-{{ $u->status==='Active' ? 'success' : 'danger' }} badge-dotted">{{ $u->status }}</span></td>
        </tr>
        @empty
        <tr><td colspan="2"><div class="empty-state" style="padding:24px 0"><h3>No users</h3><p>No users currently hold this role.</p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endforeach
@endsection

@push('scripts')
<script>
function openRoleDrawer(id){
  var tpl = document.getElementById(id);
  if(!tpl) return;
  document.getElementById('roleDrawerName').textContent = tpl.dataset.name || 'Role';
  var badge = document.getElementById('roleDrawerBadge');
  if(String(tpl.dataset.super) === '1'){
    badge.textContent = 'All access';
    badge.className = 'badge badge-success badge-dotted';
  } else {
    badge.textContent = tpl.dataset.perms + ' / ' + tpl.dataset.total + ' permissions';
    badge.className = 'badge badge-purple badge-dotted';
  }
  document.getElementById('roleDrawerBody').innerHTML = tpl.innerHTML;
  document.getElementById('roleEditPerms').href = '{{ route('users.permissions') }}';
  openDrawerById('roleDetailDrawer');
}
document.addEventListener('click', function(e){
  var el = e.target.closest('[data-role-open]');
  if(!el) return;
  if(e.target.closest('a') || e.target.closest('input')) return;
  e.stopPropagation();
  openRoleDrawer(el.getAttribute('data-role-open'));
});
</script>
@endpush