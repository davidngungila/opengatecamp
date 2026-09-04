<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Member Portal — OpenGate Camp Connect')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
@include('partials.styles-core')
@include('partials.styles-components')
<style>
.portal-nav{position:fixed;top:0;left:0;right:0;height:62px;background:linear-gradient(135deg, var(--navy-900), #0A1B33);display:flex;align-items:center;justify-content:space-between;padding:0 28px;z-index:200;box-shadow:0 2px 12px rgba(0,0,0,.15)}
.portal-brand{display:flex;align-items:center;gap:10px}
.portal-brand .brand-mark{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg, var(--blue-accent), #4C86F5);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(37,99,235,.4)}
.portal-brand strong{color:white;font-size:15px;font-weight:800}
.portal-brand span{color:rgba(255,255,255,.6);font-size:10.5px;font-weight:600;letter-spacing:.4px}
.portal-nav-links{display:flex;align-items:center;gap:6px}
.portal-nav-links a{color:rgba(255,255,255,.7);font-size:13px;font-weight:600;padding:7px 14px;border-radius:8px;transition:all .15s;text-decoration:none}
.portal-nav-links a:hover,.portal-nav-links a.active{background:rgba(255,255,255,.12);color:white}
.portal-user{display:flex;align-items:center;gap:10px}
.portal-user .avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#4C86F5,#2563EB);display:flex;align-items:center;justify-content:center;color:white;font-size:13px;font-weight:800}
.portal-user span{color:rgba(255,255,255,.9);font-size:13px;font-weight:600}
.portal-user form{margin:0}
.portal-user .btn-portal{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.8);padding:6px 12px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s}
.portal-user .btn-portal:hover{background:rgba(255,255,255,.15);color:white}
.portal-shell{margin-top:62px;min-height:calc(100vh - 62px);background:var(--bg);padding:28px 32px}
.portal-content{max-width:1100px;margin:0 auto}

.portal-card{background:var(--glass-bg-solid);border:1px solid var(--glass-border);border-radius:var(--radius-lg);box-shadow:var(--shadow-glass);padding:24px 28px;margin-bottom:20px}
.portal-card h2{font-size:16px;font-weight:800;margin:0 0 16px;color:var(--navy-900)}

.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:24px}
.stat-card{background:white;border:1px solid var(--border);border-radius:var(--radius-md);padding:18px 20px;text-align:center}
.stat-card .stat-value{font-size:26px;font-weight:800;color:var(--navy-900);line-height:1.2}
.stat-card .stat-label{font-size:12px;font-weight:600;color:var(--text-secondary);margin-top:4px}
.stat-card.green .stat-value{color:var(--green-accent)}
.stat-card.blue .stat-value{color:var(--blue-accent)}
.stat-card.purple .stat-value{color:var(--purple)}
.stat-card.orange .stat-value{color:var(--warning)}

.info-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);font-size:13.5px}
.info-row:last-child{border-bottom:none}
.info-row .label{color:var(--text-secondary);font-weight:600}
.info-row .value{font-weight:700;color:var(--navy-900)}

.portal-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700}
.portal-badge.active{background:var(--success-bg);color:var(--success)}
.portal-badge.inactive{background:var(--danger-bg);color:var(--danger)}
.portal-badge.pending{background:var(--warning-bg);color:var(--warning)}
.portal-badge.info{background:var(--info-bg);color:var(--info)}

.portal-table{width:100%;border-collapse:collapse;font-size:13px}
.portal-table th{text-align:left;padding:10px 12px;background:var(--blue-light);color:var(--navy-900);font-weight:700;font-size:11.5px;text-transform:uppercase;letter-spacing:.4px}
.portal-table th:first-child{border-radius:8px 0 0 8px}
.portal-table th:last-child{border-radius:0 8px 8px 0}
.portal-table td{padding:10px 12px;border-bottom:1px solid var(--border)}
.portal-table tr:hover td{background:rgba(37,99,235,.02)}

.portal-form .field{margin-bottom:14px}
.portal-form label{display:block;font-size:12px;font-weight:700;color:var(--text-secondary);margin-bottom:5px}
.portal-form input,.portal-form textarea,.portal-form select{width:100%;padding:9px 12px;border:1px solid var(--border-strong);border-radius:8px;font-size:13.5px;background:white;transition:border .15s}
.portal-form input:focus,.portal-form textarea:focus,.portal-form select:focus{border-color:var(--blue-accent);outline:none;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.portal-form .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}

@media(max-width:768px){
  .portal-shell{padding:16px}
  .stat-grid{grid-template-columns:1fr 1fr}
  .portal-nav-links{display:none}
  .portal-form .form-row{grid-template-columns:1fr}
}
</style>
</head>
<body>

<nav class="portal-nav">
  <div class="portal-brand">
    <div class="brand-mark">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 2L4 6v6c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10V6l-8-4z" fill="white" fill-opacity=".95"/><path d="M12 6v12M9 9h6" stroke="#0B1F3A" stroke-width="1.6" stroke-linecap="round"/></svg>
    </div>
    <div>
      <strong>OpenGate Camp Connect</strong>
      <span>MEMBER PORTAL</span>
    </div>
  </div>
  <div class="portal-nav-links">
    <a href="{{ route('portal.dashboard') }}" class="{{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">Dashboard</a>
    <a href="{{ route('portal.registrations') }}" class="{{ request()->routeIs('portal.registrations') ? 'active' : '' }}">Registrations</a>
    <a href="{{ route('portal.pledges') }}" class="{{ request()->routeIs('portal.pledges') ? 'active' : '' }}">Pledges</a>
    <a href="{{ route('portal.profile') }}" class="{{ request()->routeIs('portal.profile') ? 'active' : '' }}">Profile</a>
    <a href="{{ route('portal.family') }}" class="{{ request()->routeIs('portal.family') ? 'active' : '' }}">Family</a>
    <a href="{{ route('portal.contributions') }}" class="{{ request()->routeIs('portal.contributions') ? 'active' : '' }}">Contributions</a>
    <a href="{{ route('portal.activations') }}" class="{{ request()->routeIs('portal.activations') ? 'active' : '' }}">Activations</a>
    <a href="{{ route('portal.settings') }}" class="{{ request()->routeIs('portal.settings') ? 'active' : '' }}">Settings</a>
  </div>
  <div class="portal-user">
    <div class="avatar">{{ substr(Auth::user()->name ?? 'M', 0, 1) }}</div>
    <span>{{ Auth::user()->name }}</span>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="btn-portal">Sign Out</button>
    </form>
  </div>
</nav>

<div class="portal-shell">
  <div class="portal-content">
    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;margin-bottom:18px;color:#166534;font-size:13.5px;font-weight:600"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M20 6L9 17l-5-5"/></svg> {{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:18px;color:#991b1b;font-size:13.5px;font-weight:600"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> {{ session('error') }}</div>
    @endif
    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:18px;color:#991b1b;font-size:13px">
      @foreach($errors->all() as $e)
      <div><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> {{ $e }}</div>
      @endforeach
    </div>
    @endif

    @yield('content')
  </div>
</div>

<div class="toast-stack" id="toastStack"></div>
@include('partials.scripts')
@stack('scripts')
</body>
</html>
