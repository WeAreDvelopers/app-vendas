@extends('layouts.panel')
@section('title', 'Nova Empresa')
@section('page-title', 'Criar Nova Empresa')
@section('page-subtitle', 'Adicione uma nova empresa ao sistema')

@section('content')
<div class="row">
  <div class="col-md-8 mx-auto">
    <div class="notion-card">
      <form method="POST" action="{{ route('panel.companies.store') }}">
        @csrf

        <div class="mb-3">
          <label for="name" class="form-label">Nome da Empresa *</label>
          <input type="text"
                 class="form-control @error('name') is-invalid @enderror"
                 id="name"
                 name="name"
                 value="{{ old('name') }}"
                 placeholder="Ex: Minha Loja Online"
                 required>
          @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
          <small class="form-text text-muted">
            Nome que identificará sua empresa no sistema
          </small>
        </div>

        <div class="mb-3">
          <label for="document" class="form-label">CNPJ/CPF</label>
          <input type="text"
                 class="form-control @error('document') is-invalid @enderror"
                 id="document"
                 name="document"
                 value="{{ old('document') }}"
                 placeholder="00.000.000/0000-00">
          @error('document')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
          <small class="form-text text-muted">
            Documento fiscal da empresa (opcional)
          </small>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email"
                   class="form-control @error('email') is-invalid @enderror"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
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
                   value="{{ old('phone') }}"
                   placeholder="(11) 98765-4321">
            @error('phone')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <div class="alert alert-info">
          <i class="bi bi-info-circle me-2"></i>
          <strong>Importante:</strong> Você será automaticamente definido como administrador desta empresa e ela será selecionada após a criação.
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Criar Empresa
          </button>
          <a href="{{ route('panel.companies.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> Cancelar
          </a>
        </div>
      </form>
    </div>

    <!-- Benefícios -->
    <div class="notion-card mt-3">
      <h6 class="mb-3">
        <i class="bi bi-star me-2"></i>Ao criar uma nova empresa você poderá:
      </h6>
      <ul class="mb-0">
        <li>Gerenciar produtos e vendas separadamente</li>
        <li>Conectar diferentes contas do Mercado Livre</li>
        <li>Ter equipes e acessos independentes</li>
        <li>Organizar melhor seus negócios</li>
      </ul>
    </div>
  </div>
</div>
@endsection
