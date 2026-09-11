@extends('layouts.app')

@section('title', 'Users — OpenGate Camp Connect')
@section('crumb', 'System / Users')
@section('page_title', 'Users')

@php
    $initials = fn($name) => collect(explode(' ', str_replace(['Fr. ','Dr. '], '', $name)))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('');
@endphp

<style>
.info-wrap{position:relative;display:inline-flex;align-items:center;justify-content:center;vertical-align:middle}
.info-wrap .info-ico{width:16px;height:16px;border-radius:50%;background:var(--blue-light);color:var(--blue-accent);display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:900;border:1px solid rgba(37,99,235,.18);cursor:help;flex-shrink:0}
.info-bubble{position:absolute;left:50%;bottom:calc(100% + 8px);transform:translateX(-50%);background:#0f172a;color:#fff;font-size:11.5px;line-height:1.5;font-weight:500;padding:10px 12px;border-radius:9px;min-width:240px;max-width:320px;white-space:normal;box-shadow:0 10px 28px rgba(0,0,0,.22);opacity:0;visibility:hidden;transition:opacity .15s,visibility .15s;z-index:50;text-align:left;pointer-events:none}
.info-bubble::after{content:'';position:absolute;top:100%;left:50%;transform:translateX(-50%);border:6px solid transparent;border-top-color:#0f172a}
.info-wrap:hover .info-bubble,.info-wrap:focus-within .info-bubble{opacity:1;visibility:visible}
.info-bubble code{background:rgba(255,255,255,.12);padding:1px 5px;border-radius:4px;font-family:ui-monospace,monospace;font-size:11px}
</style>
@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>System Users</h2><div class="sub">{{ $users->count() }} system users</div></div>
    <div class="flex gap-8">
      <a href="{{ route('users.roles') }}" class="btn btn-secondary" style="text-decoration:none">Roles</a>
      <a href="{{ route('users.permissions') }}" class="btn btn-secondary" style="text-decoration:none">Permissions</a>
      <form method="POST" action="{{ route('users.welcome.bulk') }}" onsubmit="return confirm('Send the welcome SMS to all {{ $users->whereNotNull('phone')->where('phone','!=','')->count() }} users with a phone number? (uses saved default content)')">
        @csrf
        <button type="submit" class="btn btn-secondary">Send Welcome to All</button>
      </form>
      <button type="button" class="btn btn-accent" data-drawer-open="userModal" onclick="resetUserModal()">+ Add User</button>
    </div>
  </div>

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
          <div style="display:flex;align-items:center;gap:6px;margin-top:4px">
            <small style="color:var(--text-muted)">Placeholders</small>
            <span class="info-wrap" tabindex="0" aria-label="Placeholders help"><span class="info-ico">i</span><span class="info-bubble">Placeholders: <code>{name}</code> and <code>{phone}</code> are replaced with this user's details. Edit freely — each user can get a different message.</span></span>
          </div>
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
function permLabel(key){ return __permLabel[key] || key; }
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
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('[data-view-user]').forEach(function(tr){
    tr.addEventListener('click', function(e){
      if(e.target.closest('a') || e.target.closest('button') || e.target.closest('form')) return;
      var id = tr.dataset.id;
      fetch('/api/users/' + encodeURIComponent(id), {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin'
        })
        .then(function(r){
          if (!r.ok) throw new Error('HTTP '+r.status);
          return r.json();
        })
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
        .catch(function(err){
          console.error('load user failed', err);
          toast('Could not load user details' + (err && err.message ? ': ' + err.message : ''), 'error');
        });
    });
  });
});
</script>
@endpush
