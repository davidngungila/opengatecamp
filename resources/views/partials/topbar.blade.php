<header class="topbar">
  <div class="topbar-left">
    <button type="button" class="icon-btn" onclick="onSidebarToggleClick()" aria-label="Toggle sidebar">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <div class="crumb-wrap">
      <div class="page-title">@yield('crumb', 'Dashboard')</div>
    </div>
  </div>
  <div class="topbar-right">
    @php
        $fyAll = \App\Models\FinancialYear::orderByDesc('start_date')->get();
        $fyCurrent = \App\Models\FinancialYear::current();
    @endphp
    <select class="filter-select" style="min-width:170px;font-weight:700" title="Financial year filter"
            onchange="if(this.value!=='') location.href=this.value">
      <option value="{{ route('settings.years.switch', 0) }}" {{ $fyCurrent ? '' : 'selected' }}>All periods</option>
      @foreach($fyAll as $y)
        <option value="{{ route('settings.years.switch', $y->id) }}" {{ ($fyCurrent?->id === $y->id) ? 'selected' : '' }}>
          {{ str_replace(' ', '', $y->name) }}
        </option>
      @endforeach
    </select>

    <div style="position:relative">
      <button type="button" class="icon-btn" data-panel-toggle="notifPanel" onclick="togglePanel('notifPanel')" title="Notifications">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
        @php $unreadCount = \App\Models\AuditLog::unreadCount(); @endphp
        @if($unreadCount > 0)
        <span class="badge-dot" style="position:absolute;top:2px;right:2px;min-width:16px;height:16px;border-radius:999px;background:#ef4444;color:#fff;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;padding:0 4px">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
      </button>
      <div class="dropdown-panel" id="notifPanel">
        <div class="dropdown-header">
          <strong>Notifications</strong>
          <form method="POST" action="{{ route('messaging.notifications.mark-all-read') }}" id="markAllReadForm">
            @csrf
            <button type="submit" class="link-btn">Mark all read</button>
          </form>
        </div>
        <div class="dropdown-list">
          @php
              $notifEvents = \App\Models\Event::where('status','open_registration')->orderBy('start_date')->take(2)->get();
              $notifPledges = \App\Models\Pledge::whereIn('status',['pending','partial'])->count();
          @endphp
          @foreach($notifEvents as $ne)
          <a class="notif-item" href="{{ route('events.show', $ne->slug) }}" style="text-decoration:none;color:inherit">
            <div class="n-ico" style="background:var(--purple-bg);color:var(--purple)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
            <div class="n-body"><p>Registrations open: {{ $ne->title }}</p><span>{{ $ne->start_date?->format('d M') }} · {{ $ne->getAttendeeCountAttribute() }} registered</span></div>
          </a>
          @endforeach
          @if($notifPledges > 0)
          <a class="notif-item" href="{{ route('pledges.index') }}" style="text-decoration:none;color:inherit">
            <div class="n-ico" style="background:var(--success-bg);color:var(--success)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
            <div class="n-body"><p>{{ $notifPledges }} ongoing pledge(s) awaiting payments</p><span>{{ \App\Models\Pledge::whereIn('status',['pending','partial'])->sum('amount') }} pledged</span></div>
          </a>
          @endif
          <a class="notif-item" href="{{ route('attendees.index') }}" style="text-decoration:none;color:inherit">
            <div class="n-ico" style="background:var(--info-bg);color:var(--info)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            <div class="n-body"><p>{{ \App\Models\EventAttendee::where('status','pending')->count() }} pending attendee registration(s)</p><span>Needs confirmation</span></div>
          </a>
          @if($notifEvents->isEmpty() && $notifPledges === 0)
          <div class="notif-item">
            <div class="n-body"><p>You're all caught up. No new notifications.</p><span>Just now</span></div>
          </div>
          @endif
        </div>
      </div>
    </div>

    <div style="position:relative">
      <button type="button" class="user-chip" data-panel-toggle="userPanel" onclick="togglePanel('userPanel')">
        @php $u = auth()->user(); @endphp
        <div class="avatar" style="{{ $u?->profile_image ? "background-image:url('".$u->profile_image."');background-size:cover;background-position:center;" : '' }}">{{ $u?->profile_image ? '' : collect(explode(' ', $u?->name ?? 'OG'))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('') }}</div>
        <div class="u-meta">
          <div class="u-name">{{ $u?->name ?? 'Admin' }}</div>
          <div class="u-role">{{ $u?->role?->name ?? 'Administrator' }}</div>
        </div>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
      </button>
      <div class="dropdown-panel" id="userPanel">
        <a class="menu-item" href="{{ route('account.profile') }}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> My Profile</a>
        <a class="menu-item" href="{{ route('account.settings') }}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.9l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.9-.3 1.7 1.7 0 00-1 1.6V21a2 2 0 01-4 0v-.1a1.7 1.7 0 00-1-1.6 1.7 1.7 0 00-1.9.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.9 1.7 1.7 0 00-1.6-1H3a2 2 0 010-4h.1a1.7 1.7 0 001.6-1 1.7 1.7 0 00-.3-1.9l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.9.3H9a1.7 1.7 0 001-1.6V3a2 2 0 014 0v.1a1.7 1.7 0 001 1.6 1.7 1.7 0 001.9-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.9V9a1.7 1.7 0 001.6 1H21a2 2 0 010 4h-.1a1.7 1.7 0 00-1.6 1z"/></svg> Account Settings</a>
        <a class="menu-item" href="{{ route('account.audit-logs') }}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Audit Logs</a>
        <div class="menu-divider"></div>
        <form method="POST" action="{{ route('logout') }}">@csrf
          <button type="submit" class="menu-item danger" style="width:100%;border:none;background:none;cursor:pointer"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Logout</button>
        </form>
      </div>
    </div>
  </div>
</header>

<div class="modal-overlay" id="confirmModal">
  <div class="modal-box sm">
    <div class="modal-body" style="text-align:center;padding-top:32px">
      <div class="confirm-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg></div>
      <h3 style="font-size:17px;margin:0 0 8px;" data-confirm-title>Are you sure?</h3>
      <p style="color:var(--text-secondary);font-size:13.5px;margin:0;" data-confirm-message>This action cannot be undone.</p>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
      <button type="button" class="btn btn-danger" data-confirm-submit data-confirm-label>Confirm</button>
    </div>
  </div>
</div>
