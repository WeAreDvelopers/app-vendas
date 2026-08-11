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
          <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#mlCredentialsModal">
            <i class="bi bi-key"></i> Configurar API
          </button>

          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#mlSettingsModal">
            <i class="bi bi-sliders"></i> Opções
          </button>

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

        <div class="d-flex gap-2 mb-3">
          <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#mlCredentialsModal">
            <i class="bi bi-key"></i> Configurar API
          </button>

          <a href="{{ route('panel.integrations.ml.connect') }}" class="btn btn-primary">
            <i class="bi bi-link-45deg"></i> Conectar Mercado Livre
          </a>
        </div>

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

  <!-- Google Drive -->
  <div class="col-md-6">
    <div class="notion-card h-100">
      <div class="d-flex align-items-center mb-3">
        <div class="me-3" style="font-size: 2.5rem;">☁️</div>
        <div class="flex-grow-1">
          <h5 class="mb-0">Google Drive</h5>
          <small class="text-muted">Armazenamento e imagens de produtos</small>
        </div>
        @if($driveConnected)
          <span class="badge bg-success">Conectado</span>
        @else
          <span class="badge bg-secondary">Desconectado</span>
        @endif
      </div>

      @if($driveConnected)
        <div class="alert alert-success mb-3">
          <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2"></i>
            <div class="flex-grow-1">
              <strong>Conta conectada:</strong> {{ $driveIntegration->credentials['email'] ?? 'Usuário Google' }}
              <br>
              <small class="text-muted">
                Conectado em {{ $driveIntegration->connected_at->format('d/m/Y H:i') }}
              </small>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <form method="POST" action="{{ route('panel.integrations.drive.disconnect') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm"
                    onclick="return confirm('Tem certeza que deseja desconectar o Google Drive?')">
              <i class="bi bi-plug"></i> Desconectar
            </button>
          </form>

          <a href="{{ route('panel.integrations.drive.connect') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-repeat"></i> Reconectar
          </a>
        </div>

        <hr class="my-3">

        <div class="small">
          <div class="mb-2">
            <strong>Funcionalidades disponíveis:</strong>
          </div>
          <ul class="mb-0">
            <li>✅ Selecionar imagens do Drive para produtos</li>
            <li>✅ Acesso seguro aos arquivos</li>
            <li>✅ Organização centralizada</li>
          </ul>
        </div>
      @else
        <p class="text-muted mb-3">
          Conecte sua conta do Google Drive para selecionar imagens diretamente do seu armazenamento na nuvem.
        </p>

        <a href="{{ route('panel.integrations.drive.connect') }}" class="btn btn-primary">
          <i class="bi bi-link-45deg"></i> Conectar Google Drive
        </a>

        <hr class="my-3">

        <div class="small">
          <div class="mb-2">
            <strong>O que você poderá fazer:</strong>
          </div>
          <ul class="mb-0">
            <li>Escolher imagens do Drive para seus produtos</li>
            <li>Organizar fotos em pastas</li>
            <li>Acesso rápido e seguro aos arquivos</li>
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

<!-- Modal: Configurar Credenciais do Mercado Livre -->
<div class="modal fade" id="mlCredentialsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">
          <i class="bi bi-key me-2"></i>Credenciais de API - Mercado Livre
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form method="POST" action="{{ route('panel.integrations.ml.save-credentials') }}">
        @csrf

        <div class="modal-body">
          <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Como obter suas credenciais:</strong>
            <ol class="mb-0 mt-2 ps-3 small">
              <li>Acesse <a href="https://developers.mercadolivre.com.br/devcenter" target="_blank">Mercado Livre Developers</a></li>
              <li>Faça login com sua conta</li>
              <li>Vá em "Minhas aplicações" e crie uma nova aplicação</li>
              <li>Copie o <strong>App ID</strong> e <strong>Secret Key</strong></li>
              <li>Configure as URLs abaixo no painel do ML</li>
            </ol>
          </div>

          <div class="row g-3">
            <!-- App ID -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">
                App ID (Client ID) <span class="text-danger">*</span>
              </label>
              <input type="text" name="ml_app_id" id="mlAppIdInput" class="form-control @error('ml_app_id') is-invalid @enderror"
                     value="{{ old('ml_app_id', $mlAppId ?? '') }}"
                     placeholder="1234567890123456"
                     required>
              @error('ml_app_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="text-muted">ID da sua aplicação no Mercado Livre</small>
            </div>

            <!-- Secret Key -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">
                Secret Key <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <input type="password" id="mlSecretKeyInput" name="ml_secret_key"
                       class="form-control @error('ml_secret_key') is-invalid @enderror"
                       value="{{ old('ml_secret_key', $mlSecretKey ?? '') }}"
                       placeholder="••••••••••••••••••••"
                       required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('mlSecretKeyInput')">
                  <i class="bi bi-eye"></i>
                </button>
                @error('ml_secret_key')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <small class="text-muted">Chave secreta da sua aplicação</small>
            </div>
          </div>

          <hr class="my-3">

          <h6 class="mb-3">
            <i class="bi bi-link-45deg me-2"></i>URLs para Configurar no Mercado Livre
          </h6>

          <div class="row g-3">
            <!-- Redirect URL -->
            <div class="col-md-12">
              <label class="form-label fw-semibold">
                URL de Redirect (Callback OAuth)
              </label>
              <div class="input-group">
                <input type="text" class="form-control" id="redirectUrl" value="{{ url('/mercado-livre/callback') }}" readonly>
                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('{{ url('/mercado-livre/callback') }}')">
                  <i class="bi bi-clipboard"></i> Copiar
                </button>
              </div>
              <small class="text-muted">
                <i class="bi bi-info-circle"></i>
                Cole esta URL em <strong>Redirect URI</strong> no painel do ML
              </small>
            </div>

            <!-- Webhook URL -->
            <div class="col-md-12">
              <label class="form-label fw-semibold">
                URL de Notificações (Webhook)
              </label>
              <div class="input-group">
                <input type="text" class="form-control" id="webhookUrl" value="{{ url('/api/webhooks/mercado-livre') }}" readonly>
                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('{{ url('/api/webhooks/mercado-livre') }}')">
                  <i class="bi bi-clipboard"></i> Copiar
                </button>
              </div>
              <small class="text-muted">
                <i class="bi bi-info-circle"></i>
                Cole esta URL em <strong>Notifications URL</strong> no painel do ML para receber vendas e perguntas
              </small>
            </div>
          </div>

          @if($mlAppId)
            <div class="alert alert-success mt-3 mb-0">
              <i class="bi bi-check-circle me-2"></i>
              <strong>Credenciais já configuradas!</strong>
              <p class="mb-0 small mt-1">
                Você pode atualizar suas credenciais preenchendo os campos acima novamente.
              </p>
            </div>
          @endif
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle"></i> Cancelar
          </button>
          <button type="submit" class="btn btn-success">
            <i class="bi bi-save"></i> Salvar Credenciais
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Opções do Mercado Livre -->
<div class="modal fade" id="mlSettingsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-sliders me-2"></i>Opções - Mercado Livre</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <!-- Impressão de etiqueta -->
        <form method="POST" action="{{ route('panel.integrations.ml.settings') }}">
          @csrf
          <h6 class="mb-3"><i class="bi bi-printer me-1"></i> Impressão de etiqueta</h6>

          <div class="form-check form-switch mb-3">
            <input type="hidden" name="auto_print" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="autoPrintSwitch"
                   name="auto_print" value="1" @checked($mlAutoPrint)>
            <label class="form-check-label" for="autoPrintSwitch">
              Imprimir etiqueta automaticamente ao receber uma venda nova
            </label>
          </div>

          <div class="mb-3">
            <label class="form-label">Conteúdo da etiqueta</label>
            <select name="label_mode" class="form-select">
              <option value="auto" @selected($mlLabelMode==='auto')>Etiqueta real do Mercado Envios (recomendado)</option>
              <option value="simple" @selected($mlLabelMode==='simple')>Etiqueta simples (nº do pedido)</option>
            </select>
            <div class="form-text">
              No modo real, se a etiqueta ainda não estiver disponível no ML, o sistema usa a simples como fallback.
            </div>
          </div>

          <div class="text-end">
            <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-save"></i> Salvar opções</button>
          </div>
        </form>

        <hr class="my-4">

        <!-- Token do agente de impressão -->
        <h6 class="mb-2"><i class="bi bi-key me-1"></i> Token do agente de impressão</h6>
        <p class="small text-muted">
          Use este token no programa agente que imprime as etiquetas. Cada empresa tem o seu — o agente só recebe as etiquetas desta empresa.
        </p>

        <div class="input-group mb-2">
          <input type="text" class="form-control" id="printAgentTokenInput" readonly
                 value="{{ $printAgentToken ?? '' }}" placeholder="Nenhum token gerado ainda">
          <button class="btn btn-outline-secondary" type="button"
                  onclick="copyToClipboard('{{ $printAgentToken ?? '' }}')" @disabled(empty($printAgentToken))>
            <i class="bi bi-clipboard"></i> Copiar
          </button>
        </div>

        <form method="POST" action="{{ route('panel.integrations.ml.print-token') }}">
          @csrf
          <button type="submit" class="btn btn-outline-primary btn-sm"
                  onclick="return confirm('Gerar um novo token? O token anterior deixará de funcionar imediatamente.')">
            <i class="bi bi-arrow-repeat"></i> {{ $printAgentToken ? 'Gerar novo token' : 'Gerar token' }}
          </button>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
// Abre o modal automaticamente se houver erros de validação
document.addEventListener('DOMContentLoaded', function() {
  @if($errors->has('ml_app_id') || $errors->has('ml_secret_key'))
    const mlModal = new bootstrap.Modal(document.getElementById('mlCredentialsModal'));
    mlModal.show();
  @endif
});

// Toggle password visibility
function togglePassword(fieldId) {
  const field = document.getElementById(fieldId);
  const button = field.nextElementSibling;
  const icon = button.querySelector('i');

  if (field.type === 'password') {
    field.type = 'text';
    icon.classList.remove('bi-eye');
    icon.classList.add('bi-eye-slash');
  } else {
    field.type = 'password';
    icon.classList.remove('bi-eye-slash');
    icon.classList.add('bi-eye');
  }
}

// Copy to clipboard
function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    // Show temporary success message
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;

    btn.innerHTML = '<i class="bi bi-check"></i> Copiado!';
    btn.classList.add('btn-success');
    btn.classList.remove('btn-outline-secondary');

    setTimeout(() => {
      btn.innerHTML = originalHtml;
      btn.classList.remove('btn-success');
      btn.classList.add('btn-outline-secondary');
    }, 2000);
  }).catch(err => {
    console.error('Erro ao copiar:', err);
    alert('Erro ao copiar para área de transferência');
  });
}
</script>
@endpush

@endsection
