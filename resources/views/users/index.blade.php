@extends('layouts.app')

@section('title', 'Users & Roles — OpenGate Camp Connect')
@section('crumb', 'System / Users & Roles')
@section('page_title', 'Users & Roles')

@php
    $tab = $tab ?? 'users';
    $initials = fn($name) => collect(explode(' ', str_replace(['Fr. ','Dr. '], '', $name)))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('');
@endphp

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Users &amp; Roles</h2><div class="sub">{{ $users->count() }} system users · {{ $roles->count() }} roles</div></div>
    @if($tab === 'users')
      <div class="flex gap-8">
        <form method="POST" action="{{ route('users.welcome.bulk') }}" onsubmit="return confirm('Send the welcome SMS to all {{ $users->whereNotNull('phone')->where('phone','!=','')->count() }} users with a phone number? (uses saved default content)')">
          @csrf
          <button type="submit" class="btn btn-secondary">Send Welcome to All</button>
        </form>
        <button type="button" class="btn btn-accent" data-drawer-open="userModal" onclick="resetUserModal()">+ Add User</button>
      </div>
    @endif
  </div>

  <div class="tabs-bar">
    <a href="{{ route('users.index', ['tab' => 'users']) }}" class="tab-btn {{ $tab==='users' ? 'active' : '' }}">Users</a>
    <a href="{{ route('users.index', ['tab' => 'roles']) }}" class="tab-btn {{ $tab==='roles' ? 'active' : '' }}">Roles</a>
    <a href="{{ route('users.index', ['tab' => 'permissions']) }}" class="tab-btn {{ $tab==='permissions' ? 'active' : '' }}">Permissions</a>
  </div>

  @if($tab === 'users')
  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>User</th><th>Role</th><th>Phone</th><th>Status</th><th>Last Login</th><th style="width:60px">Actions</th></tr></thead>
        <tbody>
          @forelse($users as $i => $u)
          <tr style="cursor:pointer" data-view-user data-id="{{ $u->id }}" data-name="{{ $u->name }}">
            <td>
              <div class="cell-user">
                <div class="cell-avatar">{{ $initials($u->name) }}</div>
                <div><div class="cu-name">{{ $u->name }}</div><div class="cu-sub">{{ $u->email }}</div></div>
              </div>
            </td>
            <td><span class="badge badge-purple badge-dotted">{{ $u->role?->name ?? '—' }}</span></td>
            <td>{{ $u->phone ?? '—' }}</td>
            <td><span class="badge badge-{{ $u->status==='Active' ? 'success' : 'danger' }} badge-dotted">{{ $u->status }}</span></td>
            <td>{{ $u->last_login_at?->diffForHumans() ?? 'Never' }}</td>
            <td>
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-users-{{ $u->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-users-{{ $u->id }}">
                  @if(!$isCommittee)
                  <form method="POST" action="{{ route('users.welcome', $u) }}">
                    @csrf
                    <button type="submit">Send Welcome SMS</button>
                  </form>
                  <button type="button" data-edit-user
                          data-id="{{ $u->id }}" data-name="{{ $u->name }}" data-email="{{ $u->email }}"
                          data-phone="{{ $u->phone }}" data-role="{{ $u->role_id }}" data-status="{{ $u->status }}">Edit Role / Profile</button>
                  <form method="POST" action="{{ route('users.password', $u) }}">
                    @csrf @method('PATCH')
                    <button type="submit">Reset Password</button>
                  </form>
                  <form method="POST" action="{{ route('users.suspend', $u) }}">
                    @csrf @method('PATCH')
                    <button type="submit">{{ $u->status === 'Active' ? 'Suspend' : 'Re-activate' }}</button>
                  </form>
                  <form method="POST" action="{{ route('users.destroy', $u) }}"
                        data-confirm data-confirm-title="Delete this user?"
                        data-confirm-message="{{ $u->name }} will permanently lose access to the system."
                        data-confirm-label="Delete User">
                    @csrf @method('DELETE')
                    <button type="submit" class="danger">Delete</button>
                  </form>
                  @endif
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="6"><div class="empty-state"><h3>No users yet</h3><p>Invite your first team member.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @elseif($tab === 'roles')
  <div class="card-grid">
    @foreach($roles as $r)
    <div class="entity-card">
      <div class="ec-top">
        <div class="ec-ico" style="background:var(--purple-bg);color:var(--purple)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div><h4>{{ $r->name }}</h4><div class="ec-sub">{{ is_array($r->permissions) ? count($r->permissions) : 0 }} permissions granted</div></div>
      </div>
      <div class="ec-stats">
        <div class="ec-stat"><b>{{ $r->users_count }}</b><span>Users</span></div>
        <div class="ec-stat"><b>{{ $r->is_super ? 'All access' : count($r->permissions ?? []).' / '.count($permissions) }}</b><span>Access</span></div>
      </div>
      <div class="flex gap-8" style="margin-top:14px">
        <a class="btn btn-secondary btn-sm" style="flex:1" href="{{ route('users.index', ['tab'=>'permissions']) }}#role-{{ $r->id }}">Edit Permissions</a>
      </div>
    </div>
    @endforeach
  </div>

  @else
  <div class="glass-card">
    <div class="section-head"><div><h2 style="font-size:15px">Role Permission Matrix</h2><div class="sub">Tick the permissions each role may use, then save.</div></div></div>
    <div class="table-scroll" style="max-height:480px;overflow-y:auto;border:1px solid var(--border);border-radius:14px">
      <table class="data-table">
        <thead><tr><th>Permission</th>@foreach($roles as $r)<th style="text-align:center">{{ Str::limit($r->name, 12) }}</th>@endforeach</tr></thead>
        <tbody>
          @foreach($permissions as $perm)
          <tr>
            <td><code style="font-size:12px">{{ $perm }}</code></td>
            @foreach($roles as $r)
              <td style="text-align:center">
                <input type="checkbox" class="checkbox perm-box"
                       data-role="{{ $r->id }}" data-perm="{{ $perm }}"
                       {{ ($r->is_super || in_array($perm, $r->permissions ?? [])) ? 'checked' : '' }}
                       {{ $r->is_super ? 'disabled' : '' }}>
              </td>
            @endforeach
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="flex gap-8" style="justify-content:flex-end;margin-top:14px">
      <button type="button" class="btn btn-accent" onclick="savePermissions(this)">Save Permissions</button>
    </div>
  </div>
  @endif
</div>

<div class="drawer-overlay" id="userModal">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="userModalTitle">Add User</h3><p id="userModalSub">Invite a new team member with a temporary password</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form id="userForm" method="POST" action="{{ route('users.store') }}">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field"><label>Full Name *</label><input name="name" required placeholder="e.g. Grace Kileo"></div>
          <div class="field"><label>Email *</label><input type="email" name="email" required placeholder="email@stjoseph.church"></div>
          <div class="field"><label>Role</label>
            <select name="role_id">
              @foreach($roles->where('name', '!==', 'Super Administrator') as $r)
                <option value="{{ $r->id }}">{{ $r->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="field"><label>Status</label>
            <select name="status"><option>Active</option><option>Suspended</option></select>
          </div>
          <div class="field full"><label>Phone</label><input name="phone" placeholder="+255 7XX XXX XXX"></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Save User</button>
      </div>
    </form>
  </div>
</div>

<div class="drawer-overlay" id="userDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="usrDrawerName">User Details</h3><p id="usrDrawerRole" class="badge badge-purple badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="profile-detail">
        <div class="avatar avatar-lg" id="usrDrawerAvatar">—</div>
        <div>
          <div style="font-size:16px;font-weight:800" id="usrDrawerFullName">—</div>
          <div style="font-size:12.5px;color:var(--text-tertiary);font-weight:600" id="usrDrawerEmail">—</div>
        </div>
      </div>
      <div class="info-grid">
        <div class="info-row"><span>Role</span><b id="usrDrawerRoleVal">—</b></div>
        <div class="info-row"><span>Status</span><b id="usrDrawerStatus">—</b></div>
        <div class="info-row"><span>Phone</span><b id="usrDrawerPhone">—</b></div>
        <div class="info-row"><span>Last Login</span><b id="usrDrawerLastLogin">—</b></div>
        <div class="info-row"><span>Member Since</span><b id="usrDrawerCreated">—</b></div>
      </div>
      <div class="payments-head" style="margin-top:18px">
        <span>Permissions</span><span class="payments-count" id="usrPermCount">0</span>
      </div>
      <div id="usrPermList" class="payments-list"></div>

      <div class="payments-head" style="margin-top:18px">
        <span>Welcome SMS</span><span class="payments-count" id="usrWelcomePhone">—</span>
      </div>
      <form method="POST" id="welcomeSmsForm">
        @csrf
        <div class="field" style="margin-top:8px">
          <textarea name="welcome_message" id="usrWelcomeMsg" rows="4" style="width:100%" placeholder="Karibu {name}! Login at https://opengatecamp.iccrtz.org/login with your phone number.">{{ $welcomeMessage }}</textarea>
          <small style="color:var(--text-muted)">Placeholders: <code>{name}</code> and <code>{phone}</code> are replaced with this user's details. Edit freely — each user can get a different message.</small>
        </div>
        <div class="flex gap-8" style="margin-top:12px;justify-content:flex-end">
          <button type="button" class="btn btn-secondary btn-sm" id="usrWelcomeSaveDefault">Save as Default Content</button>
          <button type="submit" class="btn btn-accent btn-sm">Send Welcome SMS</button>
        </div>
      </form>
      <form method="POST" action="{{ route('users.welcome-message') }}" id="welcomeSaveForm" style="display:none">
        @csrf
        <input type="hidden" name="welcome_message" id="usrWelcomeSaveDefaultVal" value="">
      </form>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function resetUserModal(){
  var form=document.getElementById('userForm');
  form.reset();
  form.action='{{ url('/users') }}';
  var m=form.querySelector('#_umethod'); if(m) m.remove();
  document.getElementById('userModalTitle').textContent='Add User';
}
document.addEventListener('click', function(e){
  if(!e.target.closest('[data-edit-user]')) return;
  var b=e.target.closest('[data-edit-user]');
  var form=document.getElementById('userForm');
  form.action='{{ url('/users') }}/'+b.dataset.id;
  form.querySelector('[name=name]').value=b.dataset.name||'';
  form.querySelector('[name=email]').value=b.dataset.email||'';
  form.querySelector('[name=phone]').value=b.dataset.phone||'';
  form.querySelector('[name=role_id]').value=b.dataset.role||'';
  form.querySelector('[name=status]').value=b.dataset.status||'Active';
  var m=document.createElement('input');
  m.type='hidden'; m.name='_method'; m.value='PUT'; m.id='_umethod';
  form.appendChild(m);
  document.getElementById('userModalTitle').textContent='Edit User';
  openDrawerById('userModal');
});
function savePermissions(btn){
  var boxes=document.querySelectorAll('.perm-box:checked:not([disabled])');
  var byRole={};
  boxes.forEach(function(cb){
    (byRole[cb.dataset.role]=byRole[cb.dataset.role]||[]).push(cb.dataset.perm);
  });
  var queue=Object.keys(byRole);
  if(queue.length===0){ toast('No changes detected','info'); return; }
  toast('Saving permissions for '+queue.length+' role(s)...','info');
  (function next(){
    var roleId=queue.shift();
    if(roleId===undefined){ setTimeout(function(){ location.reload(); },600); return; }
    fetch('{{ url('/roles') }}/'+roleId+'/permissions', {
      method:'PUT',
      headers:{
        'Content-Type':'application/json',
        'X-CSRF-TOKEN':'{{ csrf_token() }}',
        'Accept':'application/json'
      },
      body: JSON.stringify({ permissions: byRole[roleId] })
    }).then(next).catch(function(){ toast('Failed to save permissions','error'); });
  })();
}

function permLabel(key){
  var map={
    'members.view':'View Members','members.manage':'Manage Members',
    'events.manage':'Manage Events','events.complete':'Complete Events',
    'pledges.manage':'Manage Pledges',
    'finance.view':'View Finance','finance.manage':'Manage Finance','finance.approve':'Approve Finance',
    'communication.send':'Send Communication',
    'documents.view':'View Documents','documents.manage':'Manage Documents',
    'reports.view':'View Reports','reports.export':'Export Reports',
    'users.manage':'Manage Users','roles.manage':'Manage Roles','settings.manage':'Manage Settings','audit.view':'View Audit Logs'
  };
  return map[key]||key;
}

document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('[data-view-user]').forEach(function(tr){
    tr.addEventListener('click', function(e){
      if(e.target.closest('a') || e.target.closest('button') || e.target.closest('form')) return;
      var id = tr.dataset.id;
      fetch('{{ url('/api/users') }}/' + id)
        .then(function(r){ return r.json(); })
        .then(function(d){
          var u = d.user;
          document.getElementById('usrDrawerName').textContent = u.name;
          document.getElementById('usrDrawerFullName').textContent = u.name;
          document.getElementById('usrDrawerEmail').textContent = u.email;
          document.getElementById('usrDrawerRoleVal').textContent = u.role;
          document.getElementById('usrDrawerRole').textContent = u.is_super ? u.role : (u.role + ' · ' + d.permissions.filter(function(p){ return p.granted; }).length + ' perms');

          var st = u.status;
          var stEl = document.getElementById('usrDrawerStatus');
          stEl.textContent = st;
          stEl.style.color = st === 'Active' ? 'var(--success)' : 'var(--danger)';

          document.getElementById('usrDrawerPhone').textContent = u.phone || '—';
          document.getElementById('usrDrawerLastLogin').textContent = u.last_login ? new Date(u.last_login).toLocaleString() : 'Never';
          document.getElementById('usrDrawerCreated').textContent = u.created || '—';

          var wf = document.getElementById('welcomeSmsForm');
          wf.action = '{{ url('/users') }}/' + u.id + '/welcome';
          document.getElementById('usrWelcomePhone').textContent = u.phone || 'No phone recorded';
          document.getElementById('usrWelcomeMsg').placeholder = u.phone ? undefined : 'This user has no phone number recorded.';

          var saveDefault = document.getElementById('usrWelcomeSaveDefault');
          saveDefault.onclick = function(){
            var el = document.getElementById('usrWelcomeSaveDefaultVal');
            el.value = document.getElementById('usrWelcomeMsg').value;
            document.getElementById('welcomeSaveForm').submit();
          };

          var av = document.getElementById('usrDrawerAvatar');
          av.textContent = (u.name||'?').split(' ').filter(function(w){ return ['Fr.','Dr.'].indexOf(w)===-1; }).slice(0,2).map(function(w){ return w.charAt(0); }).join('');

          var list = document.getElementById('usrPermList');
          list.innerHTML = '';
          document.getElementById('usrPermCount').textContent = d.permissions.filter(function(p){ return p.granted; }).length;
          d.permissions.forEach(function(p){
            var item = document.createElement('div');
            item.className = 'pay-item';
            item.innerHTML =
              '<div class="pay-ico" style="background:' + (p.granted ? 'var(--success-bg)' : 'rgba(15,23,42,.06)') + ';color:' + (p.granted ? 'var(--success)' : 'var(--text-tertiary)') + '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>' +
              '<div class="pay-main"><div class="pm-name">' + permLabel(p.key) + '</div><div class="pm-sub">' + p.key + '</div></div>' +
              '<div class="pay-amt" style="text-align:right"><div style="font-size:11px;font-weight:700;color:' + (p.granted ? 'var(--success)' : 'var(--text-tertiary)') + '">' + (p.granted ? 'Granted' : '—') + '</div></div>';
            list.appendChild(item);
          });
          openDrawerById('userDetailDrawer');
        })
        .catch(function(){ toast('Could not load user details', 'error'); });
    });
  });
});
</script>
@endpush
