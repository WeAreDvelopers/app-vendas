@extends('layouts.panel')
@section('title','Mapeamento de Colunas')
@section('page-title','Mapeamento de Colunas')
@section('page-subtitle', 'Fornecedor: ' . $supplier->name)

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="notion-card">
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Como funciona:</strong> Informe os nomes das colunas da planilha deste fornecedor.
        Você pode informar múltiplos valores separados por vírgula (ex: <code>sku, codigo, cod</code>).
        O sistema tentará localizar essas colunas automaticamente durante a importação.
      </div>

      <form method="POST" action="{{ route('panel.suppliers.mapping.update', $supplier) }}">
        @csrf
        @method('PUT')

        @php
          $currentMappings = $supplier->mapping->column_mappings ?? [];
        @endphp

        @foreach($defaultFields as $field => $config)
          <div class="mb-4">
            <label class="form-label fw-semibold">
              {{ $config['label'] }}
              <span class="text-muted small">({{ $field }})</span>
            </label>
            <input type="text" name="mappings[{{ $field }}]"
                   class="form-control"
                   value="{{ old('mappings.'.$field, isset($currentMappings[$field]) ? implode(', ', $currentMappings[$field]) : '') }}"
                   placeholder="Ex: {{ implode(', ', $config['examples']) }}">
            <small class="text-muted">
              Exemplos comuns: <code>{{ implode(', ', $config['examples']) }}</code>
            </small>
          </div>
        @endforeach

        <hr class="my-4">

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-dark">
            <i class="bi bi-save me-1"></i> Salvar Mapeamento
          </button>
          <a href="{{ route('panel.suppliers.edit', $supplier) }}" class="btn btn-outline-secondary">Voltar</a>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="notion-card mb-3">
      <div class="fw-semibold mb-2">Dicas de Mapeamento</div>
      <ul class="small mb-0">
        <li class="mb-2">Use nomes exatos das colunas da planilha</li>
        <li class="mb-2">Não diferencia maiúsculas/minúsculas</li>
        <li class="mb-2">Separe alternativas por vírgula</li>
        <li class="mb-2">Campos vazios usarão detecção automática</li>
        <li>Salve e teste com uma importação pequena primeiro</li>
      </ul>
    </div>

    @if($supplier->mapping)
      <div class="notion-card">
        <div class="fw-semibold mb-2 text-success">
          <i class="bi bi-check-circle me-1"></i> Mapeamento Ativo
        </div>
        <p class="small text-muted mb-2">
          Este fornecedor já possui um mapeamento configurado.
          Campos configurados: <strong>{{ count($supplier->mapping->column_mappings) }}</strong>
        </p>
        <small class="text-muted">
          Última atualização: {{ $supplier->mapping->updated_at->format('d/m/Y H:i') }}
        </small>
      </div>
    @endif
  </div>
</div>
@endsection
