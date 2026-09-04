@include('partials.styles-core')
@include('partials.styles-components')
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — OpenGate Camp Connect</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.login-shell{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;
  background:
    radial-gradient(1200px 600px at 100% -10%, rgba(37,99,235,.08), transparent 60%),
    radial-gradient(900px 500px at -10% 10%, rgba(11,31,58,.06), transparent 55%),
    var(--bg);}
.login-card{width:100%;max-width:420px;background:var(--glass-bg-solid);backdrop-filter:blur(18px);
  border:1px solid var(--glass-border);border-radius:var(--radius-xl);box-shadow:var(--shadow-lg);
  padding:36px 32px;}
.login-brand{display:flex;align-items:center;gap:12px;margin-bottom:26px;}
.login-brand .brand-mark{width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg, var(--blue-accent), #4C86F5);
  display:flex;align-items:center;justify-content:center;box-shadow:0 6px 16px rgba(37,99,235,.4);flex-shrink:0;}
.login-brand strong{display:block;font-size:16px;font-weight:800;line-height:1.25;}
.login-brand span{display:block;color:var(--text-tertiary);font-size:11.5px;font-weight:600;letter-spacing:.4px;}
.login-card h1{font-size:20px;font-weight:800;margin:0 0 4px;}
.login-card .sub{font-size:13px;color:var(--text-secondary);font-weight:500;margin:0 0 22px;}
.login-error{background:var(--danger-bg);color:var(--danger);border:1px solid rgba(220,38,38,.2);
  border-radius:10px;padding:10px 14px;font-size:12.5px;font-weight:700;margin-bottom:14px;}
.login-hint{font-size:11.5px;color:var(--text-muted);margin-top:4px;display:block;}
.login-hint code{color:var(--text-secondary);background:var(--blue-light);padding:1px 5px;border-radius:4px;font-size:11px;}
.field + .field{margin-top:0;}
.login-actions{margin-top:8px;}
.w-full{width:100%;justify-content:center;display:inline-flex;}
.login-icon{position:absolute;right:12px;top:50%;transform:translateY(-50%);width:36px;height:36px;
  border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;
  pointer-events:none;transition:all .2s ease;}
.login-icon.email-icon{background:var(--info-bg);color:var(--info);}
.login-icon.phone-icon{background:var(--success-bg);color:var(--success);}
.field-relative{position:relative;}
.field-relative input{padding-right:52px;}
</style>
</head>
<body>
<div class="login-shell">
  <div class="login-card fade-in">
    <div class="login-brand">
      <div class="brand-mark">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2L4 6v6c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10V6l-8-4z" fill="white" fill-opacity=".95"/><path d="M12 6v12M9 9h6" stroke="#0B1F3A" stroke-width="1.6" stroke-linecap="round"/></svg>
      </div>
      <div>
        <strong>OpenGate Camp Connect</strong>
        <span>Connecting Students &middot; Restoring Faith &middot; Encountering Jesus</span>
      </div>
    </div>

    
    @if($errors->any())
      <div class="login-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.attempt') }}">
      @csrf
      <div class="field">
        <label>Email or Phone Number</label>
        <div class="field-relative">
          <input type="text" name="login" id="loginInput" value="{{ old('login') }}" placeholder="you@opengatecamp.org or +255 7XX XXX XXX" required autofocus oninput="detectLoginType(this)">
          <div class="login-icon" id="loginIcon"></div>
        </div>
        <span class="login-hint" id="loginHint">Enter your email address or phone number</span>
      </div>
      <div class="field" style="margin-top:14px">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <div class="field-check" style="margin-top:14px">
        <input type="checkbox" class="checkbox" id="remember" name="remember">
        <label for="remember">Keep me signed in</label>
      </div>
      <div class="login-actions">
        <button type="submit" class="btn btn-accent w-full">Sign In</button>
      </div>
    </form>
  </div>
</div>

<div class="toast-stack" id="toastStack"></div>
@include('partials.scripts')

<script>
function detectLoginType(el) {
  var v = el.value.trim();
  var icon = document.getElementById('loginIcon');
  var hint = document.getElementById('loginHint');

  if (!v) {
    icon.className = 'login-icon';
    icon.innerHTML = '';
    hint.textContent = 'Enter your email address or phone number';
    return;
  }

  var isEmail = v.indexOf('@') !== -1;
  var isPhone = /^[0-9+]/.test(v);

  if (isEmail) {
    icon.className = 'login-icon email-icon';
    icon.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16a1 1 0 011 1v10a1 1 0 01-1 1H4a1 1 0 01-1-1V7a1 1 0 011-1z"/><polyline points="3 7 12 13 21 7"/></svg>';
    hint.textContent = 'Signing in with email';
  } else if (isPhone) {
    icon.className = 'login-icon phone-icon';
    icon.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1 1 .4 2 .7 2.9a2 2 0 01-.5 2.1L8 10a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.5c.9.3 1.9.6 2.9.7a2 2 0 011.7 2z"/></svg>';
    hint.textContent = 'Signing in with phone — default password: password';
  } else {
    icon.className = 'login-icon';
    icon.innerHTML = '';
    hint.textContent = 'Enter your email address or phone number';
  }
}
</script>
</body>
</html>
