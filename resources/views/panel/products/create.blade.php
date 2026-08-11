@extends('layouts.panel')
@section('title', 'Adicionar Produto')
@section('page-title', 'Adicionar Novo Produto')
@section('page-subtitle', 'Cadastro manual de produto')

@section('content')
<form method="POST" action="{{ route('panel.products.store') }}">
  @csrf

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
            <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror"
                   value="{{ old('sku') }}" required maxlength="100"
                   placeholder="Ex: PROD-001">
            @error('sku')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Código único do produto (obrigatório)</small>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">EAN / GTIN</label>
            <input type="text" name="ean" class="form-control @error('ean') is-invalid @enderror"
                   value="{{ old('ean') }}" maxlength="20"
                   placeholder="7891234567890">
            @error('ean')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Código de barras do produto</small>
          </div>

          <div class="col-md-12">
            <label class="form-label fw-semibold">Nome do Produto <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}" required maxlength="255"
                   placeholder="Ex: Notebook Dell Inspiron 15">
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Nome completo e descritivo do produto</small>
          </div>

          <div class="col-md-12">
            <label class="form-label fw-semibold">Título do Anúncio (Mercado Livre)</label>
            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                   value="{{ old('title') }}" maxlength="60"
                   placeholder="Ex: Notebook Dell Inspiron 15 i5 8GB 256GB SSD">
            @error('title')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">
              <span id="titleCounter">0</span>/60 caracteres
              - Seja específico e inclua marca, modelo e características principais
            </small>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Marca</label>
            <input type="text" name="brand" class="form-control @error('brand') is-invalid @enderror"
                   value="{{ old('brand') }}" maxlength="100"
                   placeholder="Ex: Dell, Samsung, Nike">
            @error('brand')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Categoria</label>
            <input type="text" name="category" class="form-control @error('category') is-invalid @enderror"
                   value="{{ old('category') }}" maxlength="100"
                   placeholder="Ex: Eletrônicos, Moda, Casa">
            @error('category')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Categoria do produto no Mercado Livre</small>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Condição <span class="text-danger">*</span></label>
            <select name="condition" class="form-select @error('condition') is-invalid @enderror" required>
              <option value="new" {{ old('condition', 'new') == 'new' ? 'selected' : '' }}>Novo</option>
              <option value="used" {{ old('condition') == 'used' ? 'selected' : '' }}>Usado</option>
            </select>
            @error('condition')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Garantia</label>
            <input type="text" name="warranty" class="form-control @error('warranty') is-invalid @enderror"
                   value="{{ old('warranty') }}" maxlength="50"
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
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">
            <i class="bi bi-file-text text-primary"></i> Descrição
          </h5>
          <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#generateDescriptionModal">
            <i class="bi bi-magic"></i> Gerar com IA
          </button>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Descrição do Produto</label>
          <textarea name="description" id="productDescription" rows="10" class="form-control @error('description') is-invalid @enderror"
                    placeholder="Descreva o produto em detalhes...">{{ old('description') }}</textarea>
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
                 value="{{ old('video_url') }}"
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
                     value="{{ old('cost_price') }}"
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
                     value="{{ old('price') }}"
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
                   value="{{ old('stock', 0) }}"
                   placeholder="0">
            @error('stock')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Quantidade disponível</small>
          </div>
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
                   value="{{ old('weight') }}"
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
                   value="{{ old('width') }}"
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
                   value="{{ old('height') }}"
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
                   value="{{ old('length') }}"
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
        <button type="submit" class="btn btn-success">
          <i class="bi bi-save"></i> Criar Produto
        </button>
        <a href="{{ route('panel.products.index') }}" class="btn btn-outline-secondary">
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
            <div class="ml-preview-no-image">
              <i class="bi bi-image"></i>
              <p>Sem imagem</p>
              <small class="text-muted">Adicione após criar o produto</small>
            </div>
          </div>

          <!-- Título -->
          <div class="ml-preview-title" id="previewTitle">
            Título do produto
          </div>

          <!-- Preço -->
          <div class="ml-preview-price">
            <span class="ml-preview-currency">R$</span>
            <span class="ml-preview-amount" id="previewPrice">
              0,00
            </span>
          </div>

          <!-- Condição e Estoque -->
          <div class="ml-preview-meta">
            <span class="ml-preview-condition" id="previewCondition">
              Novo
            </span>
            <span class="ml-preview-stock" id="previewStock">
              0 disponíveis
            </span>
          </div>

          <!-- Informações Adicionais -->
          <div class="ml-preview-info">
            <div class="ml-preview-info-item" id="previewBrand">
              <i class="bi bi-tag"></i>
              <span>Marca não informada</span>
            </div>
            <div class="ml-preview-info-item" id="previewWarrantyContainer" style="display:none;">
              <i class="bi bi-shield-check"></i>
              <span id="previewWarranty"></span>
            </div>
          </div>
        </div>

        <small class="text-muted d-block mt-2">
          <i class="bi bi-info-circle"></i>
          Preview atualiza em tempo real enquanto você edita
        </small>
      </div>

      <!-- Dicas de Preenchimento -->
      <div class="notion-card mb-3">
        <h6 class="mb-3">
          <i class="bi bi-lightbulb"></i> Dicas para Mercado Livre
        </h6>
        <ul class="small mb-0">
          <li class="mb-2"><strong>SKU:</strong> Use um código único e fácil de identificar.</li>
          <li class="mb-2"><strong>Título:</strong> Seja específico. Inclua marca, modelo e principais características.</li>
          <li class="mb-2"><strong>Descrição:</strong> Detalhe especificações, benefícios e conteúdo da embalagem.</li>
          <li class="mb-2"><strong>Imagens:</strong> Adicione após criar o produto. Recomendado 6-8 imagens de alta qualidade.</li>
          <li class="mb-2"><strong>Dimensões:</strong> Meça o produto EMBALADO para cálculo correto do frete.</li>
          <li class="mb-2"><strong>Garantia:</strong> Produtos novos devem ter no mínimo 3 meses de garantia.</li>
        </ul>
      </div>

      <!-- Ajuda IA -->
      <div class="notion-card">
        <h6 class="mb-3">
          <i class="bi bi-magic"></i> Gerar Descrição com IA
        </h6>
        <p class="small text-muted mb-2">
          Use o botão "Gerar com IA" para criar uma descrição profissional automaticamente.
        </p>
        <p class="small text-muted mb-0">
          Você pode fornecer informações adicionais para personalizar a descrição gerada.
        </p>
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
  const previewWarrantyContainer = document.getElementById('previewWarrantyContainer');

  // Inputs do formulário
  const titleInput = document.querySelector('[name="title"]');
  const nameInput = document.querySelector('[name="name"]');
  const priceInput = document.querySelector('[name="price"]');
  const conditionInput = document.querySelector('[name="condition"]');
  const stockInput = document.querySelector('[name="stock"]');
  const brandInput = document.querySelector('[name="brand"]');
  const warrantyInput = document.querySelector('[name="warranty"]');

  // Função para formatar preço
  function formatPrice(value) {
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
    const warranty = warrantyInput.value.trim();
    if (warranty) {
      previewWarranty.textContent = warranty;
      previewWarrantyContainer.style.display = 'flex';
    } else {
      previewWarrantyContainer.style.display = 'none';
    }
  }

  // Event listeners
  titleInput.addEventListener('input', updatePreviewTitle);
  nameInput.addEventListener('input', updatePreviewTitle);
  priceInput.addEventListener('input', updatePreviewPrice);
  priceInput.addEventListener('change', updatePreviewPrice);
  conditionInput.addEventListener('change', updatePreviewCondition);
  stockInput.addEventListener('input', updatePreviewStock);
  stockInput.addEventListener('change', updatePreviewStock);
  brandInput.addEventListener('input', updatePreviewBrand);
  warrantyInput.addEventListener('input', updatePreviewWarranty);

  // Contador de caracteres do título
  const titleCounter = document.getElementById('titleCounter');

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
});
</script>
@endpush

<!-- Modal: Gerar Descrição com IA -->
<div class="modal fade" id="generateDescriptionModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-magic"></i> Gerar Descrição com IA
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info">
          <i class="bi bi-info-circle"></i>
          <strong>Como funciona:</strong>
          <p class="mb-0 mt-2">
            A IA irá gerar uma descrição profissional baseada nas informações que você fornecer.
            Preencha os campos abaixo para que a descrição seja mais precisa e completa.
          </p>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Informações Base do Produto <span class="text-danger">*</span></label>
          <textarea id="aiProductInfo" class="form-control" rows="3" required
                    placeholder="Ex: Notebook Dell Inspiron 15, processador Intel i5 11ª geração, 8GB RAM, SSD 256GB, tela 15.6 polegadas Full HD"></textarea>
          <small class="text-muted">
            Descreva o produto, modelo, especificações principais
          </small>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Contexto Adicional (Opcional)</label>
          <textarea id="aiContext" class="form-control" rows="4"
                    placeholder="Ex: Este produto é ideal para uso profissional em escritórios. Destaque a durabilidade e garantia estendida. Público-alvo: profissionais que trabalham com design gráfico."></textarea>
          <small class="text-muted">
            <i class="bi bi-lightbulb"></i>
            Dicas: Mencione características específicas, público-alvo, diferenciais ou aspectos que devem ser destacados na descrição.
          </small>
        </div>

        <div class="alert alert-warning" id="generateProgress" style="display:none;">
          <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-2" role="status">
              <span class="visually-hidden">Carregando...</span>
            </div>
            <div>
              <strong>Gerando descrição com IA...</strong>
              <p class="mb-0 small">Isso pode levar alguns segundos. Aguarde...</p>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Cancelar
        </button>
        <button type="button" class="btn btn-primary" id="generateDescriptionBtn">
          <i class="bi bi-magic"></i> Gerar Descrição
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Gerar descrição com IA
document.addEventListener('DOMContentLoaded', function() {
  const generateBtn = document.getElementById('generateDescriptionBtn');
  const generateProgress = document.getElementById('generateProgress');
  const productInfoInput = document.getElementById('aiProductInfo');
  const contextInput = document.getElementById('aiContext');

  if (generateBtn) {
    generateBtn.addEventListener('click', async function() {
      const productInfo = productInfoInput.value.trim();
      const context = contextInput.value.trim();

      // Valida campos obrigatórios
      if (!productInfo) {
        alert('Por favor, forneça as informações base do produto.');
        return;
      }

      // Mostra loading
      generateBtn.disabled = true;
      generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Gerando...';
      generateProgress.style.display = 'block';

      try {
        const formData = new FormData();
        formData.append('product_info', productInfo);
        formData.append('context', context);
        formData.append('_token', '{{ csrf_token() }}');

        const response = await fetch('{{ route('panel.products.generate-description') }}', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (data.success) {
          // Atualiza o textarea com a nova descrição
          document.getElementById('productDescription').value = data.description;

          // Fecha o modal
          const modal = bootstrap.Modal.getInstance(document.getElementById('generateDescriptionModal'));
          modal.hide();

          // Limpa os campos do modal
          productInfoInput.value = '';
          contextInput.value = '';

          // Mostra mensagem de sucesso
          alert('Descrição gerada com sucesso! Revise antes de salvar o produto.');
        } else {
          alert(data.message || 'Erro ao gerar descrição');
        }
      } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao gerar descrição: ' + error.message);
      } finally {
        generateBtn.disabled = false;
        generateBtn.innerHTML = '<i class="bi bi-magic"></i> Gerar Descrição';
        generateProgress.style.display = 'none';
      }
    });
  }
});
</script>

@endsection
