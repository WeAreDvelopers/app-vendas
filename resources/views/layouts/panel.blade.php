<!doctype html>
<html lang="pt-br" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Painel')</title>
  {{-- Aplica o tema antes da pintura para evitar flash --}}
  <script>(function(){try{var t=localStorage.getItem('theme');if(!t){t=matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}document.documentElement.setAttribute('data-bs-theme',t);}catch(e){}})();</script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root, [data-bs-theme="light"] {
      color-scheme: light;
      --bs-primary: #2563eb; --bs-primary-rgb: 37,99,235;
      --bs-body-bg: #f5f6f8; --bs-body-color: #111827;
      --bs-border-color: #e6e8ec; --bs-secondary-color: #667085;
      --bs-link-color: #2563eb; --bs-link-hover-color: #1d4ed8;
      --app-surface: #ffffff; --app-surface-2: #f8fafc;
      --app-border: #e6e8ec; --app-text: #111827; --app-muted: #667085;
      --app-accent: #2563eb; --app-accent-soft: #eef4ff;
      --app-shadow: 0 1px 2px rgba(16,18,27,.04), 0 4px 16px rgba(16,18,27,.06);
      --app-green: #12a150; --app-green-soft: #e7f6ee;
      --app-amber: #b45309; --app-amber-soft: #fdf3e6;
      --app-radius: 14px;
    }
    [data-bs-theme="dark"] {
      color-scheme: dark;
      --bs-primary: #3b82f6; --bs-primary-rgb: 59,130,246;
      --bs-body-bg: #0e1116; --bs-body-color: #e6e8ee;
      --bs-border-color: #252b36; --bs-secondary-color: #9aa3b2;
      --bs-link-color: #60a5fa; --bs-link-hover-color: #93c5fd;
      --app-surface: #161a22; --app-surface-2: #1b2029;
      --app-border: #252b36; --app-text: #e6e8ee; --app-muted: #9aa3b2;
      --app-accent: #3b82f6; --app-accent-soft: rgba(59,130,246,.16);
      --app-shadow: 0 1px 2px rgba(0,0,0,.3), 0 6px 20px rgba(0,0,0,.35);
      --app-green: #3ddc84; --app-green-soft: rgba(61,220,132,.14);
      --app-amber: #f0a742; --app-amber-soft: rgba(240,167,66,.14);
    }
    html, body { min-height: 100%; }
    body { font-family: Inter, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background: var(--bs-body-bg); color: var(--app-text); -webkit-font-smoothing: antialiased; }
    .app-shell { display:flex; min-height:100dvh; }
    .sidebar { width: 248px; min-width: 248px; background: var(--app-surface); border-right: 1px solid var(--app-border);
      position: sticky; top:0; height:100dvh; padding: 14px 10px; }
    .sidebar .brand { font-weight: 700; font-size: 1.02rem; letter-spacing:-.01em; display:flex; align-items:center; gap:10px; padding:6px 6px 10px; }
    .brand-mark { width:28px; height:28px; border-radius:8px; background:var(--app-accent); color:#fff; display:grid; place-items:center; font-size:1rem; }
    .sidebar .nav-sec { font-size:.68rem; letter-spacing:.08em; text-transform:uppercase; color:var(--app-muted); font-weight:600; padding:12px 10px 5px; }
    .sidebar a.nav-link { border-radius: 10px; color: var(--app-text); font-weight:500; padding:.5rem .65rem; display:flex; align-items:center; position:relative; transition:background .15s, color .15s; }
    .sidebar a.nav-link i { opacity:.7; transition:opacity .15s; }
    .sidebar a.nav-link:hover { background: var(--app-surface-2); }
    .sidebar a.nav-link.active { background: var(--app-accent-soft); color: var(--app-accent); font-weight:600; }
    .sidebar a.nav-link.active i { opacity:1; }
    .sidebar a.nav-link.active::before { content:""; position:absolute; left:-10px; top:8px; bottom:8px; width:3px; border-radius:3px; background:var(--app-accent); }
    .topbar { position: sticky; top:0; z-index: 20; background: color-mix(in srgb, var(--bs-body-bg) 82%, transparent);
      backdrop-filter: blur(8px); border-bottom: 1px solid var(--app-border); }
    .page { padding: 24px; max-width: 1240px; margin: 0 auto; }
    .page-title { font-weight: 700; letter-spacing:-.02em; font-size: 1.5rem; }
    .notion-card { background: var(--app-surface); border:1px solid var(--app-border); border-radius: var(--app-radius); padding: 18px; box-shadow: var(--app-shadow); }
    .muted { color: var(--app-muted); }
    .chip { font-size: .75rem; border:1px solid var(--app-border); border-radius: 20px; padding: 4px 10px; background: var(--app-surface); color: var(--app-text); }
    .table { --bs-table-bg: transparent; }
    .table > :not(caption) > * > * { background: transparent; }
    .table thead th { color: var(--app-muted); font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; font-weight:600; }
    .table td, .table th { border-color: var(--app-border); }
    .tabular, td.num { font-variant-numeric: tabular-nums; }
    .btn { border-radius: 9px; font-weight: 500; }
    .form-control, .form-select, .search-input { border-radius: 10px; }
    .dropdown-menu { border-radius: 12px; border-color: var(--app-border); box-shadow: var(--app-shadow); }
    .alert { border-radius: 12px; }
    .avatar { width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,var(--app-accent),#7aa2f7); color:#fff; display:grid; place-items:center; font-weight:600; font-size:.8rem; }
    @media (prefers-reduced-motion: reduce){ *{ transition:none !important; } }
  </style>
  @stack('head')
</head>
<body>
<div class="app-shell">
  <aside class="sidebar d-none d-md-flex flex-column gap-2">
    <div class="d-flex align-items-center justify-content-between px-2 pt-1 pb-2">
      <div class="brand"><span class="brand-mark"><i class="bi bi-box-seam"></i></span> Catálogo ML</div>
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
      <a class="nav-link {{ request()->routeIs('panel.integrations.*') ? 'active' : '' }}" href="{{ route('panel.integrations.index') }}"><i class="bi bi-plug me-2"></i>Integrações</a>
      <a class="nav-link {{ request()->routeIs('panel.companies.*') ? 'active' : '' }}" href="{{ route('panel.companies.index') }}"><i class="bi bi-building-gear me-2"></i>Empresas</a>
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
            <!-- Company Selector -->
            @if(isset($currentCompany) && auth()->user()->companies->count() > 1)
            <div class="dropdown">
              <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="companyDropdown" data-bs-toggle="dropdown">
                <i class="bi bi-building"></i> {{ $currentCompany->name }}
              </button>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="companyDropdown">
                @foreach(auth()->user()->companies as $company)
                  <li>
                    <form method="POST" action="{{ route('panel.companies.switch') }}" class="d-inline">
                      @csrf
                      <input type="hidden" name="company_id" value="{{ $company->id }}">
                      <button type="submit" class="dropdown-item {{ $company->id == $currentCompany->id ? 'active' : '' }}">
                        <i class="bi bi-building me-2"></i>{{ $company->name }}
                        @if($company->id == $currentCompany->id)
                          <i class="bi bi-check2 float-end"></i>
                        @endif
                      </button>
                    </form>
                  </li>
                @endforeach
                <li><hr class="dropdown-divider"></li>
                <li>
                  <a class="dropdown-item" href="{{ route('panel.companies.index') }}">
                    <i class="bi bi-gear me-2"></i>Gerenciar Empresas
                  </a>
                </li>
              </ul>
            </div>
            @elseif(isset($currentCompany))
            <span class="chip">
              <i class="bi bi-building"></i> {{ $currentCompany->name }}
            </span>
            @endif

            <!-- Theme toggle -->
            <button type="button" class="btn btn-sm btn-outline-secondary" id="themeToggle" title="Alternar tema" aria-label="Alternar tema claro/escuro">
              <i class="bi bi-moon-stars"></i>
            </button>

            <!-- Notification Bell -->
            <div class="dropdown">
              <button class="btn btn-sm btn-outline-secondary position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-bell"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none;">
                  0
                </span>
              </button>
              <div class="dropdown-menu dropdown-menu-end p-0" aria-labelledby="notificationDropdown" style="min-width: 380px; max-height: 500px;">
                <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                  <h6 class="mb-0">Notificações</h6>
                  <button type="button" class="btn btn-sm btn-link text-decoration-none" id="markAllRead" style="display: none;">
                    Marcar todas como lidas
                  </button>
                </div>
                <div id="notificationList" style="max-height: 400px; overflow-y: auto;">
                  <div class="text-center text-muted p-4">
                    <i class="bi bi-bell-slash fs-3"></i>
                    <p class="mb-0 mt-2">Nenhuma notificação</p>
                  </div>
                </div>
              </div>
            </div>
            <!-- <span class="chip">v0.1 MVP</span> -->
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

<!-- Notification System -->
<script>
(function() {
  'use strict';

  // Configurações
  const POLLING_INTERVAL = 10000; // 10 segundos
  const NOTIFICATION_SOUND_ENABLED = true;

  let lastNotificationCount = 0;
  let pollingTimer = null;
  let isPolling = false;

  // Elementos DOM
  const notificationBadge = document.getElementById('notificationBadge');
  const notificationList = document.getElementById('notificationList');
  const markAllReadBtn = document.getElementById('markAllRead');

  /**
   * Busca notificações do servidor
   */
  async function fetchNotifications() {
    if (isPolling) return; // Evita múltiplas chamadas simultâneas

    isPolling = true;

    try {
      const response = await fetch('{{ route("panel.notifications.index") }}', {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (!response.ok) {
        throw new Error('Erro ao buscar notificações');
      }

      const data = await response.json();

      if (data.success) {
        updateNotifications(data.notifications, data.unread_count);
      }
    } catch (error) {
      console.error('Erro ao buscar notificações:', error);
    } finally {
      isPolling = false;
    }
  }

  /**
   * Atualiza a interface com as notificações
   */
  function updateNotifications(notifications, unreadCount) {
    // Atualiza badge
    if (unreadCount > 0) {
      notificationBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
      notificationBadge.style.display = 'inline-block';
      markAllReadBtn.style.display = 'inline-block';
    } else {
      notificationBadge.style.display = 'none';
      markAllReadBtn.style.display = 'none';
    }

    // Toca som se houver novas notificações
    if (NOTIFICATION_SOUND_ENABLED && unreadCount > lastNotificationCount && lastNotificationCount > 0) {
      playNotificationSound();
    }
    lastNotificationCount = unreadCount;

    // Renderiza lista de notificações
    if (notifications.length === 0) {
      notificationList.innerHTML = `
        <div class="text-center text-muted p-4">
          <i class="bi bi-bell-slash fs-3"></i>
          <p class="mb-0 mt-2">Nenhuma notificação</p>
        </div>
      `;
    } else {
      notificationList.innerHTML = notifications.map(notification => renderNotification(notification)).join('');
    }
  }

  /**
   * Renderiza uma notificação individual
   */
  function renderNotification(notification) {
    const typeIcons = {
      success: 'bi-check-circle-fill text-success',
      info: 'bi-info-circle-fill text-info',
      warning: 'bi-exclamation-triangle-fill text-warning',
      error: 'bi-x-circle-fill text-danger'
    };

    const icon = notification.icon || typeIcons[notification.type] || 'bi-bell-fill';
    const timeAgo = formatTimeAgo(new Date(notification.created_at));

    return `
      <div class="notification-item p-3 border-bottom ${notification.read ? 'read' : 'unread'}" data-id="${notification.id}">
        <div class="d-flex gap-3">
          <div>
            <i class="bi ${icon} fs-5"></i>
          </div>
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-start">
              <strong class="d-block mb-1">${escapeHtml(notification.title)}</strong>
              <small class="text-muted">${timeAgo}</small>
            </div>
            <p class="mb-2 small">${escapeHtml(notification.message)}</p>
            ${notification.action_url ? `
              <a href="${notification.action_url}" class="btn btn-sm btn-outline-primary">
                ${escapeHtml(notification.action_text || 'Ver detalhes')}
              </a>
            ` : ''}
            <div class="mt-2">
              <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 mark-read-btn" data-id="${notification.id}">
                <i class="bi bi-check2"></i> Marcar como lida
              </button>
              <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 ms-3 delete-btn" data-id="${notification.id}">
                <i class="bi bi-trash"></i> Remover
              </button>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  /**
   * Marca notificação como lida
   */
  async function markAsRead(id) {
    try {
      const response = await fetch(`/panel/notifications/${id}/read`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (response.ok) {
        fetchNotifications(); // Atualiza lista
      }
    } catch (error) {
      console.error('Erro ao marcar notificação como lida:', error);
    }
  }

  /**
   * Marca todas como lidas
   */
  async function markAllAsRead() {
    try {
      const response = await fetch('/panel/notifications/read-all', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (response.ok) {
        fetchNotifications(); // Atualiza lista
      }
    } catch (error) {
      console.error('Erro ao marcar todas como lidas:', error);
    }
  }

  /**
   * Deleta notificação
   */
  async function deleteNotification(id) {
    try {
      const response = await fetch(`/panel/notifications/${id}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (response.ok) {
        fetchNotifications(); // Atualiza lista
      }
    } catch (error) {
      console.error('Erro ao deletar notificação:', error);
    }
  }

  /**
   * Toca som de notificação
   */
  function playNotificationSound() {
    // Cria um beep curto usando Web Audio API
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);

    oscillator.frequency.value = 800;
    oscillator.type = 'sine';
    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);

    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.2);
  }

  /**
   * Formata tempo relativo
   */
  function formatTimeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);

    if (seconds < 60) return 'agora mesmo';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}min atrás`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h atrás`;
    return `${Math.floor(seconds / 86400)}d atrás`;
  }

  /**
   * Escapa HTML para prevenir XSS
   */
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  /**
   * Inicia o polling
   */
  function startPolling() {
    fetchNotifications(); // Busca imediatamente
    pollingTimer = setInterval(fetchNotifications, POLLING_INTERVAL);
  }

  /**
   * Para o polling
   */
  function stopPolling() {
    if (pollingTimer) {
      clearInterval(pollingTimer);
      pollingTimer = null;
    }
  }

  // Event listeners
  document.addEventListener('DOMContentLoaded', function() {
    // Inicia polling quando a página carrega
    startPolling();

    // Para polling quando a aba fica inativa (economiza recursos)
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) {
        stopPolling();
      } else {
        startPolling();
      }
    });

    // Marcar todas como lidas
    markAllReadBtn.addEventListener('click', function() {
      markAllAsRead();
    });

    // Event delegation para botões das notificações
    notificationList.addEventListener('click', function(e) {
      const markReadBtn = e.target.closest('.mark-read-btn');
      const deleteBtn = e.target.closest('.delete-btn');

      if (markReadBtn) {
        const id = markReadBtn.dataset.id;
        markAsRead(id);
      }

      if (deleteBtn) {
        const id = deleteBtn.dataset.id;
        if (confirm('Deseja remover esta notificação?')) {
          deleteNotification(id);
        }
      }
    });
  });

  // Para polling quando a janela fecha
  window.addEventListener('beforeunload', function() {
    stopPolling();
  });
})();
</script>

<style>
.notification-item {
  transition: background-color 0.2s;
  cursor: pointer;
}

.notification-item:hover {
  background-color: var(--app-surface-2);
}

.notification-item.unread {
  background-color: var(--app-accent-soft);
}

.notification-item.read {
  opacity: 0.7;
}

#notificationBadge {
  font-size: 0.65rem;
  padding: 0.25em 0.4em;
}
</style>

<!-- Theme toggle -->
<script>
(function(){
  var btn = document.getElementById('themeToggle');
  if(!btn) return;
  function syncIcon(){
    var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var i = btn.querySelector('i');
    if(i) i.className = dark ? 'bi bi-sun' : 'bi bi-moon-stars';
  }
  syncIcon();
  btn.addEventListener('click', function(){
    var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var next = dark ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', next);
    try { localStorage.setItem('theme', next); } catch(e){}
    syncIcon();
  });
})();
</script>

@stack('scripts')
</body>
</html>
