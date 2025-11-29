@extends('layouts.panel')
@section('title', 'Integrações')
@section('page-title', 'Integrações')
@section('page-subtitle', 'Conecte suas contas e plataformas')

@section('content')
<div class="row g-3">
  <!-- Mercado Livre -->
  <div class="col-md-6">
    <div class="notion-card h-100">
      <div class="d-flex align-items-center mb-3">
        <div class="me-3" style="font-size: 2.5rem;">🛒</div>
        <div class="flex-grow-1">
          <h5 class="mb-0">Mercado Livre</h5>
          <small class="text-muted">Marketplace líder da América Latina</small>
        </div>
        @if($mlConnected)
          <span class="badge bg-success">Conectado</span>
        @else
          <span class="badge bg-secondary">Desconectado</span>
        @endif
      </div>

      @if($mlConnected)
        <div class="alert alert-success mb-3">
          <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2"></i>
            <div class="flex-grow-1">
              <strong>Conta conectada:</strong> {{ $mlIntegration->credentials['nickname'] ?? 'Usuário ML' }}
              <br>
              <small class="text-muted">
                Conectado em {{ $mlIntegration->connected_at->format('d/m/Y H:i') }}
                @if($mlIntegration->expires_at)
                  • Expira em {{ $mlIntegration->expires_at->format('d/m/Y H:i') }}
                @endif
              </small>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <form method="POST" action="{{ route('panel.integrations.ml.disconnect') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm"
                    onclick="return confirm('Tem certeza que deseja desconectar o Mercado Livre?')">
              <i class="bi bi-plug"></i> Desconectar
            </button>
          </form>

          <a href="{{ route('panel.integrations.ml.connect') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-repeat"></i> Reconectar
          </a>
        </div>

        <hr class="my-3">

        <div class="small">
          <div class="mb-2">
            <strong>Funcionalidades disponíveis:</strong>
          </div>
          <ul class="mb-0">
            <li>✅ Publicação automática de produtos</li>
            <li>✅ Sincronização de estoque</li>
            <li>✅ Recebimento de pedidos</li>
            <li>✅ Atualização de preços</li>
          </ul>
        </div>
      @else
        <p class="text-muted mb-3">
          Conecte sua conta do Mercado Livre para publicar e gerenciar seus produtos automaticamente.
        </p>

        <a href="{{ route('panel.integrations.ml.connect') }}" class="btn btn-primary">
          <i class="bi bi-link-45deg"></i> Conectar Mercado Livre
        </a>

        <hr class="my-3">

        <div class="small">
          <div class="mb-2">
            <strong>O que você poderá fazer:</strong>
          </div>
          <ul class="mb-0">
            <li>Publicar produtos automaticamente</li>
            <li>Sincronizar estoque em tempo real</li>
            <li>Receber pedidos automaticamente</li>
            <li>Gerar etiquetas de envio</li>
          </ul>
        </div>
      @endif
    </div>
  </div>

  <!-- Shopee (Em breve) -->
  <div class="col-md-6">
    <div class="notion-card h-100 opacity-50">
      <div class="d-flex align-items-center mb-3">
        <div class="me-3" style="font-size: 2.5rem;">🛍️</div>
        <div class="flex-grow-1">
          <h5 class="mb-0">Shopee</h5>
          <small class="text-muted">E-commerce em crescimento</small>
        </div>
        <span class="badge bg-warning text-dark">Em breve</span>
      </div>

      <p class="text-muted mb-3">
        Integração com Shopee estará disponível em breve.
      </p>

      <button class="btn btn-secondary" disabled>
        <i class="bi bi-link-45deg"></i> Em desenvolvimento
      </button>
    </div>
  </div>

  <!-- Amazon (Em breve) -->
  <div class="col-md-6">
    <div class="notion-card h-100 opacity-50">
      <div class="d-flex align-items-center mb-3">
        <div class="me-3" style="font-size: 2.5rem;">📦</div>
        <div class="flex-grow-1">
          <h5 class="mb-0">Amazon</h5>
          <small class="text-muted">Maior marketplace global</small>
        </div>
        <span class="badge bg-warning text-dark">Em breve</span>
      </div>

      <p class="text-muted mb-3">
        Integração com Amazon estará disponível em breve.
      </p>

      <button class="btn btn-secondary" disabled>
        <i class="bi bi-link-45deg"></i> Em desenvolvimento
      </button>
    </div>
  </div>

  <!-- Outros Marketplaces -->
  <div class="col-md-6">
    <div class="notion-card h-100">
      <div class="d-flex align-items-center mb-3">
        <div class="me-3" style="font-size: 2.5rem;">🔌</div>
        <div class="flex-grow-1">
          <h5 class="mb-0">Outras Integrações</h5>
          <small class="text-muted">Expanda suas vendas</small>
        </div>
      </div>

      <p class="text-muted mb-3">
        Precisa de uma integração específica? Entre em contato conosco!
      </p>

      <a href="mailto:suporte@exemplo.com" class="btn btn-outline-primary">
        <i class="bi bi-envelope"></i> Solicitar Integração
      </a>
    </div>
  </div>
</div>

<!-- Informações adicionais -->
<div class="notion-card mt-3">
  <h5 class="mb-3">
    <i class="bi bi-info-circle me-2"></i>Sobre as Integrações
  </h5>

  <div class="row g-3">
    <div class="col-md-4">
      <div class="d-flex align-items-start">
        <i class="bi bi-shield-check text-success me-2 fs-4"></i>
        <div>
          <strong>Seguro e Confiável</strong>
          <p class="small text-muted mb-0">
            Suas credenciais são criptografadas e armazenadas com segurança.
          </p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="d-flex align-items-start">
        <i class="bi bi-clock-history text-primary me-2 fs-4"></i>
        <div>
          <strong>Sincronização Automática</strong>
          <p class="small text-muted mb-0">
            Produtos, estoque e pedidos sincronizados em tempo real.
          </p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="d-flex align-items-start">
        <i class="bi bi-gear text-warning me-2 fs-4"></i>
        <div>
          <strong>Configuração Simples</strong>
          <p class="small text-muted mb-0">
            Conecte suas contas em poucos cliques.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
