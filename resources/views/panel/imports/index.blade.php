@extends('layouts.panel')
@section('title','Importações')
@section('page-title','Importações')
@section('page-subtitle','Envie planilhas (XLSX/CSV) ou PDF do fornecedor')

@section('content')
<div class="row g-3">
  <div class="col-lg-4">
    <div class="notion-card">
      <div class="fw-semibold mb-2">Nova importação</div>
      <form method="post" action="{{ route('panel.imports.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-2">
          <label class="form-label">Fornecedor</label>
          <select name="supplier_id" class="form-select" id="supplierSelect">
            <option value="">Selecione ou digite novo...</option>
            @foreach($suppliers as $supplier)
              <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-2" id="customSupplierField" style="display:none;">
          <label class="form-label">Nome do fornecedor (novo)</label>
          <input type="text" name="supplier_name" class="form-control" placeholder="Ex.: ACME Distribuidora">
          <small class="text-muted">
            <a href="{{ route('panel.suppliers.create') }}" target="_blank">Cadastrar fornecedor com mapeamento</a>
          </small>
        </div>
        <div class="mb-3">
          <label class="form-label">Arquivo (xlsx, csv ou pdf)</label>
          <input type="file" name="file" class="form-control" accept=".xlsx,.csv,.pdf" required>
        </div>
        <button class="btn btn-dark">Enviar para a fila</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="notion-card">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="fw-semibold">Histórico</div>

        <!-- Campo de Busca -->
        <form method="GET" action="{{ route('panel.imports.index') }}" class="d-flex gap-2">
          <div class="input-group input-group-sm" style="width: 300px;">
            <input type="text"
                   name="q"
                   class="form-control"
                   placeholder="Buscar por fornecedor, ID, tipo ou status..."
                   value="{{ $search ?? '' }}"
                   autocomplete="off">
            <button type="submit" class="btn btn-outline-secondary">
              <i class="bi bi-search"></i>
            </button>
            @if($search ?? false)
              <a href="{{ route('panel.imports.index') }}" class="btn btn-outline-secondary" title="Limpar busca">
                <i class="bi bi-x-circle"></i>
              </a>
            @endif
          </div>
        </form>
      </div>

      @if($search ?? false)
        <div class="alert alert-info alert-dismissible fade show mb-3">
          <i class="bi bi-filter-circle"></i>
          Mostrando resultados para: <strong>{{ $search }}</strong>
          <a href="{{ route('panel.imports.index') }}" class="btn btn-sm btn-outline-primary ms-2">
            Limpar filtro
          </a>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif

      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th>ID</th><th>Fornecedor</th><th>Tipo</th><th>Status</th><th>Linhas</th><th>Quando</th><th></th></tr></thead>
          <tbody>
            @forelse($imports as $imp)
              <tr>
                <td>#{{ $imp->id }}</td>
                <td>{{ $imp->supplier_name }}</td>
                <td><span class="chip">{{ $imp->source_type }}</span></td>
                <td><span class="chip">{{ $imp->status }}</span></td>
                <td>{{ $imp->processed_rows }}/{{ $imp->total_rows }}</td>
                <td>{{ \Illuminate\Support\Carbon::parse($imp->created_at)->format('d/m/Y H:i') }}</td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a href="{{ route('panel.imports.show',$imp->id) }}" class="btn btn-outline-secondary">
                      <i class="bi bi-eye"></i>
                    </a>
                    <button type="button" class="btn btn-outline-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteModal{{ $imp->id }}"
                            title="Excluir importação">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-muted text-center py-4">
                  @if($search ?? false)
                    <i class="bi bi-search"></i>
                    Nenhuma importação encontrada com "{{ $search }}".
                    <div class="mt-2">
                      <a href="{{ route('panel.imports.index') }}" class="btn btn-sm btn-outline-primary">
                        Ver todas as importações
                      </a>
                    </div>
                  @else
                    Nenhuma importação ainda.
                  @endif
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $imports->links() }}
    </div>
  </div>
</div>

@push('scripts')
<script>
document.getElementById('supplierSelect').addEventListener('change', function() {
  const customField = document.getElementById('customSupplierField');
  const customInput = document.querySelector('[name="supplier_name"]');

  if (this.value === '') {
    customField.style.display = 'block';
    customInput.required = true;
  } else {
    customField.style.display = 'none';
    customInput.required = false;
    customInput.value = '';
  }
});
</script>
@endpush

<!-- Modais de Confirmação de Exclusão -->
@foreach($imports as $imp)
<div class="modal fade" id="deleteModal{{ $imp->id }}" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">
          <i class="bi bi-exclamation-triangle"></i> Excluir Importação
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
          <div class="fw-bold">Importação #{{ $imp->id }}</div>
          <small class="text-muted">Fornecedor: {{ $imp->supplier_name }}</small><br>
          <small class="text-muted">Tipo: {{ $imp->source_type }}</small><br>
          <small class="text-muted">Linhas: {{ $imp->processed_rows }}/{{ $imp->total_rows }}</small>
        </div>

        <p class="mb-2"><strong>O que será excluído:</strong></p>
        <ul>
          <li>Registro da importação</li>
          <li>Arquivo de origem ({{ $imp->source_type }})</li>
          <li>Todos os {{ $imp->processed_rows }} itens importados</li>
          <li>Todos os erros registrados</li>
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
        <form method="POST" action="{{ route('panel.imports.destroy', $imp->id) }}" class="d-inline">
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
