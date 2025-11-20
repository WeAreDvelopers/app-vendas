@extends('layouts.panel')
@section('title','Importação #'.$imp->id)
@section('page-title','Importação #'.$imp->id)
@section('page-subtitle', $imp->supplier_name.' — '.$imp->status)

@section('content')
<div class="notion-card mb-3">
  <div class="row g-3">
    <div class="col-md-3">
      <div class="muted">Arquivo</div>
      <div class="fw-semibold text-truncate">{{ $imp->source_file }}</div>
    </div>
    <div class="col-md-2">
      <div class="muted">Tipo</div>
      <div class="chip">{{ $imp->source_type }}</div>
    </div>
    <div class="col-md-3">
      <div class="muted">Progresso</div>
      <div>{{ $imp->processed_rows }}/{{ $imp->total_rows }}</div>
    </div>
    <div class="col-md-2">
      <div class="muted">Status</div>
      <div class="chip">{{ $imp->status }}</div>
    </div>
    <div class="col-md-2">
      <div class="muted">Criado</div>
      <div>{{ \Illuminate\Support\Carbon::parse($imp->created_at)->format('d/m/Y H:i') }}</div>
    </div>
  </div>

  @if($errorsCount > 0)
    <div class="alert alert-warning mt-3 d-flex align-items-center justify-content-between">
      <div>
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>{{ $errorsCount }}</strong> linha(s) com erro foram ignoradas durante a importação.
      </div>
      <div class="btn-group btn-group-sm">
        <a href="{{ route('panel.imports.errors', $imp->id) }}" class="btn btn-outline-warning">
          <i class="bi bi-list-ul"></i> Ver Erros
        </a>
        <a href="{{ route('panel.imports.errors.export', $imp->id) }}" class="btn btn-outline-warning">
          <i class="bi bi-download"></i> Exportar CSV
        </a>
      </div>
    </div>
  @endif
</div>

<div class="notion-card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="fw-semibold">Linhas importadas ({{ $rows->total() }})</div>
    <div class="d-flex gap-2 align-items-center">
      <!-- Campo de Busca -->
      <form method="GET" action="{{ route('panel.imports.show', $imp->id) }}" class="d-flex gap-2">
        <div class="input-group input-group-sm" style="width: 350px;">
          <input type="text"
                 name="q"
                 class="form-control"
                 placeholder="Buscar por SKU, EAN, nome, marca ou status..."
                 value="{{ $search ?? '' }}"
                 autocomplete="off">
          <button type="submit" class="btn btn-outline-secondary">
            <i class="bi bi-search"></i>
          </button>
          @if($search ?? false)
            <a href="{{ route('panel.imports.show', $imp->id) }}" class="btn btn-outline-secondary" title="Limpar busca">
              <i class="bi bi-x-circle"></i>
            </a>
          @endif
        </div>
      </form>

      <div class="btn-group">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAll">Selecionar Todos</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">Desmarcar Todos</button>
        <button type="button" class="btn btn-sm btn-primary" id="processSelected" disabled>
          <i class="bi bi-magic"></i> Processar com IA
        </button>
      </div>
    </div>
  </div>

  @if($search ?? false)
    <div class="alert alert-info alert-dismissible fade show mb-3">
      <i class="bi bi-filter-circle"></i>
      Mostrando resultados para: <strong>{{ $search }}</strong>
      ({{ $rows->total() }} produto(s) encontrado(s))
      <a href="{{ route('panel.imports.show', $imp->id) }}" class="btn btn-sm btn-outline-primary ms-2">
        Limpar filtro
      </a>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <form id="processProductsForm" method="POST" action="{{ route('panel.imports.process', $imp->id) }}">
    @csrf
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th width="40">
              <input type="checkbox" class="form-check-input" id="checkAll">
            </th>
            <th>ID</th>
            <th>SKU</th>
            <th>EAN</th>
            <th>Nome</th>
            <th>Marca</th>
            <th>Preço Custo</th>
            <th>Preço Venda</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
            <tr>
              <td>
                <input type="checkbox" class="form-check-input product-checkbox" name="product_ids[]" value="{{ $r->id }}">
              </td>
              <td>#{{ $r->id }}</td>
              <td>{{ $r->sku }}</td>
              <td>{{ $r->ean }}</td>
              <td>{{ $r->name }}</td>
              <td>{{ $r->brand }}</td>
              <td>{{ $r->cost_price ? 'R$ '.number_format($r->cost_price, 2, ',', '.') : '-' }}</td>
              <td>{{ $r->sale_price ? 'R$ '.number_format($r->sale_price, 2, ',', '.') : '-' }}</td>
              <td><span class="chip chip-sm">{{ $r->status }}</span></td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-muted text-center py-4">
                @if($search ?? false)
                  <i class="bi bi-search"></i>
                  Nenhum produto encontrado com "{{ $search }}".
                  <div class="mt-2">
                    <a href="{{ route('panel.imports.show', $imp->id) }}" class="btn btn-sm btn-outline-primary">
                      Ver todos os produtos
                    </a>
                  </div>
                @else
                  Nenhum produto importado.
                @endif
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </form>
  {{ $rows->links() }}
</div>

@push('scripts')
<script>
// Select all checkbox
document.getElementById('checkAll').addEventListener('change', function() {
  const checkboxes = document.querySelectorAll('.product-checkbox');
  checkboxes.forEach(cb => cb.checked = this.checked);
  updateProcessButton();
});

// Select all button
document.getElementById('selectAll').addEventListener('click', function() {
  document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = true);
  document.getElementById('checkAll').checked = true;
  updateProcessButton();
});

// Deselect all button
document.getElementById('deselectAll').addEventListener('click', function() {
  document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = false);
  document.getElementById('checkAll').checked = false;
  updateProcessButton();
});

// Update process button state
function updateProcessButton() {
  const selectedCount = document.querySelectorAll('.product-checkbox:checked').length;
  const btn = document.getElementById('processSelected');
  btn.disabled = selectedCount === 0;
  btn.innerHTML = selectedCount > 0
    ? `<i class="bi bi-magic"></i> Processar ${selectedCount} produto(s) com IA`
    : '<i class="bi bi-magic"></i> Processar com IA';
}

// Listen to individual checkbox changes
document.querySelectorAll('.product-checkbox').forEach(cb => {
  cb.addEventListener('change', updateProcessButton);
});

// Process selected products
document.getElementById('processSelected').addEventListener('click', function() {
  if (confirm('Deseja processar os produtos selecionados com IA? Isso irá gerar descrições e buscar imagens.')) {
    document.getElementById('processProductsForm').submit();
  }
});
</script>
@endpush
@endsection
