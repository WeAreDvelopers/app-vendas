<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Painel')</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --notion-bg: #f6f5f4;
      --notion-card: #fff;
      --notion-muted: #6b7280;
      --notion-border: #e5e7eb;
    }
    html, body { height: 100%; background: var(--notion-bg); }
    .app-shell { display:flex; min-height:100vh; }
    .sidebar { width: 260px; min-width: 260px; background: #ffffff; border-right: 1px solid var(--notion-border); position: sticky; top:0; height:100vh; padding: 12px 10px; }
    .sidebar .brand { font-weight: 700; font-size: 1.1rem; letter-spacing:.3px; }
    .sidebar a.nav-link { border-radius: 10px; color:#111827; }
    .sidebar a.nav-link.active, .sidebar a.nav-link:hover { background: #2c2c2cff; color: #fff; }
    .topbar { position: sticky; top:0; z-index: 20; background: var(--notion-bg); border-bottom: 1px solid var(--notion-border); }
    .page { padding: 24px; 
      /* max-width: 1200px; */
       margin: 0 auto; }
    .page-title { font-weight: 800; letter-spacing:.2px; font-size: 1.5rem; }
    .notion-card { background: var(--notion-card); border:1px solid var(--notion-border); border-radius: 12px; padding: 16px; }
    .muted { color: var(--notion-muted); }
    .chip { font-size: .75rem; border:1px solid var(--notion-border); border-radius: 20px; padding: 4px 10px; background:#fff; }
    .table > :not(caption) > * > * { background: transparent; }
    .search-input { border-radius: 10px; }
    .avatar { width:32px;height:32px;border-radius:50%;background:#d1d5db;display:inline-block; }
  </style>
  @stack('head')
</head>
<body>
<div class="app-shell">
  <aside class="sidebar d-none d-md-flex flex-column gap-2">
    <div class="d-flex align-items-center justify-content-between px-2 pt-1 pb-2">
      <div class="brand">📦 Catálogo ML</div>
    </div>
    <nav class="nav nav-pills flex-column">
      <a class="nav-link {{ request()->routeIs('panel.dashboard') ? 'active' : '' }}" href="{{ route('panel.dashboard') }}"><i class="bi bi-grid me-2"></i>Dashboard</a>
      <div class="mt-2 mb-1 small text-uppercase text-muted px-2">Fluxo</div>
      <a class="nav-link {{ request()->routeIs('panel.suppliers.*') ? 'active' : '' }}" href="{{ route('panel.suppliers.index') }}"><i class="bi bi-building me-2"></i>Fornecedores</a>
      <a class="nav-link {{ request()->routeIs('panel.imports.*') ? 'active' : '' }}" href="{{ route('panel.imports.index') }}"><i class="bi bi-upload me-2"></i>Importações</a>
      <a class="nav-link {{ request()->routeIs('panel.products.*') ? 'active' : '' }}" href="{{ route('panel.products.index') }}"><i class="bi bi-box-seam me-2"></i>Produtos</a>
      <a class="nav-link {{ request()->routeIs('panel.listings.*') ? 'active' : '' }}" href="{{ route('panel.listings.index') }}"><i class="bi bi-megaphone me-2"></i>Publicações</a>
      <a class="nav-link {{ request()->routeIs('panel.orders.*') ? 'active' : '' }}" href="{{ route('panel.orders.index') }}"><i class="bi bi-receipt me-2"></i>Pedidos</a>
      <div class="mt-2 mb-1 small text-uppercase text-muted px-2">Sistema</div>
      <a class="nav-link {{ request()->routeIs('panel.monitor.queues') ? 'active' : '' }}" href="{{ route('panel.monitor.queues') }}"><i class="bi bi-activity me-2"></i>Filas / Monitor</a>
    </nav>
    <div class="mt-auto px-2 pb-2">
      <div class="d-flex align-items-center gap-2">
        <span class="avatar"></span>
        <div class="flex-grow-1">
          <div class="small fw-semibold">{{ Auth::user()->name ?? 'Operador' }}</div>
          <div class="small text-muted">{{ Auth::user()->email ?? 'online' }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-sm btn-outline-secondary" title="Sair">
            <i class="bi bi-box-arrow-right"></i>
          </button>
        </form>
      </div>
    </div>
  </aside>

  <main class="flex-grow-1">
    <div class="topbar">
      <div class="container-fluid py-2">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex d-md-none align-items-center">
            <a href="#" onclick="document.querySelector('.sidebar').classList.toggle('d-none'); return false;"
               class="btn btn-outline-secondary btn-sm"><i class="bi bi-list"></i></a>
          </div>
          <form class="d-none d-md-flex" action="{{ url()->current() }}" method="get" style="min-width:360px;">
            <input name="q" value="{{ request('q') }}" type="search" class="form-control form-control-sm search-input" placeholder="Buscar...">
          </form>
          <div class="d-flex align-items-center gap-2">
            <span class="chip">v0.1 MVP</span>
          </div>
        </div>
      </div>
    </div>

    <div class="page">
      <div class="mb-3">
        <div class="page-title">@yield('page-title','')</div>
        @hasSection('page-subtitle')
          <div class="muted">@yield('page-subtitle')</div>
        @endif
      </div>

      @if(session('ok'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle me-2"></i>{{ session('ok') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif
      @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-x-circle me-2"></i>{{ session('error') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif
      @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif
      @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-x-circle me-2"></i><strong>Erro:</strong> {{ $errors->first() }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif

      @yield('content')
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
