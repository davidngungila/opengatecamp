<style>
:root{
  --navy-900:#0B1F3A;
  --navy-800:#0E2647;
  --navy-700:#123A63;
  --blue-accent:#2563EB;
  --blue-accent-dark:#1D4ED8;
  --blue-light:#EAF2FF;
  --bg:#F6F8FB;
  --white:#FFFFFF;
  --text-primary:#172033;
  --text-secondary:#64748B;
  --text-tertiary:#94A3B8;
  --border:rgba(15,23,42,.08);
  --border-strong:rgba(15,23,42,.14);
  --glass-bg:rgba(255,255,255,.70);
  --glass-bg-solid:rgba(255,255,255,.92);
  --glass-border:rgba(255,255,255,.55);
  --success:#16A34A;
  --success-bg:#ECFDF3;
  --warning:#D97706;
  --warning-bg:#FFFBEB;
  --danger:#DC2626;
  --danger-bg:#FEF2F2;
  --info:#2563EB;
  --info-bg:#EFF6FF;
  --purple:#7C3AED;
  --purple-bg:#F5F3FF;
  --radius-sm:10px;
  --radius-md:14px;
  --radius-lg:20px;
  --radius-xl:26px;
  --shadow-sm:0 2px 8px rgba(11,31,58,.06);
  --shadow-md:0 8px 24px rgba(11,31,58,.08);
  --shadow-lg:0 20px 48px rgba(11,31,58,.14);
  --shadow-glass:0 10px 34px rgba(11,31,58,.10);
  --sidebar-w:280px;
  --sidebar-w-collapsed:82px;
  --topbar-h:72px;
}
*,*::before,*::after{box-sizing:border-box;}
html,body{height:100%;}
body{
  margin:0;
  font-family:'Manrope',-apple-system,BlinkMacSystemFont,sans-serif;
  background:
    radial-gradient(1200px 600px at 100% -10%, rgba(37,99,235,.06), transparent 60%),
    radial-gradient(900px 500px at -10% 10%, rgba(11,31,58,.05), transparent 55%),
    var(--bg);
  color:var(--text-primary);
  -webkit-font-smoothing:antialiased;
  overflow-x:hidden;
}
::-webkit-scrollbar{width:8px;height:8px;}
::-webkit-scrollbar-track{background:transparent;}
::-webkit-scrollbar-thumb{background:rgba(15,23,42,.15);border-radius:10px;}
::-webkit-scrollbar-thumb:hover{background:rgba(15,23,42,.25);}
a{color:inherit;text-decoration:none;}
button{font-family:inherit;cursor:pointer;}
input,select,textarea{font-family:inherit;}
:focus-visible{outline:2px solid var(--blue-accent);outline-offset:2px;}
svg{display:block;}
@media (prefers-reduced-motion: reduce){*{animation-duration:.01ms !important;transition-duration:.01ms !important;}}

.app-shell{display:flex;min-height:100vh;}

.sidebar{
  width:var(--sidebar-w);
  flex-shrink:0;
  background:linear-gradient(185deg, var(--navy-900) 0%, #0A1B33 100%);
  position:fixed;top:0;left:0;bottom:0;
  z-index:60;
  display:flex;flex-direction:column;
  transition:width .28s cubic-bezier(.4,0,.2,1), transform .28s cubic-bezier(.4,0,.2,1);
  box-shadow:4px 0 30px rgba(0,0,0,.12);
}
.sidebar.collapsed{width:var(--sidebar-w-collapsed);}
.sidebar-brand{
  display:flex;align-items:center;gap:12px;
  padding:22px 20px;
  border-bottom:1px solid rgba(255,255,255,.08);
  min-height:var(--topbar-h);
}
.sidebar-brand .brand-mark{
  width:42px;height:42px;flex-shrink:0;border-radius:12px;
  background:linear-gradient(135deg, var(--blue-accent), #4C86F5);
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 6px 16px rgba(37,99,235,.4);
}
.sidebar-brand .brand-text{overflow:hidden;white-space:nowrap;transition:opacity .2s;}
.sidebar.collapsed .brand-text{opacity:0;width:0;}
.sidebar-brand .brand-text strong{display:block;color:#fff;font-size:15.5px;font-weight:800;letter-spacing:.2px;line-height:1.25;}
.sidebar-brand .brand-text span{display:block;color:rgba(255,255,255,.55);font-size:11.5px;font-weight:500;letter-spacing:.4px;}

.sidebar-scroll{flex:1;overflow-y:auto;overflow-x:hidden;padding:14px 12px 24px;}
.sidebar-scroll::-webkit-scrollbar-thumb{background:rgba(255,255,255,.15);}

.nav-single{
  display:flex;align-items:center;gap:12px;
  padding:11px 12px;margin-bottom:6px;border-radius:12px;
  color:rgba(255,255,255,.82);font-weight:600;font-size:14px;
  transition:background .15s, color .15s;position:relative;
}
.nav-single:hover{background:rgba(255,255,255,.06);color:#fff;}
.nav-single.active{background:rgba(255,255,255,.12);color:#fff;box-shadow:inset 0 0 0 1px rgba(255,255,255,.08);}
.nav-single .nav-icon{width:20px;height:20px;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.nav-single .nav-label{white-space:nowrap;overflow:hidden;transition:opacity .2s;}
.sidebar.collapsed .nav-label,.sidebar.collapsed .group-title,.sidebar.collapsed .chevron{opacity:0;width:0;}

.nav-group{margin-top:14px;}
.group-title{
  padding:0 12px 8px;font-size:10.5px;font-weight:700;letter-spacing:1.4px;
  color:rgba(255,255,255,.32);white-space:nowrap;overflow:hidden;
}
.nav-parent{
  display:flex;align-items:center;gap:12px;width:100%;
  padding:11px 12px;margin-bottom:2px;border-radius:12px;border:none;background:transparent;
  color:rgba(255,255,255,.82);font-weight:600;font-size:14px;text-align:left;
  transition:background .15s,color .15s;
}
.nav-parent:hover{background:rgba(255,255,255,.06);color:#fff;}
.nav-parent .nav-icon{width:20px;height:20px;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.nav-parent .nav-label{flex:1;white-space:nowrap;overflow:hidden;}
.chevron{width:14px;height:14px;flex-shrink:0;transition:transform .22s;opacity:.6;}
.nav-parent.expanded .chevron{transform:rotate(90deg);}
.nav-children{
  max-height:0;overflow:hidden;transition:max-height .28s ease;
  padding-left:20px;position:relative;
}
.nav-children.open{max-height:600px;}
.nav-children::before{content:'';position:absolute;left:31px;top:2px;bottom:8px;width:1px;background:rgba(255,255,255,.1);}
.nav-child{
  display:block;padding:9px 12px 9px 20px;margin:1px 0;border-radius:10px;
  color:rgba(255,255,255,.62);font-size:13.5px;font-weight:500;position:relative;
}
.nav-child:hover{color:#fff;background:rgba(255,255,255,.05);}
.nav-child.active{color:#fff;background:rgba(37,99,235,.28);font-weight:700;box-shadow:inset 0 0 0 1px rgba(255,255,255,.1);}

.sidebar-footer{padding:14px 12px;border-top:1px solid rgba(255,255,255,.08);}
.collapse-btn{
  width:100%;display:flex;align-items:center;justify-content:center;gap:8px;
  padding:10px;border-radius:10px;border:1px solid rgba(255,255,255,.12);
  background:rgba(255,255,255,.04);color:rgba(255,255,255,.7);font-size:12.5px;font-weight:600;
}
.collapse-btn:hover{background:rgba(255,255,255,.08);color:#fff;}
.collapse-btn svg{transition:transform .25s;}
.sidebar.collapsed .collapse-btn svg{transform:rotate(180deg);}
.sidebar.collapsed .collapse-btn span{display:none;}

.tooltip-wrap{position:relative;}
.sidebar.collapsed .tooltip-wrap:hover .tt{
  opacity:1;visibility:visible;transform:translateX(0);
}
.tt{
  position:absolute;left:calc(100% + 14px);top:50%;transform:translateY(-50%) translateX(-6px);
  background:var(--navy-900);color:#fff;padding:7px 12px;border-radius:8px;font-size:12.5px;font-weight:600;
  white-space:nowrap;opacity:0;visibility:hidden;transition:.15s;pointer-events:none;z-index:80;
  box-shadow:var(--shadow-lg);
}

.main-wrap{flex:1;margin-left:var(--sidebar-w);transition:margin-left .28s cubic-bezier(.4,0,.2,1),padding-top .28s cubic-bezier(.4,0,.2,1);min-width:0;}
.main-wrap.sidebar-collapsed{margin-left:var(--sidebar-w-collapsed);}

.topbar{
  height:var(--topbar-h);position:fixed;top:0;left:var(--sidebar-w);right:0;
  z-index:50;
  background:rgba(255,255,255,.78);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
  padding:0 28px;gap:16px;
  transition:left .28s cubic-bezier(.4,0,.2,1);
}
.main-wrap.sidebar-collapsed .topbar{left:var(--sidebar-w-collapsed);}
.topbar-left{display:flex;align-items:center;gap:16px;min-width:0;}
.icon-btn{
  width:40px;height:40px;border-radius:11px;border:1px solid var(--border);background:var(--white);
  display:flex;align-items:center;justify-content:center;color:var(--text-secondary);flex-shrink:0;
  transition:.15s;position:relative;
}
.icon-btn:hover{background:var(--blue-light);color:var(--blue-accent);border-color:rgba(37,99,235,.25);}
.crumb-wrap{min-width:0;}
.crumb{font-size:12px;color:var(--text-tertiary);font-weight:600;display:flex;align-items:center;gap:6px;margin-bottom:2px;}
.page-title{font-size:19px;font-weight:800;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.topbar-right{display:flex;align-items:center;gap:10px;flex-shrink:0;}
.search-box{
  display:flex;align-items:center;gap:8px;background:var(--white);border:1px solid var(--border);
  border-radius:11px;padding:0 14px;height:40px;width:260px;transition:.15s;
}
.search-box:focus-within{border-color:var(--blue-accent);box-shadow:0 0 0 3px rgba(37,99,235,.12);}
.search-box input{border:none;outline:none;background:transparent;font-size:13.5px;width:100%;color:var(--text-primary);}
.search-box svg{flex-shrink:0;color:var(--text-tertiary);}
.badge-dot{position:absolute;top:7px;right:7px;width:8px;height:8px;border-radius:50%;background:var(--danger);border:2px solid var(--white);}
.avatar{
  width:40px;height:40px;border-radius:11px;background:linear-gradient(135deg,var(--navy-800),var(--blue-accent));
  color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;flex-shrink:0;
}
.user-chip{display:flex;align-items:center;gap:10px;padding:6px 8px 6px 6px;border-radius:12px;border:1px solid transparent;position:relative;}
.user-chip:hover{background:var(--white);border-color:var(--border);}
.user-chip .u-meta{text-align:left;}
.user-chip .u-name{font-size:13px;font-weight:700;color:var(--text-primary);line-height:1.2;}
.user-chip .u-role{font-size:11.5px;color:var(--text-tertiary);font-weight:600;}

.dropdown-panel{
  position:absolute;top:calc(100% + 10px);right:0;width:340px;
  background:var(--glass-bg-solid);backdrop-filter:blur(20px);
  border:1px solid var(--glass-border);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);
  z-index:90;overflow:hidden;opacity:0;transform:translateY(-8px);pointer-events:none;transition:.18s ease;
}
.dropdown-panel.open{opacity:1;transform:translateY(0);pointer-events:auto;}
.dropdown-header{padding:16px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.dropdown-header strong{font-size:14.5px;font-weight:800;}
.link-btn{background:none;border:none;color:var(--blue-accent);font-size:12.5px;font-weight:700;padding:4px;}
.dropdown-list{max-height:360px;overflow-y:auto;}
.notif-item{display:flex;gap:12px;padding:13px 18px;border-bottom:1px solid var(--border);}
.notif-item:hover{background:rgba(37,99,235,.04);}
.notif-item .n-ico{width:36px;height:36px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.notif-item .n-body p{margin:0 0 3px;font-size:13px;font-weight:600;color:var(--text-primary);}
.notif-item .n-body span{font-size:11.5px;color:var(--text-tertiary);font-weight:600;}
.menu-item{display:flex;align-items:center;gap:10px;padding:12px 18px;font-size:13.5px;font-weight:600;color:var(--text-primary);cursor:pointer;}
.menu-item:hover{background:rgba(37,99,235,.06);color:var(--blue-accent);}
.menu-item.danger:hover{background:var(--danger-bg);color:var(--danger);}
.menu-divider{height:1px;background:var(--border);margin:4px 0;}

.page-content{padding:calc(var(--topbar-h) + 26px) 28px 60px;max-width:1500px;}
.fade-in{animation:fadeIn .35s ease both;}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px);}to{opacity:1;transform:translateY(0);}}

.welcome-block{margin-bottom:22px;}
.welcome-block h1{font-size:24px;font-weight:800;margin:0 0 4px;}
.welcome-block p{margin:0;color:var(--text-secondary);font-size:14px;font-weight:500;}

.section-head{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:16px;flex-wrap:wrap;}
.section-head h2{font-size:17px;font-weight:800;margin:0;}
.section-head .sub{font-size:12.5px;color:var(--text-tertiary);font-weight:600;margin-top:2px;}

.glass-card{
  background:var(--glass-bg);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
  border:1px solid var(--glass-border);border-radius:var(--radius-lg);
  box-shadow:var(--shadow-glass);padding:20px;
}
.solid-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);padding:20px;}
</style>
