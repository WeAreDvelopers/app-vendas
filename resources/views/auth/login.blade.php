<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Catálogo ML</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --notion-bg: #f6f5f4;
      --notion-card: #fff;
      --notion-border: #e5e7eb;
    }
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    .login-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      padding: 40px;
      width: 100%;
      max-width: 420px;
    }
    .brand {
      font-size: 2rem;
      font-weight: 700;
      text-align: center;
      margin-bottom: 8px;
    }
    .brand-subtitle {
      text-align: center;
      color: #6b7280;
      margin-bottom: 32px;
      font-size: 0.9rem;
    }
    .form-label {
      font-weight: 600;
      color: #374151;
      margin-bottom: 8px;
    }
    .form-control {
      border-radius: 10px;
      border: 1px solid var(--notion-border);
      padding: 12px 16px;
      font-size: 0.95rem;
    }
    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .btn-login {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      border-radius: 10px;
      padding: 12px;
      font-weight: 600;
      color: white;
      width: 100%;
      margin-top: 24px;
      transition: transform 0.2s;
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
      color: white;
    }
    .form-check-label {
      color: #6b7280;
      font-size: 0.9rem;
    }
    .alert {
      border-radius: 10px;
      border: none;
    }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="brand">📦 Catálogo ML</div>
    <div class="brand-subtitle">Sistema de Gestão de Produtos</div>

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
        <label for="email" class="form-label">
          <i class="bi bi-envelope me-1"></i> E-mail
        </label>
        <input
          type="email"
          class="form-control @error('email') is-invalid @enderror"
          id="email"
          name="email"
          value="{{ old('email') }}"
          placeholder="seu@email.com"
          required
          autofocus
        >
        @error('email')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">
          <i class="bi bi-lock me-1"></i> Senha
        </label>
        <input
          type="password"
          class="form-control @error('password') is-invalid @enderror"
          id="password"
          name="password"
          placeholder="••••••••"
          required
        >
        @error('password')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="form-check mb-3">
        <input
          type="checkbox"
          class="form-check-input"
          id="remember"
          name="remember"
        >
        <label class="form-check-label" for="remember">
          Lembrar de mim
        </label>
      </div>

      <button type="submit" class="btn btn-login">
        <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
      </button>
    </form>

    <div class="text-center mt-4">
      <small class="text-muted">
        <i class="bi bi-shield-lock me-1"></i>
        Acesso restrito a usuários autorizados
      </small>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
