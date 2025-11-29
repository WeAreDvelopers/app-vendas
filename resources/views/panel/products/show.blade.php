@extends('layouts.panel')
@section('title', $product->name)
@section('page-title', $product->name)
@section('page-subtitle', 'SKU: ' . $product->sku)

@section('content')
<div class="row g-3">
  <!-- Coluna Principal -->
  <div class="col-lg-8">
    <!-- Informações Básicas -->
    <div class="notion-card mb-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Informações do Produto</h5>
        <a href="{{ route('panel.products.edit', $product->id) }}" class="btn btn-sm btn-outline-primary">
          <i class="bi bi-pencil"></i> Editar
        </a>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <div class="muted">Nome</div>
          <div class="fw-semibold">{{ $product->name }}</div>
        </div>
        <div class="col-md-3">
          <div class="muted">SKU</div>
          <div class="fw-semibold">{{ $product->sku }}</div>
        </div>
        <div class="col-md-3">
          <div class="muted">EAN</div>
          <div class="fw-semibold">{{ $product->ean ?? 'N/A' }}</div>
        </div>
        <div class="col-md-4">
          <div class="muted">Marca</div>
          <div class="fw-semibold">{{ $product->brand ?? 'N/A' }}</div>
        </div>
        <div class="col-md-4">
          <div class="muted">Preço de Custo</div>
          <div class="fw-semibold">{{ $product->cost_price ? 'R$ ' . number_format($product->cost_price, 2, ',', '.') : 'N/A' }}</div>
        </div>
        <div class="col-md-4">
          <div class="muted">Preço de Venda</div>
          <div class="fw-semibold">{{ $product->price ? 'R$ ' . number_format($product->price, 2, ',', '.') : 'N/A' }}</div>
        </div>
        <div class="col-md-4">
          <div class="muted">Estoque</div>
          <div class="fw-semibold">{{ $product->stock }} unidades</div>
        </div>
        <div class="col-md-4">
          <div class="muted">Status</div>
          <div>
            <span class="chip chip-{{ $product->status === 'ready' ? 'success' : 'secondary' }}">
              {{ $product->status }}
            </span>
          </div>
        </div>
        <div class="col-md-4">
          <div class="muted">Criado em</div>
          <div class="fw-semibold">{{ \Carbon\Carbon::parse($product->created_at)->format('d/m/Y H:i') }}</div>
        </div>
      </div>
    </div>

    <!-- Imagem de Referência para Busca por Similaridade -->
    <div class="notion-card mb-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
          <i class="bi bi-bullseye text-primary"></i> Imagem de Referência
        </h5>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#referenceImageModal">
          <i class="bi bi-upload"></i> {{ $product->reference_image_path ? 'Alterar' : 'Definir' }}
        </button>
      </div>

      @if($product->reference_image_path)
      <div class="row align-items-center">
        <div class="col-md-4">
          <img src="{{ $product->reference_image_path }}"
               alt="Imagem de Referência"
               class="img-fluid rounded"
               style="width: 100%; height: 150px; object-fit: cover;">
        </div>
        <div class="col-md-8">
          <p class="mb-2">
            <strong>Busca por Similaridade Ativa</strong>
            <span class="badge bg-success ms-2">ON</span>
          </p>
          <p class="text-muted mb-2">
            <small>
              <i class="bi bi-info-circle"></i>
              As imagens buscadas serão filtradas usando IA para manter apenas as que são visualmente similares a esta referência.
            </small>
          </p>
          <div class="d-flex align-items-center gap-3">
            <div>
              <small class="text-muted">Threshold de Similaridade:</small>
              <strong>{{ $product->similarity_threshold ?? 0.7 }}</strong>
              <small class="text-muted">(0.0 - 1.0)</small>
            </div>
            <form method="POST" action="{{ route('panel.products.reference-image.delete', $product->id) }}" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Deseja remover a imagem de referência?')">
                <i class="bi bi-trash"></i> Remover
              </button>
            </form>
          </div>
        </div>
      </div>
      @else
      <div class="alert alert-info mb-0">
        <i class="bi bi-lightbulb"></i>
        <strong>Melhore a busca de imagens!</strong>
        <p class="mb-0 mt-2">
          Defina uma imagem de referência e o sistema usará IA (Gemini Vision) para buscar apenas imagens similares visualmente ao produto desejado.
        </p>
        <small class="text-muted">
          Isso elimina resultados irrelevantes e mantém apenas imagens realmente parecidas com o produto.
        </small>
      </div>
      @endif
    </div>

    <!-- Descrição Gerada pela IA -->
    @if($product->description)
    <div class="notion-card mb-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
          <i class="bi bi-magic text-primary"></i> Descrição Gerada pela IA
        </h5>
        @if($productRaw && isset($productRaw->extra))
        @php
          $extra = json_decode($productRaw->extra, true);
          $provider = $extra['ai_provider'] ?? null;
        @endphp
        @if($provider)
        <span class="chip chip-sm chip-{{ $provider === 'gemini' ? 'success' : ($provider === 'openai' ? 'primary' : 'secondary') }}">
          {{ $provider === 'gemini' ? 'Gemini (Gratuito)' : ($provider === 'openai' ? 'OpenAI' : 'Fallback') }}
        </span>
        @endif
        @endif
      </div>

      <div class="description-preview" style="max-height: 400px; overflow-y: auto;">
        {!! \Illuminate\Support\Str::markdown($product->description) !!}
      </div>
    </div>
    @endif

    <!-- Imagens do Produto -->
    <div class="notion-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Imagens do Produto ({{ $images->count() }})</h5>
        <div class="btn-group">
          <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#searchImagesModal">
            <i class="bi bi-search"></i> Buscar Imagens
          </button>
          @if(driveConnected())
          <button type="button" class="btn btn-sm btn-info" onclick="openDrivePicker()">
            <i class="bi bi-cloud"></i> Google Drive
          </button>
          @endif
          <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addImageModal">
            <i class="bi bi-plus-circle"></i> Upload Manual
          </button>
          @if($images->count() > 0)
          <form method="POST" action="{{ route('panel.products.images.deleteAll', $product->id) }}" class="d-inline" onsubmit="return confirm('Deseja remover TODAS as {{ $images->count() }} imagens? Esta ação não pode ser desfeita!')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">
              <i class="bi bi-trash"></i> Remover Todas
            </button>
          </form>
          @endif
        </div>
      </div>

      @if($images->count() > 0)
      <div class="row g-3">
        @foreach($images as $image)
        <div class="col-md-4">
          <div class="position-relative image-card">
            <img src="{{ $image->path }}"
                 alt="Imagem do produto"
                 class="img-fluid rounded"
                 style="width: 100%; height: 200px; object-fit: cover;">
            <div class="position-absolute top-0 end-0 p-2">
              <span class="badge bg-dark">{{ $image->sort }}</span>
            </div>
            <div class="position-absolute bottom-0 start-0 end-0 p-2 image-actions" style="background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);">
              <div class="d-flex justify-content-between align-items-center">
                <small class="text-white">
                  @if($image->sort == 1)
                    <i class="bi bi-star-fill"></i> Principal
                  @endif
                </small>
                <form method="POST" action="{{ route('panel.products.images.delete', [$product->id, $image->id]) }}" class="d-inline" onsubmit="return confirm('Deseja remover esta imagem?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-danger">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
        @endforeach
      </div>
      @else
      <div class="alert alert-info mb-0">
        <i class="bi bi-info-circle"></i>
        Nenhuma imagem adicionada ainda. Clique em "Adicionar Imagem" para fazer upload ou buscar imagens.
      </div>
      @endif
    </div>
  </div>

  <!-- Coluna Lateral -->
  <div class="col-lg-4">
    <!-- Metadados da IA -->
    @if($productRaw && isset($productRaw->extra))
    @php
      $extra = json_decode($productRaw->extra, true);
    @endphp
    <div class="notion-card mb-3">
      <h6 class="mb-3">
        <i class="bi bi-cpu"></i> Metadados da IA
      </h6>

      <div class="mb-2">
        <small class="text-muted">Provider</small>
        <div class="fw-semibold">
          @if(isset($extra['ai_provider']))
            @if($extra['ai_provider'] === 'gemini')
              <span class="text-success">✓ Google Gemini (Gratuito)</span>
            @elseif($extra['ai_provider'] === 'openai')
              <span class="text-primary">OpenAI GPT-4o-mini</span>
            @else
              <span class="text-secondary">Descrição Básica</span>
            @endif
          @else
            N/A
          @endif
        </div>
      </div>

      @if(isset($extra['ai_model']))
      <div class="mb-2">
        <small class="text-muted">Modelo</small>
        <div class="fw-semibold">{{ $extra['ai_model'] }}</div>
      </div>
      @endif

      @if(isset($extra['ai_cost']))
      <div class="mb-2">
        <small class="text-muted">Custo</small>
        <div class="fw-semibold">
          @if($extra['ai_cost'] == 0)
            <span class="text-success">R$ 0,00 (Grátis!)</span>
          @else
            R$ {{ number_format($extra['ai_cost'] * 5.5, 4) }}
          @endif
        </div>
      </div>
      @endif

      @if(isset($extra['processed_at']))
      <div class="mb-2">
        <small class="text-muted">Processado em</small>
        <div class="fw-semibold">{{ \Carbon\Carbon::parse($extra['processed_at'])->format('d/m/Y H:i') }}</div>
      </div>
      @endif
    </div>
    @endif

    <!-- Ações Rápidas -->
    <div class="notion-card">
      <h6 class="mb-3">Ações</h6>
      <div class="d-grid gap-2">
        <a href="{{ route('panel.products.edit', $product->id) }}" class="btn btn-outline-primary">
          <i class="bi bi-pencil"></i> Editar Produto
        </a>
        <a href="{{ route('panel.mercado-livre.prepare', $product->id) }}" class="btn btn-outline-success">
          <i class="bi bi-box-arrow-up"></i> Publicar no ML
        </a>
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteProductModal">
          <i class="bi bi-trash"></i> Excluir Produto
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Buscar Imagens Online -->
<div class="modal fade" id="searchImagesModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-search"></i> Buscar Imagens Online
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- Etapa 1: Configurações de Busca -->
      <div id="searchStep1">
        <div class="modal-body">
          @if($product->reference_image_path)
          <div class="alert alert-success">
            <i class="bi bi-check-circle"></i>
            <strong>Filtro de Similaridade Ativo!</strong>
            <p class="mb-0 mt-2">
              As imagens encontradas serão comparadas com sua imagem de referência usando IA.
              Apenas imagens com similaridade ≥ {{ $product->similarity_threshold ?? 0.7 }} serão retornadas.
            </p>
            <div class="mt-2">
              <img src="{{ $product->reference_image_path }}" alt="Referência" style="max-height: 100px; border-radius: 4px;">
            </div>
          </div>
          @else
          <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Sem Filtro de Similaridade</strong>
            <p class="mb-0 mt-2">
              Você não definiu uma imagem de referência. A busca retornará imagens baseadas apenas em texto (EAN, nome, marca).
            </p>
            <p class="mb-0 mt-2">
              <a href="#" data-bs-toggle="modal" data-bs-target="#referenceImageModal" data-bs-dismiss="modal">
                Clique aqui para definir uma imagem de referência
              </a> e melhorar a precisão da busca.
            </p>
          </div>
          @endif

          <div class="mb-3">
            <label class="form-label fw-semibold">
              Palavras-chave adicionais (Opcional)
              <span class="text-muted fw-normal">- Para refinar a busca</span>
            </label>
            <input type="text" id="searchContext" class="form-control"
                   placeholder="Ex: vista frontal, fundo branco, alta resolução">
            <small class="text-muted">
              <i class="bi bi-lightbulb"></i>
              Adicione termos específicos para melhorar os resultados. Exemplos: "fundo branco", "vista frontal", "embalagem", "alta qualidade"
            </small>
          </div>

          <div class="mb-3">
            <label class="form-label">Quantidade de imagens</label>
            <select name="limit" id="searchLimit" class="form-select">
              <option value="3">3 imagens</option>
              <option value="5" selected>5 imagens</option>
              <option value="10">10 imagens</option>
            </select>
            <small class="text-muted">
              Quanto mais imagens, maior o tempo de processamento (se usar similaridade).
            </small>
          </div>

          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="use_similarity" id="useSimilarity"
                   {{ $product->reference_image_path ? 'checked' : '' }}
                   {{ !$product->reference_image_path ? 'disabled' : '' }}>
            <label class="form-check-label" for="useSimilarity">
              Usar filtro de similaridade visual
              @if(!$product->reference_image_path)
                <span class="text-muted">(requer imagem de referência)</span>
              @endif
            </label>
          </div>

          <!-- Progresso da busca -->
          <div class="alert alert-info mt-3 mb-0" style="display:none;" id="searchProgress">
            <div class="d-flex align-items-center">
              <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Carregando...</span>
              </div>
              <div>
                <strong>Buscando imagens...</strong>
                <p class="mb-0 small">Isso pode levar 10-40 segundos dependendo da quantidade e se usar similaridade.</p>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-success" id="searchImagesBtn">
            <i class="bi bi-search"></i> Buscar Imagens
          </button>
        </div>
      </div>

      <!-- Etapa 2: Preview e Seleção -->
      <div id="searchStep2" style="display:none;">
        <div class="modal-body">
          <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            <strong>Selecione as imagens que deseja baixar</strong>
            <p class="mb-0 mt-2">
              Clique nas imagens ou nos checkboxes para selecionar. As imagens selecionadas serão baixadas e otimizadas automaticamente.
            </p>
          </div>

          <!-- Grid de preview -->
          <div id="imagesPreview" class="row g-3">
            <!-- Imagens serão inseridas aqui via JavaScript -->
          </div>

          <div class="mt-3">
            <small class="text-muted">
              <span id="selectedCount">0</span> imagem(ns) selecionada(s) de <span id="totalCount">0</span>
            </small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="backToSearchBtn">
            <i class="bi bi-arrow-left"></i> Voltar
          </button>
          <button type="button" class="btn btn-primary" id="selectAllBtn">
            <i class="bi bi-check-all"></i> Selecionar Todas
          </button>
          <button type="button" class="btn btn-success" id="downloadSelectedBtn" disabled>
            <i class="bi bi-download"></i> Baixar Selecionadas
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Imagem de Referência -->
<div class="modal fade" id="referenceImageModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-bullseye"></i> Definir Imagem de Referência
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('panel.products.reference-image.upload', $product->id) }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            <strong>Como funciona?</strong>
            <p class="mb-0 mt-2">
              A imagem de referência será usada pelo Gemini Vision (IA) para comparar e filtrar as imagens encontradas na busca online,
              mantendo apenas as que são visualmente similares ao produto desejado.
            </p>
          </div>

          <div class="mb-3">
            <label class="form-label">Selecione a imagem de referência</label>
            <input type="file" name="reference_image" class="form-control" accept="image/*" required>
            <small class="text-muted">
              Formatos aceitos: JPG, PNG. Use uma imagem clara do produto que você deseja buscar.
            </small>
          </div>

          <div class="mb-3">
            <label class="form-label">Threshold de Similaridade</label>
            <input type="range" name="similarity_threshold" class="form-range" min="0" max="1" step="0.1" value="{{ $product->similarity_threshold ?? 0.7 }}" id="similarityRange">
            <div class="d-flex justify-content-between">
              <small class="text-muted">Menos rigoroso (mais imagens)</small>
              <strong id="similarityValue">{{ $product->similarity_threshold ?? 0.7 }}</strong>
              <small class="text-muted">Mais rigoroso (menos imagens)</small>
            </div>
            <small class="text-muted">
              <i class="bi bi-lightbulb"></i>
              Valores mais altos (ex: 0.8-0.9) retornam apenas imagens muito similares.
              Valores mais baixos (ex: 0.5-0.6) permitem mais variação.
            </small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Salvar Referência
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Adicionar Imagem -->
<div class="modal fade" id="addImageModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Adicionar Imagem</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <ul class="nav nav-tabs mb-3" role="tablist">
          <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#upload-tab">Upload</button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#search-tab">Buscar Online</button>
          </li>
        </ul>

        <div class="tab-content">
          <div class="tab-pane fade show active" id="upload-tab">
            <form method="POST" action="{{ route('panel.products.images.upload', $product->id) }}" enctype="multipart/form-data">
              @csrf
              <div class="mb-3">
                <label class="form-label">Selecione imagens</label>
                <input type="file" name="images[]" class="form-control" multiple accept="image/*" required>
                <small class="text-muted">Formatos aceitos: JPG, PNG, WEBP</small>
              </div>

              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="resize_images" id="resizeImages" value="1">
                  <label class="form-check-label" for="resizeImages">
                    <strong>Redimensionar e otimizar imagens</strong>
                  </label>
                </div>
                <small class="text-muted d-block mt-1">
                  <i class="bi bi-info-circle"></i>
                  Ao marcar esta opção, as imagens serão:
                  <ul class="small mb-0 mt-1">
                    <li>Redimensionadas para mínimo 500x500px (mantendo proporção)</li>
                    <li>Adicionado fundo branco se necessário</li>
                    <li>Convertidas para JPEG otimizado (90% qualidade)</li>
                    <li>Centralizadas automaticamente</li>
                  </ul>
                  <strong class="text-primary">Recomendado para Mercado Livre</strong>
                </small>
              </div>

              <button type="submit" class="btn btn-primary">Fazer Upload</button>
            </form>
          </div>

          <div class="tab-pane fade" id="search-tab">
            <div class="alert alert-info">
              <i class="bi bi-info-circle"></i>
              Busca automática de imagens será implementada em breve.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('styles')
<style>
.description-preview {
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.description-preview h2 {
  font-size: 1.25rem;
  margin-top: 1rem;
  margin-bottom: 0.5rem;
}

.description-preview h3 {
  font-size: 1.1rem;
  margin-top: 0.75rem;
  margin-bottom: 0.5rem;
}

.description-preview ul, .description-preview ol {
  padding-left: 1.5rem;
}

.description-preview strong {
  font-weight: 600;
}

.chip-success {
  background-color: #d4edda;
  color: #155724;
}

.chip-primary {
  background-color: #cfe2ff;
  color: #084298;
}

.chip-secondary {
  background-color: #e2e3e5;
  color: #41464b;
}

.image-card {
  transition: transform 0.2s;
}

.image-card:hover {
  transform: translateY(-2px);
}

.image-card .image-actions {
  opacity: 0;
  transition: opacity 0.2s;
}

.image-card:hover .image-actions {
  opacity: 1;
}

.image-preview-card {
  transition: all 0.2s;
  padding: 8px;
  border-radius: 8px;
}

.image-preview-card:hover {
  background-color: #f8f9fa;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.image-preview-card img {
  border: 2px solid #dee2e6;
}

.image-preview-card .image-checkbox {
  background-color: white;
  border: 2px solid #6c757d;
  cursor: pointer;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Slider de threshold
  const rangeInput = document.getElementById('similarityRange');
  const valueDisplay = document.getElementById('similarityValue');

  if (rangeInput && valueDisplay) {
    rangeInput.addEventListener('input', function() {
      valueDisplay.textContent = parseFloat(this.value).toFixed(1);
    });
  }

  // Sistema de busca de imagens com preview
  const productId = {{ $product->id }};
  let foundImages = [];
  let selectedImages = new Set();

  const searchBtn = document.getElementById('searchImagesBtn');
  const searchProgress = document.getElementById('searchProgress');
  const step1 = document.getElementById('searchStep1');
  const step2 = document.getElementById('searchStep2');
  const imagesPreview = document.getElementById('imagesPreview');
  const selectedCountEl = document.getElementById('selectedCount');
  const totalCountEl = document.getElementById('totalCount');
  const backToSearchBtn = document.getElementById('backToSearchBtn');
  const selectAllBtn = document.getElementById('selectAllBtn');
  const downloadSelectedBtn = document.getElementById('downloadSelectedBtn');

  // Buscar imagens
  searchBtn.addEventListener('click', async function() {
    const limit = document.getElementById('searchLimit').value;
    const useSimilarity = document.getElementById('useSimilarity').checked;
    const searchContext = document.getElementById('searchContext').value.trim();

    // Mostra loading
    searchBtn.disabled = true;
    searchBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Buscando...';
    searchProgress.style.display = 'block';

    try {
      const formData = new FormData();
      formData.append('limit', limit);
      formData.append('use_similarity', useSimilarity ? '1' : '0');
      formData.append('context', searchContext);
      formData.append('_token', '{{ csrf_token() }}');

      const response = await fetch(`/panel/products/${productId}/images/search`, {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (data.success) {
        foundImages = data.images;
        selectedImages.clear();

        // Mostra step 2 com as imagens
        showImagePreview(data.images);
      } else {
        alert(data.message || 'Erro ao buscar imagens');
        searchBtn.disabled = false;
        searchBtn.innerHTML = '<i class="bi bi-search"></i> Buscar Imagens';
        searchProgress.style.display = 'none';
      }
    } catch (error) {
      console.error('Erro:', error);
      alert('Erro ao buscar imagens: ' + error.message);
      searchBtn.disabled = false;
      searchBtn.innerHTML = '<i class="bi bi-search"></i> Buscar Imagens';
      searchProgress.style.display = 'none';
    }
  });

  // Mostra preview das imagens
  function showImagePreview(images) {
    // Limpa preview anterior
    imagesPreview.innerHTML = '';

    if (images.length === 0) {
      imagesPreview.innerHTML = '<div class="col-12"><div class="alert alert-warning">Nenhuma imagem encontrada</div></div>';
      return;
    }

    // Cria cards de preview
    images.forEach((image, index) => {
      const col = document.createElement('div');
      col.className = 'col-md-4 col-lg-3';

      const similarity = image.similarity_score ? ` (${(image.similarity_score * 100).toFixed(0)}%)` : '';

      col.innerHTML = `
        <div class="image-preview-card" data-index="${index}" data-url="${image.url}">
          <div class="position-relative">
            <img src="${image.url}" alt="Preview" class="img-fluid rounded" style="width: 100%; height: 200px; object-fit: cover; cursor: pointer;">
            <div class="position-absolute top-0 start-0 p-2">
              <input type="checkbox" class="form-check-input image-checkbox" data-index="${index}" style="width: 20px; height: 20px;">
            </div>
            ${similarity ? `<div class="position-absolute top-0 end-0 p-2"><span class="badge bg-success">${similarity}</span></div>` : ''}
          </div>
          <div class="mt-2">
            <small class="text-muted">${image.width}x${image.height}px</small>
          </div>
        </div>
      `;

      imagesPreview.appendChild(col);
    });

    // Adiciona event listeners
    document.querySelectorAll('.image-preview-card img, .image-checkbox').forEach(el => {
      el.addEventListener('click', function(e) {
        const card = e.target.closest('.image-preview-card');
        const checkbox = card.querySelector('.image-checkbox');
        const index = parseInt(checkbox.dataset.index);

        if (e.target.tagName === 'IMG') {
          checkbox.checked = !checkbox.checked;
        }

        if (checkbox.checked) {
          selectedImages.add(index);
          card.style.border = '3px solid #198754';
          card.style.borderRadius = '8px';
        } else {
          selectedImages.delete(index);
          card.style.border = 'none';
        }

        updateSelectedCount();
      });
    });

    // Atualiza contadores
    totalCountEl.textContent = images.length;
    selectedCountEl.textContent = '0';

    // Mostra step 2
    step1.style.display = 'none';
    step2.style.display = 'block';
    searchBtn.disabled = false;
    searchBtn.innerHTML = '<i class="bi bi-search"></i> Buscar Imagens';
    searchProgress.style.display = 'none';
  }

  // Atualiza contador de selecionadas
  function updateSelectedCount() {
    selectedCountEl.textContent = selectedImages.size;
    downloadSelectedBtn.disabled = selectedImages.size === 0;
  }

  // Voltar para busca
  backToSearchBtn.addEventListener('click', function() {
    step2.style.display = 'none';
    step1.style.display = 'block';
  });

  // Selecionar todas
  selectAllBtn.addEventListener('click', function() {
    const allSelected = selectedImages.size === foundImages.length;

    if (allSelected) {
      // Desselecionar todas
      selectedImages.clear();
      document.querySelectorAll('.image-checkbox').forEach(cb => cb.checked = false);
      document.querySelectorAll('.image-preview-card').forEach(card => {
        card.style.border = 'none';
      });
      selectAllBtn.innerHTML = '<i class="bi bi-check-all"></i> Selecionar Todas';
    } else {
      // Selecionar todas
      selectedImages.clear();
      document.querySelectorAll('.image-checkbox').forEach((cb, idx) => {
        cb.checked = true;
        selectedImages.add(idx);
      });
      document.querySelectorAll('.image-preview-card').forEach(card => {
        card.style.border = '3px solid #198754';
        card.style.borderRadius = '8px';
      });
      selectAllBtn.innerHTML = '<i class="bi bi-x-circle"></i> Desselecionar Todas';
    }

    updateSelectedCount();
  });

  // Baixar imagens selecionadas
  downloadSelectedBtn.addEventListener('click', async function() {
    if (selectedImages.size === 0) return;

    downloadSelectedBtn.disabled = true;
    downloadSelectedBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Baixando...';

    try {
      const imagesToDownload = Array.from(selectedImages).map(index => ({
        url: foundImages[index].url,
        width: foundImages[index].width,
        height: foundImages[index].height
      }));

      const formData = new FormData();
      formData.append('images', JSON.stringify(imagesToDownload));
      formData.append('_token', '{{ csrf_token() }}');

      const response = await fetch(`/panel/products/${productId}/images/download`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ images: imagesToDownload })
      });

      if (response.ok) {
        // Recarrega a página para mostrar as novas imagens
        window.location.reload();
      } else {
        const data = await response.json();
        alert(data.message || 'Erro ao baixar imagens');
        downloadSelectedBtn.disabled = false;
        downloadSelectedBtn.innerHTML = '<i class="bi bi-download"></i> Baixar Selecionadas';
      }
    } catch (error) {
      console.error('Erro:', error);
      alert('Erro ao baixar imagens: ' + error.message);
      downloadSelectedBtn.disabled = false;
      downloadSelectedBtn.innerHTML = '<i class="bi bi-download"></i> Baixar Selecionadas';
    }
  });

  // Reset ao fechar modal
  document.getElementById('searchImagesModal').addEventListener('hidden.bs.modal', function() {
    step2.style.display = 'none';
    step1.style.display = 'block';
    foundImages = [];
    selectedImages.clear();
    imagesPreview.innerHTML = '';
  });
});
</script>
@endpush

<!-- Modal: Confirmar Exclusão do Produto -->
<div class="modal fade" id="deleteProductModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">
          <i class="bi bi-exclamation-triangle"></i> Excluir Produto
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle"></i>
          <strong>Atenção!</strong> Esta ação não pode ser desfeita.
        </div>

        <p class="mb-3">Você está prestes a excluir permanentemente o produto:</p>

        <div class="bg-light p-3 rounded mb-3">
          <div class="fw-bold">{{ $product->name }}</div>
          <small class="text-muted">SKU: {{ $product->sku }}</small>
        </div>

        <p class="mb-2"><strong>O que será excluído:</strong></p>
        <ul>
          <li>Dados do produto no banco de dados</li>
          <li>{{ $images->count() }} imagem(ns) do storage</li>
          @if($product->reference_image_path)
          <li>Imagem de referência</li>
          @endif
        </ul>

        <p class="text-danger fw-bold mb-0">
          <i class="bi bi-exclamation-circle"></i>
          Tem certeza que deseja continuar?
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Cancelar
        </button>
        <form method="POST" action="{{ route('panel.products.destroy', $product->id) }}" class="d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger">
            <i class="bi bi-trash"></i> Sim, Excluir Produto
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

@if(driveConnected())
<!-- Google Picker API -->
<script src="https://apis.google.com/js/api.js"></script>
<script>
  let accessToken = '{{ driveAccessToken() }}';
  const developerKey = '{{ env('GOOGLE_API_KEY') }}';
  const clientId = '{{ env('GOOGLE_CLIENT_ID') }}';
  const appId = '{{ env('GOOGLE_APP_ID', '') }}';
  let pickerApiLoaded = false;
  let oauthToken;

  function loadPicker() {
    gapi.load('auth', {'callback': onAuthApiLoad});
    gapi.load('picker', {'callback': onPickerApiLoad});
  }

  function onAuthApiLoad() {
    gapi.auth.setToken({access_token: accessToken});
    oauthToken = accessToken;
  }

  function onPickerApiLoad() {
    pickerApiLoaded = true;
  }

  function openDrivePicker() {
    if (pickerApiLoaded && oauthToken) {
      createPicker();
    } else {
      loadPicker();
      setTimeout(openDrivePicker, 500);
    }
  }

  function createPicker() {
    const docsView = new google.picker.DocsView(google.picker.ViewId.DOCS_IMAGES)
      .setIncludeFolders(true)
      .setSelectFolderEnabled(false)
      .setMode(google.picker.DocsViewMode.GRID);

    const picker = new google.picker.PickerBuilder()
      .addView(docsView)
      .addView(new google.picker.DocsUploadView())
      .setOAuthToken(oauthToken)
      .setDeveloperKey(developerKey)
      .setCallback(pickerCallback)
      .setTitle('Selecione imagens do Google Drive')
      .enableFeature(google.picker.Feature.MULTISELECT_ENABLED)
      .build();

    picker.setVisible(true);
  }

  function pickerCallback(data) {
    if (data[google.picker.Response.ACTION] == google.picker.Action.PICKED) {
      const docs = data[google.picker.Response.DOCUMENTS];

      // Mostra loading
      const loadingHtml = `
        <div class="alert alert-info">
          <i class="bi bi-hourglass-split"></i>
          Baixando ${docs.length} imagem(ns) do Google Drive...
        </div>
      `;
      document.querySelector('.notion-card').insertAdjacentHTML('afterbegin', loadingHtml);

      // Envia IDs dos arquivos para o backend
      const fileIds = docs.map(doc => doc.id);

      fetch('{{ route('panel.products.images.drive.download', $product->id) }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ file_ids: fileIds })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          window.location.reload();
        } else {
          alert('Erro ao baixar imagens: ' + (data.message || 'Erro desconhecido'));
          location.reload();
        }
      })
      .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao processar imagens do Drive');
        location.reload();
      });
    }
  }

  // Carregar API automaticamente
  if (typeof gapi !== 'undefined') {
    loadPicker();
  }
</script>
@endif

@endsection
