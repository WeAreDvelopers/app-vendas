@extends('layouts.panel')
@section('title','Editar Fornecedor')
@section('page-title','Editar Fornecedor')
@section('page-subtitle', $supplier->name)

@section('content')
<div class="row">
  <div class="col-lg-6">
    <div class="notion-card">
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      <form method="POST" action="{{ route('panel.suppliers.update', $supplier) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
          <label class="form-label fw-semibold">Nome do Fornecedor</label>
          <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                 value="{{ old('name', $supplier->name) }}" required>
          @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Código Único</label>
          <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                 value="{{ old('code', $supplier->code) }}" required>
          @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Descrição</label>
          <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                    rows="3">{{ old('description', $supplier->description) }}</textarea>
          @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="active" id="active"
                   value="1" {{ old('active', $supplier->active) ? 'checked' : '' }}>
            <label class="form-check-label" for="active">Fornecedor ativo</label>
          </div>
        </div>

        <div class="d-flex gap-2 mb-3">
          <button type="submit" class="btn btn-dark">Salvar Alterações</button>
          <a href="{{ route('panel.suppliers.mapping', $supplier) }}" class="btn btn-outline-primary">
            <i class="bi bi-diagram-3"></i> Configurar Mapeamento
          </a>
          <a href="{{ route('panel.suppliers.index') }}" class="btn btn-outline-secondary">Voltar</a>
        </div>
      </form>

      <hr class="my-4">

      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="fw-semibold text-danger">Zona de Perigo</div>
          <small class="text-muted">Remover fornecedor e todos os dados relacionados</small>
        </div>
        <form method="POST" action="{{ route('panel.suppliers.destroy', $supplier) }}"
              onsubmit="return confirm('Tem certeza? Esta ação não pode ser desfeita!')">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-sm btn-outline-danger">Excluir Fornecedor</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="notion-card mb-3">
      <div class="fw-semibold mb-2">Informações</div>
      <table class="table table-sm">
        <tr>
          <td class="text-muted">ID:</td>
          <td>#{{ $supplier->id }}</td>
        </tr>
        <tr>
          <td class="text-muted">Criado em:</td>
          <td>{{ $supplier->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
          <td class="text-muted">Atualizado em:</td>
          <td>{{ $supplier->updated_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
          <td class="text-muted">Mapeamento:</td>
          <td>
            @if($supplier->mapping)
              <span class="badge bg-success">Configurado</span>
              <a href="{{ route('panel.suppliers.mapping', $supplier) }}" class="small">Editar</a>
            @else
              <span class="badge bg-warning">Não configurado</span>
              <a href="{{ route('panel.suppliers.mapping', $supplier) }}" class="small">Configurar agora</a>
            @endif
          </td>
        </tr>
      </table>
    </div>
  </div>
</div>
@endsection
