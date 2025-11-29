@extends('layouts.panel')
@section('title', 'Minhas Empresas')
@section('page-title', 'Gerenciar Empresas')
@section('page-subtitle', 'Visualize e gerencie suas empresas')

@section('content')
<div class="notion-card mb-3">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-1">Suas Empresas</h5>
      <small class="text-muted">Você tem acesso a {{ $companies->count() }} empresa(s)</small>
    </div>
    <a href="{{ route('panel.companies.create') }}" class="btn btn-primary">
      <i class="bi bi-plus-circle"></i> Nova Empresa
    </a>
  </div>
</div>

<div class="row g-3">
  @forelse($companies as $company)
    <div class="col-md-6 col-lg-4">
      <div class="notion-card h-100 {{ $company->id == auth()->user()->current_company_id ? 'border-primary border-2' : '' }}">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div>
            <h5 class="mb-1">
              <i class="bi bi-building"></i> {{ $company->name }}
            </h5>
            @if($company->id == auth()->user()->current_company_id)
              <span class="badge bg-primary">Empresa Atual</span>
            @else
              <span class="badge bg-secondary">Disponível</span>
            @endif
          </div>
          @if($company->pivot->is_admin)
            <span class="badge bg-success" title="Você é administrador">
              <i class="bi bi-shield-check"></i> Admin
            </span>
          @endif
        </div>

        <div class="mb-3">
          @if($company->document)
            <div class="small mb-1">
              <i class="bi bi-card-text text-muted me-1"></i>
              <strong>CNPJ/CPF:</strong> {{ $company->document }}
            </div>
          @endif

          @if($company->email)
            <div class="small mb-1">
              <i class="bi bi-envelope text-muted me-1"></i>
              <strong>Email:</strong> {{ $company->email }}
            </div>
          @endif

          @if($company->phone)
            <div class="small mb-1">
              <i class="bi bi-telephone text-muted me-1"></i>
              <strong>Telefone:</strong> {{ $company->phone }}
            </div>
          @endif

          <div class="small mt-2">
            <i class="bi bi-calendar text-muted me-1"></i>
            <strong>Criada em:</strong> {{ $company->created_at->format('d/m/Y') }}
          </div>
        </div>

        <div class="d-flex gap-2 mt-auto">
          @if($company->id != auth()->user()->current_company_id)
            <form method="POST" action="{{ route('panel.companies.switch') }}" class="flex-grow-1">
              @csrf
              <input type="hidden" name="company_id" value="{{ $company->id }}">
              <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                <i class="bi bi-arrow-repeat"></i> Trocar
              </button>
            </form>
          @endif

          @if($company->pivot->is_admin)
            <a href="{{ route('panel.companies.edit', $company->id) }}" class="btn btn-sm btn-outline-secondary">
              <i class="bi bi-gear"></i> Configurar
            </a>
          @endif
        </div>
      </div>
    </div>
  @empty
    <div class="col-12">
      <div class="notion-card text-center py-5">
        <i class="bi bi-building text-muted" style="font-size: 3rem;"></i>
        <h5 class="mt-3">Nenhuma empresa encontrada</h5>
        <p class="text-muted">Crie sua primeira empresa para começar</p>
        <a href="{{ route('panel.companies.create') }}" class="btn btn-primary">
          <i class="bi bi-plus-circle"></i> Criar Empresa
        </a>
      </div>
    </div>
  @endforelse
</div>

<!-- Informações -->
<div class="notion-card mt-4">
  <h6 class="mb-3">
    <i class="bi bi-info-circle me-2"></i>Sobre as Empresas
  </h6>

  <div class="row g-3 small">
    <div class="col-md-4">
      <div class="d-flex align-items-start">
        <i class="bi bi-shield-lock text-primary me-2 fs-5"></i>
        <div>
          <strong>Isolamento Total</strong>
          <p class="text-muted mb-0">
            Cada empresa possui seus próprios dados: produtos, importações, integrações e pedidos completamente isolados.
          </p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="d-flex align-items-start">
        <i class="bi bi-people text-success me-2 fs-5"></i>
        <div>
          <strong>Múltiplos Usuários</strong>
          <p class="text-muted mb-0">
            Uma empresa pode ter vários usuários com diferentes níveis de acesso (admin ou colaborador).
          </p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="d-flex align-items-start">
        <i class="bi bi-arrow-left-right text-warning me-2 fs-5"></i>
        <div>
          <strong>Troca Rápida</strong>
          <p class="text-muted mb-0">
            Alterne entre empresas instantaneamente usando o seletor no topo da página.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
