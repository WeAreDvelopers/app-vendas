@extends('layouts.panel')
@section('title', 'Preparar Anúncio - Mercado Livre')
@section('page-title', 'Publicar no Mercado Livre')
@section('page-subtitle', $product->name)

@section('content')
<div class="row g-3">
  <!-- Coluna Principal -->
  <div class="col-lg-8">
    <!-- Score de Qualidade -->
    <div class="notion-card mb-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
          <i class="bi bi-star-fill text-warning"></i> Análise de Qualidade
        </h5>
        <div>
          <span class="badge bg-{{ $validation['quality_level'] === 'excellent' ? 'success' : ($validation['quality_level'] === 'good' ? 'primary' : ($validation['quality_level'] === 'fair' ? 'warning' : 'danger')) }} fs-5">
            {{ $validation['percentage'] }}%
          </span>
        </div>
      </div>

      <!-- Barra de progresso -->
      <div class="progress mb-3" style="height: 25px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated
                    bg-{{ $validation['quality_level'] === 'excellent' ? 'success' : ($validation['quality_level'] === 'good' ? 'primary' : ($validation['quality_level'] === 'fair' ? 'warning' : 'danger')) }}"
             role="progressbar"
             style="width: {{ $validation['percentage'] }}%">
          {{ $validation['percentage'] }}%
        </div>
      </div>

      <!-- Mensagem de status -->
      @if($validation['percentage'] >= 80)
        <div class="alert alert-success">
          <i class="bi bi-check-circle"></i>
          <strong>Excelente!</strong> Seu anúncio está otimizado para alcançar o máximo de visibilidade no Mercado Livre.
        </div>
      @elseif($validation['percentage'] >= 60)
        <div class="alert alert-info">
          <i class="bi bi-info-circle"></i>
          <strong>Bom!</strong> Seu anúncio tem qualidade adequada. Preencha os campos opcionais abaixo para melhorar ainda mais.
        </div>
      @else
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle"></i>
          <strong>Atenção!</strong> Preencha os campos obrigatórios abaixo para poder publicar seu anúncio.
        </div>
      @endif

      <!-- Erros e avisos -->
      @if(count($validation['errors']) > 0)
        <div class="alert alert-danger">
          <strong><i class="bi bi-x-circle"></i> Erros que impedem a publicação:</strong>
          <ul class="mb-0 mt-2">
            @foreach($validation['errors'] as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @if(count($validation['warnings']) > 0)
        <div class="alert alert-warning">
          <strong><i class="bi bi-exclamation-triangle"></i> Recomendações para melhorar a qualidade:</strong>
          <ul class="mb-0 mt-2">
            @foreach($validation['warnings'] as $warning)
              <li>{{ $warning }}</li>
            @endforeach
          </ul>
        </div>
      @endif
    </div>

    <!-- Formulário de Configuração -->
    <form method="POST" action="{{ route('panel.mercado-livre.save-draft', $product->id) }}">
      @csrf

      <!-- Informações Básicas -->
      <div class="notion-card mb-3">
        <h5 class="mb-3">
          <i class="bi bi-info-circle text-primary"></i> Informações Básicas
        </h5>

        <div class="row g-3">
          <!-- Título -->
          <div class="col-12">
            <label class="form-label">
              Título do Anúncio <span class="text-danger">*</span>
              <small class="text-muted">(máx. 60 caracteres)</small>
            </label>
            <input type="text"
                   name="title"
                   class="form-control"
                   maxlength="60"
                   value="{{ $listing->title ?? substr($product->name, 0, 60) }}"
                   id="mlTitle">
            <small class="text-muted">
              <span id="titleCount">{{ strlen($listing->title ?? $product->name) }}</span>/60 caracteres
            </small>
          </div>

          <!-- Categoria -->
          <div class="col-md-6">
            <label class="form-label">
              Categoria <span class="text-danger">*</span>
            </label>
            <select name="category_id" class="form-select" id="mlCategory" required>
              <option value="">Selecione uma categoria</option>
              @foreach($suggestedCategories as $cat)
                <option value="{{ $cat['id'] }}"
                        {{ ($listing->category_id ?? '') === $cat['id'] ? 'selected' : '' }}>
                  {{ $cat['name'] }}
                </option>
              @endforeach
            </select>
            <small class="text-muted">
              Categorias sugeridas pela IA do Mercado Livre
            </small>
          </div>

          <!-- Condição -->
          <div class="col-md-6">
            <label class="form-label">
              Condição <span class="text-danger">*</span>
            </label>
            <select name="condition" class="form-select" required>
              <option value="new" {{ ($listing->condition ?? 'new') === 'new' ? 'selected' : '' }}>Novo</option>
              <option value="used" {{ ($listing->condition ?? '') === 'used' ? 'selected' : '' }}>Usado</option>
            </select>
          </div>

          <!-- Preço -->
          <div class="col-md-6">
            <label class="form-label">
              Preço <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="number"
                     name="price"
                     class="form-control"
                     step="0.01"
                     min="0.01"
                     value="{{ $listing->price ?? $product->price }}"
                     required>
            </div>
          </div>

          <!-- Quantidade -->
          <div class="col-md-6">
            <label class="form-label">
              Quantidade Disponível <span class="text-danger">*</span>
            </label>
            <input type="number"
                   name="available_quantity"
                   class="form-control"
                   min="1"
                   value="{{ $listing->available_quantity ?? $product->stock ?? 1 }}"
                   required>
          </div>

          <!-- Tipo de Anúncio -->
          <div class="col-12">
            <label class="form-label">
              Tipo de Anúncio <span class="text-danger">*</span>
            </label>
            <select name="listing_type_id" class="form-select" required>
              <option value="gold_special" {{ ($listing->listing_type_id ?? 'gold_special') === 'gold_special' ? 'selected' : '' }}>
                Clássico (Gratuito) - Comissão maior
              </option>
              <option value="gold_pro" {{ ($listing->listing_type_id ?? '') === 'gold_pro' ? 'selected' : '' }}>
                Premium (Pago) - Melhor posicionamento
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Descrição -->
      <div class="notion-card mb-3">
        <h5 class="mb-3">
          <i class="bi bi-file-text text-primary"></i> Descrição e Mídia
        </h5>

        <div class="mb-3">
          <label class="form-label">Descrição do Produto</label>
          <textarea name="plain_text_description"
                    class="form-control"
                    rows="8">{{ $listing->plain_text_description ?? $product->description }}</textarea>
          <small class="text-muted">
            O Mercado Livre aceita apenas texto puro (sem formatação HTML/Markdown).
          </small>
        </div>

        <div class="mb-3">
          <label class="form-label">
            Vídeo do YouTube <small class="text-muted">(opcional)</small>
          </label>
          <input type="text"
                 name="video_id"
                 class="form-control"
                 placeholder="Ex: dQw4w9WgXcQ"
                 value="{{ $listing->video_id ?? '' }}">
          <small class="text-muted">
            Cole apenas o ID do vídeo (após watch?v=)
          </small>
        </div>
      </div>

      <!-- Envio e Logística -->
      <div class="notion-card mb-3">
        <h5 class="mb-3">
          <i class="bi bi-truck text-primary"></i> Envio e Logística
        </h5>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Modo de Envio</label>
            <select name="shipping_mode" class="form-select">
              <option value="me2" {{ ($listing->shipping_mode ?? 'me2') === 'me2' ? 'selected' : '' }}>
                Mercado Envios (Recomendado)
              </option>
              <option value="custom" {{ ($listing->shipping_mode ?? '') === 'custom' ? 'selected' : '' }}>
                Envio por conta própria
              </option>
            </select>
          </div>

          <div class="col-md-6">
            <div class="form-check mt-4">
              <input class="form-check-input"
                     type="checkbox"
                     name="free_shipping"
                     value="1"
                     {{ ($listing->free_shipping ?? false) ? 'checked' : '' }}>
              <label class="form-check-label">
                Frete Grátis (melhor conversão)
              </label>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label">Retirada Local</label>
            <select name="shipping_local_pick_up" class="form-select">
              <option value="true" {{ ($listing->shipping_local_pick_up ?? 'true') === 'true' ? 'selected' : '' }}>
                Permitir retirada local
              </option>
              <option value="false" {{ ($listing->shipping_local_pick_up ?? '') === 'false' ? 'selected' : '' }}>
                Não permitir retirada
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Garantia -->
      <div class="notion-card mb-3">
        <h5 class="mb-3">
          <i class="bi bi-shield-check text-primary"></i> Garantia (Opcional)
        </h5>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Tipo de Garantia</label>
            <input type="text"
                   name="warranty_type"
                   class="form-control"
                   placeholder="Ex: Garantia do fabricante"
                   value="{{ $listing->warranty_type ?? '' }}">
          </div>

          <div class="col-md-6">
            <label class="form-label">Tempo de Garantia</label>
            <input type="text"
                   name="warranty_time"
                   class="form-control"
                   placeholder="Ex: 90 dias"
                   value="{{ $listing->warranty_time ?? '' }}">
          </div>
        </div>
      </div>

      <!-- Botões de Ação -->
      <div class="d-flex gap-2">
        <a href="{{ route('panel.products.show', $product->id) }}" class="btn btn-secondary">
          <i class="bi bi-arrow-left"></i> Voltar
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save"></i> Salvar Rascunho
        </button>
        @if($validation['can_publish'])
          <button type="button" class="btn btn-success" onclick="publishNow()">
            <i class="bi bi-upload"></i> Publicar Agora
          </button>
        @else
          <button type="button" class="btn btn-success" disabled title="Corrija os erros antes de publicar">
            <i class="bi bi-upload"></i> Publicar Agora
          </button>
        @endif
      </div>
    </form>
  </div>

  <!-- Coluna Lateral - Preview -->
  <div class="col-lg-4">
    <!-- Preview do Anúncio -->
    <div class="notion-card mb-3 sticky-top" style="top: 20px;">
      <h6 class="mb-3">
        <i class="bi bi-eye"></i> Preview do Anúncio
      </h6>

      <!-- Imagens -->
      @if($images->count() > 0)
        <div id="carouselPreview" class="carousel slide mb-3" data-bs-ride="carousel">
          <div class="carousel-inner">
            @foreach($images as $index => $image)
              <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                <img src="{{ $image->path }}" class="d-block w-100 rounded" alt="Imagem {{ $index + 1 }}">
              </div>
            @endforeach
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#carouselPreview" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#carouselPreview" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
          </button>
        </div>
        <small class="text-muted">{{ $images->count() }} imagem(ns)</small>
      @else
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle"></i>
          Nenhuma imagem disponível
        </div>
      @endif

      <hr>

      <!-- Informações -->
      <div class="mb-2">
        <small class="text-muted">Título:</small>
        <div class="fw-semibold" id="previewTitle">{{ $product->name }}</div>
      </div>

      <div class="mb-2">
        <small class="text-muted">Preço:</small>
        <div class="fw-bold text-success fs-4">R$ {{ number_format($product->price ?? 0, 2, ',', '.') }}</div>
      </div>

      @if($product->brand)
        <div class="mb-2">
          <small class="text-muted">Marca:</small>
          <div class="fw-semibold">{{ $product->brand }}</div>
        </div>
      @endif

      @if($product->ean)
        <div class="mb-2">
          <small class="text-muted">EAN:</small>
          <div class="fw-semibold">{{ $product->ean }}</div>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
// Contador de caracteres do título
document.getElementById('mlTitle').addEventListener('input', function() {
  const count = this.value.length;
  document.getElementById('titleCount').textContent = count;
  document.getElementById('previewTitle').textContent = this.value;
});

// Função para publicar
function publishNow() {
  if (confirm('Deseja publicar este anúncio no Mercado Livre agora?')) {
    // Cria formulário temporário para POST
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route('panel.mercado-livre.publish', $product->id) }}';

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);

    document.body.appendChild(form);
    form.submit();
  }
}
</script>
@endpush
