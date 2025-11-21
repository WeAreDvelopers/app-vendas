@extends('layouts.panel')
@section('title','Dashboard')
@section('page-title','Dashboard')
@section('page-subtitle','Visão geral do pipeline')

@section('content')
<!-- Card de Status Mercado Livre -->
<div class="notion-card mb-3" id="ml-status-card">
  <div class="d-flex align-items-center justify-content-between">
    <div>
      <div class="fw-semibold mb-1">Mercado Livre</div>
      <div class="text-muted small" id="ml-status-text">Verificando conexão...</div>
    </div>
    <div>
      <a href="{{ route('panel.mercado-livre.connect') }}" class="btn btn-primary" id="ml-connect-btn" style="display:none;">
        Conectar Conta
      </a>
      <a href="{{ route('panel.mercado-livre.disconnect') }}" class="btn btn-outline-danger" id="ml-disconnect-btn" style="display:none;">
        Desconectar
      </a>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-3">
    <div class="notion-card">
      <div class="muted mb-1">Importações</div>
      <div class="fs-3 fw-bold">{{ number_format($stats['imports']) }}</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="notion-card">
      <div class="muted mb-1">Produtos</div>
      <div class="fs-3 fw-bold">{{ number_format($stats['products']) }}</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="notion-card">
      <div class="muted mb-1">Publicações</div>
      <div class="fs-3 fw-bold">{{ number_format($stats['listings']) }}</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="notion-card">
      <div class="muted mb-1">Pedidos</div>
      <div class="fs-3 fw-bold">{{ number_format($stats['orders']) }}</div>
    </div>
  </div>
</div>

<script>
// Verifica status da conexão com Mercado Livre
fetch('{{ route("panel.mercado-livre.status") }}')
  .then(r => r.json())
  .then(data => {
    const statusText = document.getElementById('ml-status-text');
    const connectBtn = document.getElementById('ml-connect-btn');
    const disconnectBtn = document.getElementById('ml-disconnect-btn');
    const card = document.getElementById('ml-status-card');

    if (data.connected) {
      statusText.innerHTML = `Conectado como <strong>${data.ml_nickname || 'Usuário'}</strong>`;
      card.classList.add('border-success');
      disconnectBtn.style.display = 'inline-block';
    } else {
      statusText.textContent = 'Não conectado';
      card.classList.add('border-warning');
      connectBtn.style.display = 'inline-block';
    }
  })
  .catch(() => {
    document.getElementById('ml-status-text').textContent = 'Erro ao verificar status';
  });
</script>

<div class="notion-card mt-4">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div class="fw-semibold">Pedidos recentes</div>
    <a href="{{ route('panel.orders.index') }}" class="btn btn-sm btn-outline-secondary">Ver todos</a>
  </div>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>ID</th><th>ML Order</th><th>Status</th><th>Data</th></tr></thead>
      <tbody>
        @forelse($recentOrders as $o)
          <tr>
            <td>#{{ $o->id }}</td>
            <td>{{ $o->ml_order_id }}</td>
            <td><span class="chip">{{ $o->status }}</span></td>
            <td>{{ \Illuminate\Support\Carbon::parse($o->created_at)->format('d/m/Y H:i') }}</td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-muted">Sem pedidos ainda.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
