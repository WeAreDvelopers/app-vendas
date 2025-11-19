@extends('layouts.panel')
@section('title','Erros de Importação #'.$imp->id)
@section('page-title','Erros de Importação #'.$imp->id)
@section('page-subtitle', $imp->supplier_name)

@section('content')
<div class="mb-3">
  <a href="{{ route('panel.imports.show', $imp->id) }}" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Voltar para Importação
  </a>
  <a href="{{ route('panel.imports.errors.export', $imp->id) }}" class="btn btn-outline-primary btn-sm">
    <i class="bi bi-download"></i> Exportar CSV
  </a>
</div>

<div class="notion-card">
  <div class="fw-semibold mb-3">
    <i class="bi bi-exclamation-triangle text-warning me-2"></i>
    Linhas com Erro ({{ $errors->total() }})
  </div>

  @if($errors->isEmpty())
    <div class="text-muted">Nenhum erro encontrado nesta importação.</div>
  @else
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>Linha</th>
            <th>Tipo de Erro</th>
            <th>Mensagem</th>
            <th>Dados da Linha</th>
            <th>Data</th>
          </tr>
        </thead>
        <tbody>
          @foreach($errors as $error)
            <tr>
              <td>
                <span class="badge bg-secondary">#{{ $error->row_number }}</span>
              </td>
              <td>
                @php
                  $badgeClass = match($error->error_type) {
                    'missing_identifier' => 'bg-danger',
                    'missing_name' => 'bg-warning',
                    default => 'bg-secondary'
                  };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $error->error_type }}</span>
              </td>
              <td>{{ $error->error_message }}</td>
              <td>
                @php
                  $rowData = json_decode($error->row_data, true);
                @endphp
                @if($rowData)
                  <small class="text-muted">
                    @foreach($rowData as $key => $value)
                      @if($value)
                        <span class="d-block"><strong>{{ $key }}:</strong> {{ $value }}</span>
                      @endif
                    @endforeach
                  </small>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
              <td>
                <small>{{ \Illuminate\Support\Carbon::parse($error->created_at)->format('d/m H:i') }}</small>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{ $errors->links() }}
  @endif
</div>

<div class="mt-3">
  <div class="notion-card">
    <div class="fw-semibold mb-2">Tipos de Erro Comuns</div>
    <ul class="small mb-0">
      <li><strong>missing_identifier:</strong> Linha sem SKU ou EAN (obrigatório para identificar produto)</li>
      <li><strong>missing_name:</strong> Linha sem nome do produto (obrigatório)</li>
    </ul>
  </div>
</div>
@endsection
