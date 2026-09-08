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
          <tr class="role-row" style="cursor:pointer" data-role-details-row="roleDetails{{ $r->id }}">
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
              <button type="button" class="btn btn-secondary btn-sm" data-details-btn onclick="toggleRoleDetails(this.closest('tr'))">Details</button>
            </td>
          </tr>
          <tr class="role-details-row" id="roleDetails{{ $r->id }}">
            <td colspan="5">
              <div class="role-details-grid">
                <div class="role-details-col">
                  <div class="details-title">
                    <span>Permissions granted</span>
                    <span class="payments-count">{{ $r->is_super ? 'All '.count($permissions) : count($r->permissions ?? []).' of '.count($permissions) }}</span>
                  </div>
                  <div class="table-scroll" style="border:1px solid var(--border);border-radius:12px;max-height:320px;overflow:auto">
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
                </div>
                <div class="role-details-col">
                  <div class="details-title">
                    <span>Users with this role</span>
                    <span class="payments-count">{{ $r->users_count }}</span>
                  </div>
                  <div class="table-scroll" style="border:1px solid var(--border);border-radius:12px;max-height:320px;overflow:auto">
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
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function toggleRoleDetails(row){
  var id = row.getAttribute('data-role-details-row');
  var details = document.getElementById(id);
  if(!details) return;
  var open = details.classList.toggle('open');
  row.classList.toggle('role-row-open', open);
  var btn = row.querySelector('[data-details-btn]');
  if(btn) btn.textContent = open ? 'Hide' : 'Details';
}
document.addEventListener('click', function(e){
  var row = e.target.closest('tr[data-role-details-row]');
  if(!row) return;
  if(e.target.closest('button') || e.target.closest('a') || e.target.closest('input')) return;
  toggleRoleDetails(row);
});
</script>
@endpush