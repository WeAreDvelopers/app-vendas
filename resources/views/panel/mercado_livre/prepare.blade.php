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

      <!-- Atributos da Categoria -->
      <div class="notion-card mb-3" id="categoryAttributesCard" style="display: none;">
        <h5 class="mb-3">
          <i class="bi bi-list-check text-primary"></i> Atributos da Categoria
        </h5>

        <div id="categoryAttributesLoading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Carregando atributos...</span>
          </div>
          <p class="mt-2 text-muted">Carregando atributos obrigatórios da categoria...</p>
        </div>

        <div id="categoryAttributesContent" style="display: none;">
          <!-- Atributos obrigatórios -->
          <div id="requiredAttributes" class="mb-3"></div>

          <!-- Atributos opcionais -->
          <div id="optionalAttributes"></div>
        </div>

        <div id="categoryAttributesError" class="alert alert-warning" style="display: none;">
          <i class="bi bi-exclamation-triangle"></i>
          Não foi possível carregar os atributos da categoria. Tente novamente.
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
                     id="freeShippingCheck"
                     disabled>
              <label class="form-check-label text-muted">
                Frete Grátis (indisponível)
              </label>
            </div>
            <small class="text-warning">
              <i class="bi bi-exclamation-triangle"></i> Requer Mercado Envios Full (me1) - não disponível nesta conta
            </small>
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
        <div class="fw-bold text-success fs-4" id="previewPrice">R$ {{ number_format($listing->price ?? $product->price ?? 0, 2, ',', '.') }}</div>
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

// Atualiza preview do preço
document.querySelector('input[name="price"]').addEventListener('input', function() {
  const price = parseFloat(this.value) || 0;
  const formatted = price.toLocaleString('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  });
  document.getElementById('previewPrice').textContent = formatted;
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

// Carrega atributos da categoria quando selecionada
const categorySelect = document.getElementById('mlCategory');
let currentCategoryAttributes = {};

categorySelect.addEventListener('change', function() {
  const categoryId = this.value;

  if (!categoryId) {
    document.getElementById('categoryAttributesCard').style.display = 'none';
    return;
  }

  // Mostra card de atributos
  document.getElementById('categoryAttributesCard').style.display = 'block';
  document.getElementById('categoryAttributesLoading').style.display = 'block';
  document.getElementById('categoryAttributesContent').style.display = 'none';
  document.getElementById('categoryAttributesError').style.display = 'none';

  // Busca atributos da categoria
  fetch(`{{ route('panel.mercado-livre.category-attributes') }}?category_id=${categoryId}`)
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        throw new Error(data.error);
      }

      currentCategoryAttributes = data;
      renderCategoryAttributes(data);

      document.getElementById('categoryAttributesLoading').style.display = 'none';
      document.getElementById('categoryAttributesContent').style.display = 'block';
    })
    .catch(error => {
      console.error('Erro ao carregar atributos:', error);
      document.getElementById('categoryAttributesLoading').style.display = 'none';
      document.getElementById('categoryAttributesError').style.display = 'block';
    });
});

// Renderiza campos de atributos
function renderCategoryAttributes(data) {
  const requiredContainer = document.getElementById('requiredAttributes');
  const optionalContainer = document.getElementById('optionalAttributes');

  requiredContainer.innerHTML = '';
  optionalContainer.innerHTML = '';

  // Carrega atributos salvos do listing
  let savedAttributes = @json(json_decode($listing->attributes ?? '[]', true) ?? []);

  // Garante que savedAttributes é um array
  if (!Array.isArray(savedAttributes)) {
    savedAttributes = [];
  }

  const savedAttributesMap = {};
  savedAttributes.forEach(attr => {
    if (attr && attr.id && attr.value_name) {
      savedAttributesMap[attr.id] = attr.value_name;
    }
  });

  // Carrega atributos mapeados do produto
  let productAttributes = @json($productAttributes ?? []);

  // Garante que productAttributes é um objeto
  if (typeof productAttributes !== 'object' || productAttributes === null) {
    productAttributes = {};
  }

  // Mescla: prioriza valores salvos, depois valores do produto
  const attributeValues = {...productAttributes, ...savedAttributesMap};

  // Renderiza atributos obrigatórios
  if (data.required && data.required.length > 0) {
    const title = document.createElement('h6');
    title.className = 'text-danger mb-3';
    title.innerHTML = '<i class="bi bi-asterisk"></i> Atributos Obrigatórios';
    requiredContainer.appendChild(title);

    data.required.forEach(attr => {
      requiredContainer.appendChild(createAttributeField(attr, true, attributeValues[attr.id]));
    });
  }

  // Renderiza atributos opcionais (apenas alguns importantes)
  if (data.optional && data.optional.length > 0) {
    const title = document.createElement('h6');
    title.className = 'text-muted mb-3 mt-4';
    title.innerHTML = '<i class="bi bi-plus-circle"></i> Atributos Opcionais (recomendados)';
    optionalContainer.appendChild(title);

    // Mostra apenas os primeiros 5 atributos opcionais
    data.optional.slice(0, 5).forEach(attr => {
      optionalContainer.appendChild(createAttributeField(attr, false, attributeValues[attr.id]));
    });
  }
}

// Cria campo para um atributo
function createAttributeField(attr, required, savedValue) {
  const div = document.createElement('div');
  div.className = 'mb-3';

  const label = document.createElement('label');
  label.className = 'form-label';
  label.innerHTML = attr.name + (required ? ' <span class="text-danger">*</span>' : '');

  if (attr.hint) {
    const hint = document.createElement('small');
    hint.className = 'text-muted d-block';
    hint.textContent = attr.hint;
    label.appendChild(hint);
  }

  div.appendChild(label);

  // Define o campo baseado no tipo
  let input;

  if (attr.values && attr.values.length > 0) {
    // Select com opções predefinidas
    input = document.createElement('select');
    input.className = 'form-select';
    input.name = `ml_attr[${attr.id}]`;
    input.dataset.attrId = attr.id;
    input.dataset.attrName = attr.name;

    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.textContent = 'Selecione...';
    input.appendChild(emptyOption);

    attr.values.forEach(value => {
      const option = document.createElement('option');
      // IMPORTANTE: Usa value.name como valor, não o ID
      // O Mercado Livre espera o nome do valor, não o ID
      option.value = value.name;
      option.textContent = value.name;
      if (savedValue && savedValue === value.name) {
        option.selected = true;
      }
      input.appendChild(option);
    });
  } else if (attr.value_type === 'number' || attr.value_type === 'number_unit') {
    // Campo numérico
    input = document.createElement('input');
    input.type = 'number';
    input.className = 'form-control';
    input.name = `ml_attr[${attr.id}]`;
    input.dataset.attrId = attr.id;
    input.dataset.attrName = attr.name;
    input.step = '0.01';
    if (savedValue) input.value = savedValue;

    // Se tiver unidades, adiciona select de unidade
    if (attr.allowed_units && attr.allowed_units.length > 0) {
      const inputGroup = document.createElement('div');
      inputGroup.className = 'input-group';

      inputGroup.appendChild(input);

      const unitSelect = document.createElement('select');
      unitSelect.className = 'form-select';
      unitSelect.style.maxWidth = '120px';
      unitSelect.name = `ml_attr_unit[${attr.id}]`;

      attr.allowed_units.forEach(unit => {
        const option = document.createElement('option');
        option.value = unit.id;
        option.textContent = unit.name;
        unitSelect.appendChild(option);
      });

      inputGroup.appendChild(unitSelect);
      div.appendChild(inputGroup);
      return div;
    }
  } else {
    // Campo de texto
    input = document.createElement('input');
    input.type = 'text';
    input.className = 'form-control';
    input.name = `ml_attr[${attr.id}]`;
    input.dataset.attrId = attr.id;
    input.dataset.attrName = attr.name;
    if (savedValue) input.value = savedValue;
  }

  if (required) {
    input.required = true;
  }

  div.appendChild(input);
  return div;
}

// Carrega atributos se já tiver categoria selecionada
window.addEventListener('load', function() {
  if (categorySelect.value) {
    categorySelect.dispatchEvent(new Event('change'));
  }
});
</script>
@endpush
