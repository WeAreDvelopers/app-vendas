@extends('layouts.panel')
@section('title','Produtos')
@section('page-title','Produtos')
@section('page-subtitle','Catálogo normalizado pronto para publicação')

@section('content')
<div class="notion-card">
  <form class="row g-2 mb-3" method="get">
    <div class="col-md-6">
      <input type="search" class="form-control" name="q" value="{{ $search }}" placeholder="Buscar por nome, SKU ou EAN">
    </div>
    <div class="col-md-2">
      <button class="btn btn-dark w-100">Buscar</button>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>#</th><th>SKU</th><th>EAN</th><th>Nome</th><th>Preço</th><th>Estoque</th><th>Status</th><th>Ações</th></tr></thead>
      <tbody>
        @forelse($products as $p)
          <tr>
            <td>{{ $p->id }}</td>
            <td class="fw-semibold">{{ $p->sku }}</td>
            <td>{{ $p->ean ?? '-' }}</td>
            <td>
              <div class="fw-semibold">{{ $p->name }}</div>
              @if($p->description)
                <small class="text-success">
                  <i class="bi bi-magic"></i> Com descrição IA
                </small>
              @endif
              @if($p->integrations && $p->integrations->count() > 0)
                <div class="mt-1">
                  @foreach($p->integrations as $integration)
                    @php
                      $info = (new \App\Models\ProductIntegration)->getPlatformInfo();
                      $platformData = $info;
                      // Buscar info específica da plataforma atual
                      foreach ([
                        'mercado_livre' => ['name' => 'Mercado Livre', 'color' => 'warning', 'icon' => '🛒'],
                        'shopee' => ['name' => 'Shopee', 'color' => 'danger', 'icon' => '🛍️'],
                        'amazon' => ['name' => 'Amazon', 'color' => 'dark', 'icon' => '📦'],
                        'magalu' => ['name' => 'Magazine Luiza', 'color' => 'primary', 'icon' => '🏪'],
                        'americanas' => ['name' => 'Americanas', 'color' => 'danger', 'icon' => '🏬'],
                      ] as $key => $val) {
                        if ($integration->platform === $key) {
                          $platformData = $val;
                          break;
                        }
                      }
                    @endphp
                    <span class="chip chip-{{ $platformData['color'] }}" style="font-size: 0.75rem;">
                      {{ $platformData['icon'] }} {{ $platformData['name'] }}
                    </span>
                  @endforeach
                </div>
              @endif
            </td>
            <td>{{ $p->price ? 'R$ ' . number_format($p->price, 2, ',', '.') : '-' }}</td>
            <td>{{ $p->stock }}</td>
            <td>
              <span class="chip chip-{{ $p->status === 'ready' ? 'success' : 'secondary' }}">
                {{ $p->status }}
              </span>
            </td>
            <td>
              <div class="btn-group btn-group-sm">
                <a href="{{ route('panel.products.show', $p->id) }}" class="btn btn-outline-primary">
                  <i class="bi bi-eye"></i>
                </a>
                <a href="{{ route('panel.products.edit', $p->id) }}" class="btn btn-outline-secondary">
                  <i class="bi bi-pencil"></i>
                </a>
                <button type="button" class="btn btn-outline-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteModal{{ $p->id }}"
                        title="Excluir produto">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-muted">Nenhum produto encontrado.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $products->links() }}
</div>

<!-- Modais de Confirmação de Exclusão -->
@foreach($products as $p)
<div class="modal fade" id="deleteModal{{ $p->id }}" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">
          <i class="bi bi-exclamation-triangle"></i> Excluir Produto
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle"></i>
          <strong>Atenção!</strong> Esta ação não pode ser desfeita.
        </div>

        <p class="mb-3">Você está prestes a excluir permanentemente:</p>

        <div class="bg-light p-3 rounded mb-3">
          <div class="fw-bold">{{ $p->name }}</div>
          <small class="text-muted">SKU: {{ $p->sku }}</small>
        </div>

        <p class="mb-2"><strong>O que será excluído:</strong></p>
        <ul>
          <li>Dados do produto no banco de dados</li>
          <li>Todas as imagens do storage</li>
          <li>Imagem de referência (se existir)</li>
        </ul>

        <p class="text-danger fw-bold mb-0">
          <i class="bi bi-exclamation-circle"></i>
          Tem certeza que deseja continuar?
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Cancelar
        </button>
        <form method="POST" action="{{ route('panel.products.destroy', $p->id) }}" class="d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger">
            <i class="bi bi-trash"></i> Sim, Excluir
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
@endforeach

@endsection
