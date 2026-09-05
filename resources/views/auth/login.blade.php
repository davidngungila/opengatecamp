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
.login-shell{min-height:100vh;display:flex;background:
    radial-gradient(1200px 600px at 100% -10%, rgba(37,99,235,.08), transparent 60%),
    radial-gradient(900px 500px at -10% 10%, rgba(11,31,58,.06), transparent 55%),
    var(--bg);}

/* Left image slider */
.login-slider{flex:0 0 55%;max-width:55%;position:relative;overflow:hidden;background:#0B1F3A;}
.slider-brand{position:absolute;top:0;left:0;right:0;z-index:4;padding:34px 46px;}
.slider-brand strong{display:block;color:#fff;font-size:36px;font-weight:800;line-height:1.15;text-shadow:0 2px 12px rgba(0,0,0,.35);}
.slider-brand span{display:block;color:rgba(255,255,255,.85);font-size:17px;font-weight:600;letter-spacing:.4px;margin-top:8px;text-shadow:0 1px 8px rgba(0,0,0,.35);}
.slider-brand .brand-bar{width:44px;height:4px;background:linear-gradient(90deg,#4C86F5,transparent);border-radius:3px;margin-bottom:14px;}
.slider-track{position:absolute;inset:0;}
.slider-slide{position:absolute;inset:0;background-size:cover;background-position:center;opacity:0;transition:opacity 1.1s ease;display:flex;align-items:flex-start;}
.slider-slide.active{opacity:1;}
.slide-shade{position:absolute;inset:0;background:linear-gradient(180deg, rgba(6,16,36,.75) 0%, rgba(6,16,36,.38) 30%, rgba(6,16,36,.2) 50%, rgba(6,16,36,.45) 78%, rgba(6,16,36,.8) 100%);}
.slide-caption{position:relative;z-index:2;padding:172px 46px 44px;max-width:100%;}
.slide-caption .slide-tag{display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,.92);font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:12px;text-shadow:0 1px 6px rgba(0,0,0,.5);}
.slide-caption .slide-tag:before{content:'';width:26px;height:2px;background:linear-gradient(90deg, #4C86F5, transparent);border-radius:2px;}
.slide-caption h2{color:#fff;font-size:26px;font-weight:800;line-height:1.2;margin:0 0 8px;text-shadow:0 2px 10px rgba(0,0,0,.55);}
.slide-caption p{color:rgba(255,255,255,.88);font-size:13.5px;font-weight:500;margin:0;max-width:380px;text-shadow:0 1px 8px rgba(0,0,0,.5);}
.slide-caption .slide-brand{position:absolute;top:-64px;left:0;}
.slider-nav{position:absolute;left:0;right:0;bottom:20px;display:flex;justify-content:center;gap:8px;z-index:3;}
.slider-dot{width:8px;height:8px;border-radius:999px;border:none;cursor:pointer;background:rgba(255,255,255,.35);transition:all .25s;padding:0;}
.slider-dot.active{width:26px;background:#fff;}

/* Right login panel */
.login-panel{flex:1;display:flex;align-items:center;justify-content:center;padding:24px;}
.login-card{width:100%;max-width:440px;margin:0 auto;}
.login-org{text-align:center;margin-bottom:30px;}
.login-logo{width:88px;height:88px;object-fit:contain;border-radius:22px;margin:0 auto 16px;
  background:#fff;padding:8px;box-shadow:0 8px 24px rgba(37,99,235,.22);display:block;}
.login-org h1{font-size:17px;font-weight:800;color:var(--navy-900);margin:0 0 5px;line-height:1.3;}
.login-org p{font-size:13px;font-weight:600;color:var(--text-secondary);margin:0;}
.login-card h1{font-size:20px;font-weight:800;margin:0 0 4px;}
.login-card .sub{font-size:13px;color:var(--text-secondary);font-weight:500;margin:0 0 22px;}
.login-error{background:var(--danger-bg);color:var(--danger);border:1px solid rgba(220,38,38,.2);
  border-radius:10px;padding:11px 16px;font-size:13.5px;font-weight:700;margin-bottom:16px;}
.login-hint{font-size:13px;color:var(--text-muted);margin-top:6px;display:block;}
.login-hint code{color:var(--text-secondary);background:var(--blue-light);padding:1px 5px;border-radius:4px;font-size:11px;}
.field + .field{margin-top:0;}
.login-actions{margin-top:12px;}
.w-full{width:100%;justify-content:center;display:inline-flex;}
.login-icon{position:absolute;right:14px;top:50%;transform:translateY(-50%);width:40px;height:40px;
  border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;
  pointer-events:none;transition:all .2s ease;}
.login-icon.phone-icon{background:var(--success-bg);color:var(--success);}
.field-relative{position:relative;}
.field-relative input{padding-right:56px;}
.login-panel .field label{font-size:14px;}
.login-panel .field input,.login-panel .field select,.login-panel .field textarea{
  font-size:15px;padding:13px 15px;border-radius:12px;}
.login-panel .field-check label{font-size:13.5px;font-weight:600;}
.login-panel .btn{padding:13px 20px;font-size:15px;border-radius:12px;}
.login-footer{margin-top:26px;text-align:center;font-size:13px;color:var(--text-muted);letter-spacing:.3px;}

@media(max-width:900px){
  .login-slider{display:none;}
  .login-shell{padding:24px;}
}
</style>
</head>
<body>
<div class="login-shell">

  <div class="login-slider" id="loginSlider">
    <div class="slider-brand">
      <div class="brand-bar"></div>
      <strong>OpenGate Camp Connect</strong>
      <span>Connecting Students &middot; Restoring Faith &middot; Encountering Jesus</span>
    </div>
    <div class="slider-track">
      <div class="slider-slide active" style="background-image:url('https://res.cloudinary.com/dpyppzvzj/image/upload/v1775477696/0104_65_z85w9h.jpg')">
        <div class="slide-shade"></div>
        <div class="slide-caption">
          <span class="slide-tag">Connecting Students</span>
          <h2>One family. One mission.</h2>
          <p>Bringing students together around God's Word, on campus and beyond.</p>
        </div>
      </div>
      <div class="slider-slide" style="background-image:url('https://res.cloudinary.com/dpyppzvzj/image/upload/v1775477708/0104_84_tapexp.jpg')">
        <div class="slide-shade"></div>
        <div class="slide-caption">
          <span class="slide-tag">Restoring Faith</span>
          <h2>A place to be renewed.</h2>
          <p>Worship, testimonies and quiet moments that restore hope and purpose.</p>
        </div>
      </div>
      <div class="slider-slide" style="background-image:url('https://res.cloudinary.com/dpyppzvzj/image/upload/v1775477760/0204_23_jc7eqh.jpg')">
        <div class="slide-shade"></div>
        <div class="slide-caption">
          <span class="slide-tag">Encountering Jesus</span>
          <h2>Lives changed at camp.</h2>
          <p>Every session is an invitation to meet Jesus and be transformed by His grace.</p>
        </div>
      </div>
      <div class="slider-slide" style="background-image:url('https://res.cloudinary.com/dpyppzvzj/image/upload/v1775477720/0104_95_qxaupn.jpg')">
        <div class="slide-shade"></div>
        <div class="slide-caption">
          <span class="slide-tag">OpenGate Camp Connect</span>
          <h2>Connecting. Restoring. Encountering.</h2>
          <p>Your camp management and mission hub — registrations, pledges and more in one place.</p>
        </div>
      </div>
    </div>
    <div class="slider-nav" id="sliderNav"></div>
  </div>

  <div class="login-panel">
    <div class="login-card fade-in">
      <div class="login-org">
        <img src="{{ asset('logo.png') }}" alt="OpenGate Camp Connect" class="login-logo">
        <h1>Inter-Colleges Charismatic Catholic Renewal Tanzania</h1>
        <p>Archdiocese of Arusha and Diocese of Moshi</p>
      </div>

      @if($errors->any())
        <div class="login-error">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('login.attempt') }}">
        @csrf
        <div class="field">
          <label>Phone Number</label>
          <div class="field-relative">
            <input type="text" name="login" id="loginInput" value="{{ old('login') }}" placeholder="+255 7XX XXX XXX" required autofocus oninput="detectLoginType(this)">
            <div class="login-icon" id="loginIcon"></div>
          </div>
          <span class="login-hint" id="loginHint">Enter your phone number</span>
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

      <div class="login-footer">Connecting Students &middot; Restoring Faith &middot; Encountering Jesus</div>
    </div>
  </div>
</div>

<div class="toast-stack" id="toastStack"></div>
@include('partials.scripts')

<script>
(function () {
  var slides = document.querySelectorAll('.slider-slide');
  var nav = document.getElementById('sliderNav');
  var idx = 0;
  var timer = null;

  if (slides.length && nav) {
    for (var i = 0; i < slides.length; i++) {
      (function (i) {
        var dot = document.createElement('button');
        dot.className = 'slider-dot' + (i === 0 ? ' active' : '');
        dot.setAttribute('aria-label', 'Slide ' + (i + 1));
        dot.addEventListener('click', function () {
          go(i);
          restart();
        });
        nav.appendChild(dot);
      })(i);
    }

    function go(i) {
      slides[idx].classList.remove('active');
      nav.children[idx].classList.remove('active');
      idx = (i + slides.length) % slides.length;
      slides[idx].classList.add('active');
      nav.children[idx].classList.add('active');
    }

    function auto() {
      timer = setInterval(function () { go(idx + 1); }, 5000);
    }

    function restart() {
      clearInterval(timer);
      auto();
    }

    document.getElementById('loginSlider').addEventListener('mouseenter', function () { clearInterval(timer); });
    document.getElementById('loginSlider').addEventListener('mouseleave', auto);

    auto();
  }

  function detectLoginType(el) {
    var v = el.value.trim();
    var icon = document.getElementById('loginIcon');
    var hint = document.getElementById('loginHint');

    if (!v) {
      icon.className = 'login-icon';
      icon.innerHTML = '';
      hint.textContent = 'Enter your phone number';
      return;
    }

    var isPhone = /^[0-9+]/.test(v);

    if (isPhone) {
      icon.className = 'login-icon phone-icon';
      icon.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1 1 .4 2 .7 2.9a2 2 0 01-.5 2.1L8 10a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.5c.9.3 1.9.6 2.9.7a2 2 0 011.7 2z"/></svg>';
      hint.textContent = 'Signing in with phone';
    } else {
      icon.className = 'login-icon';
      icon.innerHTML = '';
      hint.textContent = 'Enter your phone number';
    }
  }

  window.detectLoginType = detectLoginType;
})();
</script>
</body>
</html>