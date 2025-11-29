@extends('layouts.panel')
@section('title', 'Editar Empresa')
@section('page-title', 'Configurar Empresa')
@section('page-subtitle', $company->name)

@section('content')
<div class="row">
  <div class="col-md-8 mx-auto">
    <div class="notion-card">
      <form method="POST" action="{{ route('panel.companies.update', $company->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
          <label for="name" class="form-label">Nome da Empresa *</label>
          <input type="text"
                 class="form-control @error('name') is-invalid @enderror"
                 id="name"
                 name="name"
                 value="{{ old('name', $company->name) }}"
                 required>
          @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <label for="document" class="form-label">CNPJ/CPF</label>
          <input type="text"
                 class="form-control @error('document') is-invalid @enderror"
                 id="document"
                 name="document"
                 value="{{ old('document', $company->document) }}"
                 placeholder="00.000.000/0000-00">
          @error('document')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email"
                   class="form-control @error('email') is-invalid @enderror"
                   id="email"
                   name="email"
                   value="{{ old('email', $company->email) }}"
                   placeholder="contato@empresa.com">
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label for="phone" class="form-label">Telefone</label>
            <input type="text"
                   class="form-control @error('phone') is-invalid @enderror"
                   id="phone"
                   name="phone"
                   value="{{ old('phone', $company->phone) }}"
                   placeholder="(11) 98765-4321">
            @error('phone')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Salvar Alterações
          </button>
          <a href="{{ route('panel.companies.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
          </a>
        </div>
      </form>
    </div>

    <!-- Estatísticas -->
    <div class="notion-card mt-3">
      <h6 class="mb-3">
        <i class="bi bi-graph-up me-2"></i>Estatísticas da Empresa
      </h6>

      <div class="row g-3">
        <div class="col-md-4">
          <div class="text-center">
            <div class="fs-3 fw-bold text-primary">{{ $company->imports()->count() }}</div>
            <small class="text-muted">Importações</small>
          </div>
        </div>

        <div class="col-md-4">
          <div class="text-center">
            <div class="fs-3 fw-bold text-success">{{ $company->products()->count() }}</div>
            <small class="text-muted">Produtos</small>
          </div>
        </div>

        <div class="col-md-4">
          <div class="text-center">
            <div class="fs-3 fw-bold text-info">{{ $company->suppliers()->count() }}</div>
            <small class="text-muted">Fornecedores</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Integrações -->
    <div class="notion-card mt-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">
          <i class="bi bi-plug me-2"></i>Integrações Ativas
        </h6>
        <a href="{{ route('panel.integrations.index') }}" class="btn btn-sm btn-outline-primary">
          <i class="bi bi-gear"></i> Gerenciar
        </a>
      </div>

      @php
        $mlIntegration = $company->integrations()->where('integration_type', 'mercado_livre')->first();
      @endphp

      @if($mlIntegration && $mlIntegration->isConnected())
        <div class="alert alert-success mb-0">
          <i class="bi bi-check-circle me-2"></i>
          <strong>Mercado Livre conectado:</strong> {{ $mlIntegration->credentials['nickname'] ?? 'Conta ML' }}
        </div>
      @else
        <div class="alert alert-warning mb-0">
          <i class="bi bi-exclamation-triangle me-2"></i>
          Nenhuma integração ativa. <a href="{{ route('panel.integrations.index') }}">Conecte agora</a>
        </div>
      @endif
    </div>

    <!-- Usuários -->
    <div class="notion-card mt-3">
      <h6 class="mb-3">
        <i class="bi bi-people me-2"></i>Usuários com Acesso ({{ $company->users()->count() }})
      </h6>

      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr>
              <th>Nome</th>
              <th>Email</th>
              <th>Permissão</th>
            </tr>
          </thead>
          <tbody>
            @foreach($company->users as $user)
              <tr>
                <td>
                  {{ $user->name }}
                  @if($user->id == auth()->id())
                    <span class="badge bg-primary badge-sm">Você</span>
                  @endif
                </td>
                <td>{{ $user->email }}</td>
                <td>
                  @if($user->pivot->is_admin)
                    <span class="badge bg-success">Administrador</span>
                  @else
                    <span class="badge bg-secondary">Colaborador</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
