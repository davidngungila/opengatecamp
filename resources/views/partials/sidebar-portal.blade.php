@php
    $p = request()->path() === 'portal' ? 'portal' : request()->path();
    $seg = explode('/', $p);
    $base = $seg[0];
    $isPortal = $base === 'portal';
    $portalPage = $seg[1] ?? 'dashboard';
@endphp
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-mark">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 2L4 6v6c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10V6l-8-4z" fill="white" fill-opacity=".95"/><path d="M12 6v12M9 9h6" stroke="#0B1F3A" stroke-width="1.6" stroke-linecap="round"/></svg>
    </div>
    <div class="brand-text">
      <strong>Open Gate</strong>
      <span>MEMBER PORTAL</span>
    </div>
  </div>

  <nav class="sidebar-scroll" id="sidebarNav">
    <div class="tooltip-wrap">
      <a href="{{ route('portal.dashboard') }}" class="nav-single {{ $portalPage==='dashboard' ? 'active' : '' }}">
        <span class="nav-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg></span>
        <span class="nav-label">Dashboard</span>
      </a>
      <span class="tt">Dashboard</span>
    </div>

    <div class="nav-group">
      <div class="tooltip-wrap">
        <button type="button" class="nav-parent {{ in_array($portalPage,['registrations','pledges']) ? 'expanded' : '' }}" onclick="toggleNavGroup(this)">
          <span class="nav-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l9 9h-3v6h-4v-4h-4v4H6v-6H3l9-9z"/></svg></span>
          <span class="nav-label">My Camp</span>
          <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M9 18l6-6-6-6"/></svg>
        </button>
        <span class="tt">My Camp</span>
      </div>
      <div class="nav-children {{ in_array($portalPage,['registrations','pledges']) ? 'open' : '' }}">
        <a href="{{ route('portal.registrations') }}" class="nav-child {{ $portalPage==='registrations' ? 'active' : '' }}">Registrations</a>
        <a href="{{ route('portal.pledges') }}" class="nav-child {{ $portalPage==='pledges' ? 'active' : '' }}">Pledges</a>
      </div>

      <div class="tooltip-wrap">
        <button type="button" class="nav-parent {{ in_array($portalPage,['profile','settings']) ? 'expanded' : '' }}" onclick="toggleNavGroup(this)">
          <span class="nav-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg></span>
          <span class="nav-label">My Account</span>
          <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M9 18l6-6-6-6"/></svg>
        </button>
        <span class="tt">My Account</span>
      </div>
      <div class="nav-children {{ in_array($portalPage,['profile','settings']) ? 'open' : '' }}">
        <a href="{{ route('portal.profile') }}" class="nav-child {{ $portalPage==='profile' ? 'active' : '' }}">My Profile</a>
        <a href="{{ route('portal.family') }}" class="nav-child {{ $portalPage==='family' ? 'active' : '' }}">My Family</a>
        <a href="{{ route('portal.settings') }}" class="nav-child {{ $portalPage==='settings' ? 'active' : '' }}">Account Settings</a>
      </div>

      <div class="tooltip-wrap">
        <button type="button" class="nav-parent {{ $portalPage==='contributions' ? 'expanded' : '' }}" onclick="toggleNavGroup(this)">
          <span class="nav-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v10M15 9.5c0-1.4-1.3-2.5-3-2.5s-3 1-3 2.3c0 3 6 1.4 6 4.3 0 1.4-1.3 2.4-3 2.4s-3-1-3-2.4"/></svg></span>
          <span class="nav-label">My Giving</span>
          <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M9 18l6-6-6-6"/></svg>
        </button>
        <span class="tt">My Giving</span>
      </div>
      <div class="nav-children {{ $portalPage==='contributions' ? 'open' : '' }}">
        <a href="{{ route('portal.contributions') }}" class="nav-child {{ $portalPage==='contributions' ? 'active' : '' }}">Contributions</a>
      </div>

      <div class="tooltip-wrap">
        <button type="button" class="nav-parent {{ $portalPage==='activations' ? 'expanded' : '' }}" onclick="toggleNavGroup(this)">
          <span class="nav-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></span>
          <span class="nav-label">Activations</span>
          <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M9 18l6-6-6-6"/></svg>
        </button>
        <span class="tt">Activations</span>
      </div>
      <div class="nav-children {{ $portalPage==='activations' ? 'open' : '' }}">
        <a href="{{ route('portal.activations') }}" class="nav-child {{ $portalPage==='activations' ? 'active' : '' }}">Activation Status</a>
      </div>
    </div>

    <div style="border-top:1px solid rgba(255,255,255,.08);margin:12px 0"></div>

    <div class="tooltip-wrap">
      <a href="{{ url('/') }}" class="nav-single">
        <span class="nav-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
        <span class="nav-label">Admin Panel</span>
      </a>
      <span class="tt">Admin Panel</span>
    </div>
  </nav>
</aside>
