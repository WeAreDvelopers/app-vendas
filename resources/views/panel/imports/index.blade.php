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
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="fw-semibold">Histórico</div>
      </div>
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
                <td><a href="{{ route('panel.imports.show',$imp->id) }}" class="btn btn-sm btn-outline-secondary">Detalhes</a></td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-muted">Nenhuma importação ainda.</td></tr>
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
@endsection
