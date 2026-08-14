<!doctype html>
<html lang="pt-br" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <title>Login - Fiply Vendas</title>
  <script>(function(){try{var t=localStorage.getItem('theme');if(!t){t=matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}document.documentElement.setAttribute('data-bs-theme',t);}catch(e){}})();</script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root, [data-bs-theme="light"] {
      color-scheme: light;
      --bs-primary: #1877f2; --bs-primary-rgb: 24,119,242;
      --app-bg: #e9f2fe; --app-bg-2: #f5f6f8;
      --app-surface: #ffffff; --app-border: #e6e8ec; --app-text: #111827; --app-muted: #667085;
      --app-accent: #1877f2; --app-shadow: 0 20px 60px rgba(16,18,27,.14), 0 2px 8px rgba(16,18,27,.06);
    }
    [data-bs-theme="dark"] {
      color-scheme: dark;
      --bs-primary: #4b9bff; --bs-primary-rgb: 75,155,255;
      --app-bg: #0b0e13; --app-bg-2: #0e1116;
      --app-surface: #161a22; --app-border: #252b36; --app-text: #e6e8ee; --app-muted: #9aa3b2;
      --app-accent: #4b9bff; --app-shadow: 0 20px 60px rgba(0,0,0,.5), 0 2px 8px rgba(0,0,0,.4);
    }
    body {
      min-height: 100dvh; display: flex; align-items: center; justify-content: center; padding: 24px;
      font-family: Inter, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      color: var(--app-text);
      background:
        radial-gradient(1200px 500px at 50% -10%, color-mix(in srgb, var(--app-accent) 18%, transparent), transparent 60%),
        linear-gradient(180deg, var(--app-bg), var(--app-bg-2));
      -webkit-font-smoothing: antialiased;
    }
    .login-card {
      background: var(--app-surface); border: 1px solid var(--app-border);
      border-radius: 18px; box-shadow: var(--app-shadow); padding: 36px 34px; width: 100%; max-width: 420px;
    }
    .brand-row { display: flex; align-items: center; justify-content: center; gap: 11px; margin-bottom: 6px; }
    .brand-mark { width: 44px; height: 44px; display: block; }
    .brand-name { font-size: 1.45rem; font-weight: 700; letter-spacing: -.02em; }
    .brand-name .accent { color: var(--app-accent); }
    .brand-subtitle { text-align: center; color: var(--app-muted); margin-bottom: 28px; font-size: .9rem; }
    .form-label { font-weight: 600; color: var(--app-text); margin-bottom: 6px; font-size: .875rem; }
    .form-control { border-radius: 10px; padding: 11px 14px; font-size: .95rem; }
    .btn-primary { --bs-btn-padding-y: .7rem; border-radius: 10px; font-weight: 600; width: 100%; margin-top: 8px; }
    .form-check-label { color: var(--app-muted); font-size: .9rem; }
    .alert { border-radius: 11px; }
    .pw-wrap { position: relative; }
    .pw-toggle { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); border: 0; background: transparent; color: var(--app-muted); width: 36px; height: 36px; border-radius: 8px; }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="brand-row">
      <span class="brand-mark">@include('partials.brand-mark')</span>
      <span class="brand-name">Fiply <span class="accent">Vendas</span></span>
    </div>
    <div class="brand-subtitle">Sistema de Gestão de Produtos e Vendas</div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-x-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-x-circle me-2"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}">
      @csrf

      <div class="mb-3">
        <label for="email" class="form-label">E-mail</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
               value="{{ old('email') }}" placeholder="seu@email.com" autocomplete="email" required autofocus>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Senha</label>
        <div class="pw-wrap">
          <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                 placeholder="••••••••" autocomplete="current-password" required>
          <button type="button" class="pw-toggle" id="pwToggle" aria-label="Mostrar senha" title="Mostrar/ocultar senha">
            <i class="bi bi-eye"></i>
          </button>
        </div>
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
      </div>

      <div class="form-check mb-2">
        <input type="checkbox" class="form-check-input" id="remember" name="remember">
        <label class="form-check-label" for="remember">Lembrar de mim</label>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
      </button>
    </form>

    <div class="text-center mt-4">
      <small class="muted" style="color:var(--app-muted)">
        <i class="bi bi-shield-lock me-1"></i> Acesso restrito a usuários autorizados
      </small>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function(){
      var btn = document.getElementById('pwToggle'), pw = document.getElementById('password');
      if(!btn || !pw) return;
      btn.addEventListener('click', function(){
        var show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        btn.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
      });
    })();
  </script>
</body>
</html>
