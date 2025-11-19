@extends('layouts.panel')
@section('title','Fornecedores')
@section('page-title','Fornecedores')
@section('page-subtitle','Gerencie fornecedores e seus mapeamentos de planilha')

@section('content')
<div class="row g-3">
  <div class="col-12">
    <div class="notion-card">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="fw-semibold">Todos os fornecedores</div>
        <a href="{{ route('panel.suppliers.create') }}" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-circle me-1"></i> Novo Fornecedor
        </a>
      </div>

      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nome</th>
              <th>Código</th>
              <th>Status</th>
              <th>Importações</th>
              <th>Mapeamento</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($suppliers as $supplier)
              <tr>
                <td>#{{ $supplier->id }}</td>
                <td>
                  <div class="fw-semibold">{{ $supplier->name }}</div>
                  @if($supplier->description)
                    <div class="small text-muted">{{ Str::limit($supplier->description, 50) }}</div>
                  @endif
                </td>
                <td><span class="chip">{{ $supplier->code }}</span></td>
                <td>
                  @if($supplier->active)
                    <span class="badge bg-success">Ativo</span>
                  @else
                    <span class="badge bg-secondary">Inativo</span>
                  @endif
                </td>
                <td>{{ $supplier->imports_count ?? 0 }}</td>
                <td>
                  @if($supplier->mapping)
                    <span class="badge bg-primary">Configurado</span>
                  @else
                    <span class="badge bg-warning">Não configurado</span>
                  @endif
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a href="{{ route('panel.suppliers.edit', $supplier) }}" class="btn btn-outline-secondary">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <a href="{{ route('panel.suppliers.mapping', $supplier) }}" class="btn btn-outline-primary">
                      <i class="bi bi-diagram-3"></i> Mapeamento
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-muted">Nenhum fornecedor cadastrado.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $suppliers->links() }}
    </div>
  </div>
</div>
@endsection
