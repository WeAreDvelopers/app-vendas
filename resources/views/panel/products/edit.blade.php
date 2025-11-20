@extends('layouts.panel')
@section('title', 'Editar Produto')
@section('page-title', 'Editar Produto')
@section('page-subtitle', 'SKU: ' . ($product->sku ?? 'N/A'))

@section('content')
<form method="POST" action="{{ route('panel.products.update', $product->id) }}">
  @csrf
  @method('PUT')

  <div class="row g-3">
    <!-- Coluna Principal - Formulário -->
    <div class="col-lg-8">

      <!-- Informações Básicas -->
      <div class="notion-card mb-3">
        <h5 class="mb-3">
          <i class="bi bi-info-circle text-primary"></i> Informações Básicas
        </h5>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">SKU <span class="text-danger">*</span></label>
            <input type="text" class="form-control" value="{{ $product->sku }}" disabled>
            <small class="text-muted">SKU não pode ser alterado</small>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">EAN / GTIN</label>
            <input type="text" name="ean" class="form-control @error('ean') is-invalid @enderror"
                   value="{{ old('ean', $product->ean ?? '') }}" maxlength="20"
                   placeholder="7891234567890">
            @error('ean')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Código de barras do produto</small>
          </div>

          <div class="col-md-12">
            <label class="form-label fw-semibold">Nome do Produto <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $product->name) }}" required maxlength="255">
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Nome completo e descritivo do produto</small>
          </div>

          <div class="col-md-12">
            <label class="form-label fw-semibold">Título do Anúncio (Mercado Livre)</label>
            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                   value="{{ old('title', $product->title ?? '') }}" maxlength="60"
                   placeholder="Ex: Notebook Dell Inspiron 15 i5 8GB 256GB SSD">
            @error('title')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">
              <span id="titleCounter">{{ strlen($product->title ?? '') }}</span>/60 caracteres
              - Seja específico e inclua marca, modelo e características principais
            </small>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Marca</label>
            <input type="text" name="brand" class="form-control @error('brand') is-invalid @enderror"
                   value="{{ old('brand', $product->brand ?? '') }}" maxlength="100"
                   placeholder="Ex: Dell, Samsung, Nike">
            @error('brand')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Categoria</label>
            <input type="text" name="category" class="form-control @error('category') is-invalid @enderror"
                   value="{{ old('category', $product->category ?? '') }}" maxlength="100"
                   placeholder="Ex: Eletrônicos, Moda, Casa">
            @error('category')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Categoria do produto no Mercado Livre</small>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Condição <span class="text-danger">*</span></label>
            <select name="condition" class="form-select @error('condition') is-invalid @enderror" required>
              <option value="new" {{ old('condition', $product->condition ?? 'new') == 'new' ? 'selected' : '' }}>Novo</option>
              <option value="used" {{ old('condition', $product->condition ?? '') == 'used' ? 'selected' : '' }}>Usado</option>
            </select>
            @error('condition')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Garantia</label>
            <input type="text" name="warranty" class="form-control @error('warranty') is-invalid @enderror"
                   value="{{ old('warranty', $product->warranty ?? '') }}" maxlength="50"
                   placeholder="Ex: 12 meses, 6 meses">
            @error('warranty')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Tempo de garantia do produto</small>
          </div>
        </div>
      </div>

      <!-- Descrição -->
      <div class="notion-card mb-3">
        <h5 class="mb-3">
          <i class="bi bi-file-text text-primary"></i> Descrição
        </h5>

        <div class="mb-3">
          <label class="form-label fw-semibold">Descrição do Produto</label>
          <textarea name="description" rows="10" class="form-control @error('description') is-invalid @enderror"
                    placeholder="Descreva o produto em detalhes...">{{ old('description', $product->description ?? '') }}</textarea>
          @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
          <small class="text-muted">
            <i class="bi bi-lightbulb"></i>
            Inclua características, especificações técnicas, benefícios e conteúdo da embalagem
          </small>
        </div>

        <div>
          <label class="form-label fw-semibold">URL do Vídeo (YouTube)</label>
          <input type="url" name="video_url" class="form-control @error('video_url') is-invalid @enderror"
                 value="{{ old('video_url', $product->video_url ?? '') }}"
                 placeholder="https://www.youtube.com/watch?v=...">
          @error('video_url')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
          <small class="text-muted">Vídeo demonstrativo do produto (opcional)</small>
        </div>
      </div>

      <!-- Preço e Estoque -->
      <div class="notion-card mb-3">
        <h5 class="mb-3">
          <i class="bi bi-currency-dollar text-success"></i> Preço e Estoque
        </h5>

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Preço de Custo</label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="number" name="cost_price" step="0.01" min="0"
                     class="form-control @error('cost_price') is-invalid @enderror"
                     value="{{ old('cost_price', $product->cost_price ?? '') }}"
                     placeholder="0,00">
            </div>
            @error('cost_price')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Custo de aquisição do produto</small>
          </div>

          <div class="col-md-4">
            <label class="form-label fw-semibold">Preço de Venda <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="number" name="price" step="0.01" min="0" required
                     class="form-control @error('price') is-invalid @enderror"
                     value="{{ old('price', $product->price ?? '') }}"
                     placeholder="0,00">
            </div>
            @error('price')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Preço de venda ao cliente</small>
          </div>

          <div class="col-md-4">
            <label class="form-label fw-semibold">Estoque <span class="text-danger">*</span></label>
            <input type="number" name="stock" min="0" required
                   class="form-control @error('stock') is-invalid @enderror"
                   value="{{ old('stock', $product->stock ?? '') }}"
                   placeholder="0">
            @error('stock')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Quantidade disponível</small>
          </div>

          @if(old('cost_price', $product->cost_price ?? null) && old('price', $product->price ?? null))
          <div class="col-12">
            <div class="alert alert-info mb-0">
              <strong>Margem de Lucro:</strong>
              @php
                $cost = floatval(old('cost_price', $product->cost_price ?? 0));
                $price = floatval(old('price', $product->price ?? 0));
                $margin = $price > 0 ? (($price - $cost) / $price) * 100 : 0;
              @endphp
              <span class="{{ $margin > 0 ? 'text-success' : 'text-danger' }}">
                {{ number_format($margin, 2) }}%
                (R$ {{ number_format($price - $cost, 2, ',', '.') }})
              </span>
            </div>
          </div>
          @endif
        </div>
      </div>

      <!-- Dimensões e Peso (Obrigatório para Envio) -->
      <div class="notion-card mb-3">
        <h5 class="mb-3">
          <i class="bi bi-box-seam text-warning"></i> Dimensões e Peso
          <span class="badge bg-warning text-dark">Obrigatório para Mercado Livre</span>
        </h5>

        <div class="alert alert-warning mb-3">
          <i class="bi bi-exclamation-triangle"></i>
          <strong>Importante:</strong> As dimensões e peso são obrigatórios para calcular o frete no Mercado Livre.
          Forneça medidas precisas do produto embalado.
        </div>

        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label fw-semibold">Peso (gramas) <span class="text-danger">*</span></label>
            <input type="number" name="weight" step="0.01" min="0" required
                   class="form-control @error('weight') is-invalid @enderror"
                   value="{{ old('weight', $product->weight ?? '') }}"
                   placeholder="Ex: 500">
            @error('weight')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Em gramas (g)</small>
          </div>

          <div class="col-md-3">
            <label class="form-label fw-semibold">Largura (cm) <span class="text-danger">*</span></label>
            <input type="number" name="width" step="0.01" min="0" required
                   class="form-control @error('width') is-invalid @enderror"
                   value="{{ old('width', $product->width ?? '') }}"
                   placeholder="Ex: 20">
            @error('width')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Em centímetros</small>
          </div>

          <div class="col-md-3">
            <label class="form-label fw-semibold">Altura (cm) <span class="text-danger">*</span></label>
            <input type="number" name="height" step="0.01" min="0" required
                   class="form-control @error('height') is-invalid @enderror"
                   value="{{ old('height', $product->height ?? '') }}"
                   placeholder="Ex: 15">
            @error('height')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Em centímetros</small>
          </div>

          <div class="col-md-3">
            <label class="form-label fw-semibold">Comprimento (cm) <span class="text-danger">*</span></label>
            <input type="number" name="length" step="0.01" min="0" required
                   class="form-control @error('length') is-invalid @enderror"
                   value="{{ old('length', $product->length ?? '') }}"
                   placeholder="Ex: 30">
            @error('length')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Em centímetros</small>
          </div>
        </div>
      </div>

      <!-- Botões de Ação -->
      <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save"></i> Salvar Alterações
        </button>
        <a href="{{ route('panel.products.show', $product->id) }}" class="btn btn-outline-secondary">
          <i class="bi bi-x-circle"></i> Cancelar
        </a>
      </div>
    </div>

    <!-- Coluna Lateral - Preview e Ajuda -->
    <div class="col-lg-4">
      <!-- Preview do Anúncio -->
      <div class="notion-card mb-3">
        <h6 class="mb-3">
          <i class="bi bi-eye"></i> Preview do Anúncio
        </h6>

        <div class="ml-preview-card">
          <!-- Imagem Principal -->
          <div class="ml-preview-image">
            @if($images->count() > 0)
              <img id="previewImage" src="{{ $images->first()->path }}" alt="Preview">
            @else
              <div class="ml-preview-no-image">
                <i class="bi bi-image"></i>
                <p>Sem imagem</p>
              </div>
            @endif
          </div>

          <!-- Título -->
          <div class="ml-preview-title" id="previewTitle">
            {{ ($product->title ?? '') ?: ($product->name ?? 'Título do produto') }}
          </div>

          <!-- Preço -->
          <div class="ml-preview-price">
            <span class="ml-preview-currency">R$</span>
            <span class="ml-preview-amount" id="previewPrice">
              {{ number_format($product->price ?? 0, 2, ',', '.') }}
            </span>
          </div>

          <!-- Condição e Estoque -->
          <div class="ml-preview-meta">
            <span class="ml-preview-condition" id="previewCondition">
              {{ ($product->condition ?? 'new') == 'new' ? 'Novo' : 'Usado' }}
            </span>
            <span class="ml-preview-stock" id="previewStock">
              {{ $product->stock ?? 0 }} disponíveis
            </span>
          </div>

          <!-- Informações Adicionais -->
          <div class="ml-preview-info">
            <div class="ml-preview-info-item" id="previewBrand">
              <i class="bi bi-tag"></i>
              <span>{{ ($product->brand ?? '') ?: 'Marca não informada' }}</span>
            </div>
            @if($product->warranty ?? false)
            <div class="ml-preview-info-item">
              <i class="bi bi-shield-check"></i>
              <span id="previewWarranty">{{ $product->warranty }}</span>
            </div>
            @endif
          </div>
        </div>

        <small class="text-muted d-block mt-2">
          <i class="bi bi-info-circle"></i>
          Preview atualiza em tempo real enquanto você edita
        </small>
      </div>

      <!-- Imagens -->
      <div class="notion-card mb-3">
        <h6 class="mb-3">
          <i class="bi bi-images"></i> Imagens ({{ $images->count() }})
        </h6>

        @if($images->count() > 0)
          <div class="row g-2">
            @foreach($images->take(4) as $image)
              <div class="col-6">
                <img src="{{ $image->path }}" alt="Imagem" class="img-fluid rounded"
                     style="width: 100%; height: 80px; object-fit: cover;">
              </div>
            @endforeach
          </div>
          @if($images->count() > 4)
            <p class="text-muted small mt-2 mb-0">
              +{{ $images->count() - 4 }} imagens
            </p>
          @endif
        @else
          <div class="alert alert-warning mb-0">
            <i class="bi bi-exclamation-triangle"></i>
            Nenhuma imagem adicionada
          </div>
        @endif

        <a href="{{ route('panel.products.show', $product->id) }}" class="btn btn-sm btn-outline-primary w-100 mt-2">
          <i class="bi bi-images"></i> Gerenciar Imagens
        </a>
      </div>

      <!-- Dicas de Preenchimento -->
      <div class="notion-card mb-3">
        <h6 class="mb-3">
          <i class="bi bi-lightbulb"></i> Dicas para Mercado Livre
        </h6>
        <ul class="small mb-0">
          <li class="mb-2"><strong>Título:</strong> Seja específico. Inclua marca, modelo e principais características.</li>
          <li class="mb-2"><strong>Descrição:</strong> Detalhe especificações, benefícios e conteúdo da embalagem.</li>
          <li class="mb-2"><strong>Imagens:</strong> Mínimo 1, recomendado 6-8 imagens de alta qualidade.</li>
          <li class="mb-2"><strong>Dimensões:</strong> Meça o produto EMBALADO para cálculo correto do frete.</li>
          <li class="mb-2"><strong>Garantia:</strong> Produtos novos devem ter no mínimo 3 meses de garantia.</li>
        </ul>
      </div>

      <!-- Status do Produto -->
      <div class="notion-card">
        <h6 class="mb-3">Status Atual</h6>
        @if(isset($product->status))
        <div class="mb-2">
          <small class="text-muted">Status:</small>
          <div>
            <span class="badge bg-{{ $product->status === 'ready' ? 'success' : ($product->status === 'published' ? 'primary' : 'secondary') }}">
              {{ ucfirst($product->status) }}
            </span>
          </div>
        </div>
        @endif
        <div class="mb-2">
          <small class="text-muted">Criado em:</small>
          <div class="fw-semibold">{{ \Carbon\Carbon::parse($product->created_at)->format('d/m/Y H:i') }}</div>
        </div>
        <div>
          <small class="text-muted">Atualizado em:</small>
          <div class="fw-semibold">{{ \Carbon\Carbon::parse($product->updated_at)->format('d/m/Y H:i') }}</div>
        </div>
      </div>
    </div>
  </div>
</form>

@push('styles')
<style>
/* Preview do Anúncio - Estilo Mercado Livre */
.ml-preview-card {
  background: white;
  border: 1px solid #e5e5e5;
  border-radius: 8px;
  overflow: hidden;
}

.ml-preview-image {
  width: 100%;
  height: 250px;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.ml-preview-image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.ml-preview-no-image {
  text-align: center;
  color: #999;
}

.ml-preview-no-image i {
  font-size: 3rem;
  margin-bottom: 0.5rem;
  display: block;
}

.ml-preview-title {
  padding: 16px;
  font-size: 1.1rem;
  font-weight: 400;
  color: #333;
  line-height: 1.4;
  min-height: 60px;
  border-bottom: 1px solid #f5f5f5;
}

.ml-preview-price {
  padding: 12px 16px;
  background: #fff;
  border-bottom: 1px solid #f5f5f5;
}

.ml-preview-currency {
  font-size: 1.2rem;
  color: #666;
  margin-right: 4px;
}

.ml-preview-amount {
  font-size: 2rem;
  font-weight: 300;
  color: #333;
}

.ml-preview-meta {
  padding: 12px 16px;
  display: flex;
  gap: 16px;
  border-bottom: 1px solid #f5f5f5;
}

.ml-preview-condition {
  color: #00a650;
  font-size: 0.875rem;
}

.ml-preview-stock {
  color: #666;
  font-size: 0.875rem;
}

.ml-preview-info {
  padding: 12px 16px;
}

.ml-preview-info-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 0;
  font-size: 0.875rem;
  color: #666;
}

.ml-preview-info-item i {
  color: #999;
}

.ml-preview-info-item span {
  color: #333;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Elementos do preview
  const previewTitle = document.getElementById('previewTitle');
  const previewPrice = document.getElementById('previewPrice');
  const previewCondition = document.getElementById('previewCondition');
  const previewStock = document.getElementById('previewStock');
  const previewBrand = document.getElementById('previewBrand');
  const previewWarranty = document.getElementById('previewWarranty');

  // Inputs do formulário
  const titleInput = document.querySelector('[name="title"]');
  const nameInput = document.querySelector('[name="name"]');
  const priceInput = document.querySelector('[name="price"]');
  const conditionInput = document.querySelector('[name="condition"]');
  const stockInput = document.querySelector('[name="stock"]');
  const brandInput = document.querySelector('[name="brand"]');
  const warrantyInput = document.querySelector('[name="warranty"]');

  // Debug - verificar se elementos foram encontrados
  console.log('Preview do Anúncio - Elementos encontrados:');
  console.log('- previewPrice:', previewPrice);
  console.log('- priceInput:', priceInput);
  console.log('- Valor inicial do preço:', priceInput?.value);

  // Função para formatar preço
  function formatPrice(value) {
    // Remove caracteres não numéricos exceto ponto e vírgula
    const cleanValue = String(value).replace(',', '.');
    const price = parseFloat(cleanValue) || 0;
    return price.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // Atualiza título do preview
  function updatePreviewTitle() {
    const title = titleInput.value.trim() || nameInput.value.trim() || 'Título do produto';
    previewTitle.textContent = title;
  }

  // Atualiza preço do preview
  function updatePreviewPrice() {
    const formattedPrice = formatPrice(priceInput.value);
    previewPrice.textContent = formattedPrice;
    console.log('Preview preço atualizado:', priceInput.value, '->', formattedPrice);
  }

  // Atualiza condição do preview
  function updatePreviewCondition() {
    const condition = conditionInput.value === 'new' ? 'Novo' : 'Usado';
    previewCondition.textContent = condition;
    previewCondition.style.color = conditionInput.value === 'new' ? '#00a650' : '#3483fa';
  }

  // Atualiza estoque do preview
  function updatePreviewStock() {
    const stock = parseInt(stockInput.value) || 0;
    previewStock.textContent = stock + ' disponíveis';

    if (stock === 0) {
      previewStock.style.color = '#ff3b3b';
      previewStock.textContent = 'Sem estoque';
    } else if (stock < 5) {
      previewStock.style.color = '#ff9800';
      previewStock.textContent = stock + ' disponíveis (últimas unidades)';
    } else {
      previewStock.style.color = '#666';
      previewStock.textContent = stock + ' disponíveis';
    }
  }

  // Atualiza marca do preview
  function updatePreviewBrand() {
    const brand = brandInput.value.trim() || 'Marca não informada';
    previewBrand.querySelector('span').textContent = brand;
  }

  // Atualiza garantia do preview
  function updatePreviewWarranty() {
    if (warrantyInput && previewWarranty) {
      const warranty = warrantyInput.value.trim();
      if (warranty) {
        previewWarranty.textContent = warranty;
        previewWarranty.closest('.ml-preview-info-item').style.display = 'flex';
      } else {
        previewWarranty.closest('.ml-preview-info-item').style.display = 'none';
      }
    }
  }

  // Event listeners
  titleInput.addEventListener('input', updatePreviewTitle);
  nameInput.addEventListener('input', updatePreviewTitle);

  // Preço - usar ambos input e change para garantir atualização
  priceInput.addEventListener('input', updatePreviewPrice);
  priceInput.addEventListener('change', updatePreviewPrice);
  priceInput.addEventListener('keyup', updatePreviewPrice);

  conditionInput.addEventListener('change', updatePreviewCondition);

  // Estoque - usar ambos input e change
  stockInput.addEventListener('input', updatePreviewStock);
  stockInput.addEventListener('change', updatePreviewStock);
  stockInput.addEventListener('keyup', updatePreviewStock);

  brandInput.addEventListener('input', updatePreviewBrand);
  if (warrantyInput) {
    warrantyInput.addEventListener('input', updatePreviewWarranty);
  }

  // Contador de caracteres do título
  const titleCounter = document.getElementById('titleCounter');

  if (titleInput && titleCounter) {
    titleInput.addEventListener('input', function() {
      titleCounter.textContent = this.value.length;

      if (this.value.length > 60) {
        titleCounter.classList.add('text-danger');
        titleCounter.classList.remove('text-success', 'text-warning');
      } else if (this.value.length > 50) {
        titleCounter.classList.add('text-warning');
        titleCounter.classList.remove('text-danger', 'text-success');
      } else {
        titleCounter.classList.add('text-success');
        titleCounter.classList.remove('text-danger', 'text-warning');
      }
    });
  }

  // Cálculo automático da margem de lucro
  const costInput = document.querySelector('[name="cost_price"]');

  if (costInput && priceInput) {
    function updateMargin() {
      const cost = parseFloat(costInput.value) || 0;
      const price = parseFloat(priceInput.value) || 0;

      if (price > 0) {
        const margin = ((price - cost) / price) * 100;
        const profit = price - cost;

        console.log('Margem:', margin.toFixed(2) + '%', 'Lucro: R$', profit.toFixed(2));
      }
    }

    costInput.addEventListener('input', updateMargin);
    priceInput.addEventListener('input', updateMargin);
  }
});
</script>
@endpush
@endsection
