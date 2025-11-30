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
    <div class="col-md-2">
      <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#syncModal">
        <i class="bi bi-arrow-repeat"></i> Sincronizar
      </button>
    </div>
    <div class="col-md-2 text-end">
      <a href="{{ route('panel.products.create') }}" class="btn btn-success w-100">
        <i class="bi bi-plus-circle"></i> Adicionar
      </a>
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
                      $platformInfo = $integration->getPlatformInfo();
                    @endphp
                    <span class="chip chip-{{ $platformInfo['color'] }}" style="font-size: 0.75rem;" title="Status: {{ $integration->status }}">
                      {{ $platformInfo['icon'] }} {{ $platformInfo['name'] }}
                    </span>
                  @endforeach
                </div>
              @endif
              @if($p->ml_listing)
                <div class="mt-1">
                  @php
                    $mlStatus = $p->ml_listing->status ?? 'draft';
                    $mlBadgeColor = match($mlStatus) {
                      'queued' => 'info',
                      'processing' => 'warning',
                      'active' => 'success',
                      'failed' => 'danger',
                      'draft' => 'secondary',
                      default => 'secondary'
                    };
                    $mlIcon = match($mlStatus) {
                      'queued' => '<i class="bi bi-clock-history"></i>',
                      'processing' => '<i class="bi bi-arrow-repeat"></i>',
                      'active' => '<i class="bi bi-check-circle"></i>',
                      'failed' => '<i class="bi bi-x-circle"></i>',
                      'draft' => '<i class="bi bi-file-earmark"></i>',
                      default => '<i class="bi bi-question-circle"></i>'
                    };
                  @endphp
                  <span class="chip chip-{{ $mlBadgeColor }}" style="font-size: 0.75rem;" title="Status ML: {{ $mlStatus }}">
                    {!! $mlIcon !!} ML: {{ ucfirst($mlStatus) }}
                  </span>
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

<!-- Modal de Sincronização -->
<div class="modal fade" id="syncModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
          <i class="bi bi-arrow-repeat"></i> Sincronizar Produtos
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info">
          <i class="bi bi-info-circle"></i>
          <strong>Como funciona:</strong>
          <p class="mb-0 mt-2">
            Esta função irá buscar todos os produtos publicados nas plataformas conectadas
            e importá-los para sua base local.
          </p>
        </div>

        <h6 class="mb-3">Selecione a plataforma:</h6>

        <div class="d-grid gap-2">
          <button type="button" class="btn btn-outline-primary btn-lg" onclick="syncPlatform('mercado_livre')">
            <i class="bi bi-shop"></i> Mercado Livre
          </button>

          <button type="button" class="btn btn-outline-secondary btn-lg" disabled>
            <i class="bi bi-cart"></i> Shopee
            <small class="d-block">Em breve</small>
          </button>

          <button type="button" class="btn btn-outline-secondary btn-lg" disabled>
            <i class="bi bi-bag"></i> Shopify
            <small class="d-block">Em breve</small>
          </button>
        </div>

        <!-- Área de progresso -->
        <div id="syncProgress" class="mt-3" style="display:none;">
          <div class="alert alert-warning">
            <div class="d-flex align-items-center">
              <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Carregando...</span>
              </div>
              <div>
                <strong>Sincronizando...</strong>
                <p class="mb-0 small" id="syncMessage">Aguarde enquanto buscamos os produtos...</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Resultado -->
        <div id="syncResult" class="mt-3" style="display:none;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Fechar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
async function syncPlatform(platform) {
  const progressDiv = document.getElementById('syncProgress');
  const resultDiv = document.getElementById('syncResult');
  const messageSpan = document.getElementById('syncMessage');

  // Mostra loading
  progressDiv.style.display = 'block';
  resultDiv.style.display = 'none';

  try {
    const response = await fetch(`{{ route('panel.products.sync') }}?platform=${platform}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      }
    });

    const data = await response.json();

    // Esconde loading
    progressDiv.style.display = 'none';

    if (data.success) {
      const stats = data.stats;
      resultDiv.innerHTML = `
        <div class="alert alert-success">
          <i class="bi bi-check-circle"></i>
          <strong>Sincronização concluída!</strong>
          <div class="mt-2">
            <div><strong>Total processado:</strong> ${stats.total}</div>
            <div class="text-success"><strong>Criados:</strong> ${stats.created}</div>
            <div class="text-info"><strong>Atualizados:</strong> ${stats.updated}</div>
            <div class="text-muted"><strong>Ignorados:</strong> ${stats.skipped}</div>
            ${stats.errors && stats.errors.length > 0 ? `
              <div class="mt-2 text-danger">
                <strong>Erros:</strong>
                <ul class="mb-0">
                  ${stats.errors.map(e => `<li>${e}</li>`).join('')}
                </ul>
              </div>
            ` : ''}
          </div>
        </div>
      `;
      resultDiv.style.display = 'block';

      // Recarrega a página após 2 segundos
      setTimeout(() => {
        window.location.reload();
      }, 2000);

    } else {
      resultDiv.innerHTML = `
        <div class="alert alert-danger">
          <i class="bi bi-x-circle"></i>
          <strong>Erro na sincronização</strong>
          <p class="mb-0 mt-2">${data.message}</p>
        </div>
      `;
      resultDiv.style.display = 'block';
    }

  } catch (error) {
    console.error('Erro:', error);
    progressDiv.style.display = 'none';
    resultDiv.innerHTML = `
      <div class="alert alert-danger">
        <i class="bi bi-x-circle"></i>
        <strong>Erro ao sincronizar</strong>
        <p class="mb-0 mt-2">${error.message}</p>
      </div>
    `;
    resultDiv.style.display = 'block';
  }
}
</script>

@endsection
