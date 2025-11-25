@extends('layouts.panel')
@section('title', 'Preparar Anúncio Mercado Livre')

@section('content')
<div class="container">
  <h1 class="mb-4">
    Preparar Anúncio - {{ $product->name }}
  </h1>

  @if(session('ok'))
    <div class="alert alert-success">
      {{ session('ok') }}
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger">
      {{ session('error') }}
    </div>
  @endif
  @if(!empty($listing?->validation_errors))
    <div class="alert alert-warning">
      <strong>Atenção:</strong> ainda existem pontos a melhorar:
      <ul class="mb-0">
        @foreach($listing->validation_errors as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('panel.mercado-livre.save-draft', $product->id) }}">
    @csrf
    <div class="row">
      <!-- Título -->
      <div class="col-md-6 mb-3">
        <label for="mlTitle" class="form-label">
          Título do anúncio
          <span class="text-danger">*</span>
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
      <div class="col-md-6 mb-3">
        <label for="mlCategory" class="form-label">
          Categoria no Mercado Livre
          <span class="text-danger">*</span>
        </label>
        <input type="text"
               name="category_id"
               class="form-control"
               id="mlCategory"
               value="{{ $listing->category_id ?? '' }}"
               placeholder="Ex: MLB271709">
        <small class="text-muted">
          Use o identificador da categoria (ex.: MLB271709). Você pode usar a busca de categorias na documentação do ML.
        </small>
      </div>
    </div>

    <div class="row">
      <!-- Preço -->
      <div class="col-md-4 mb-3">
        <label class="form-label">
          Preço
          <span class="text-danger">*</span>
        </label>
        <input type="number"
               step="0.01"
               min="0.01"
               class="form-control"
               name="price"
               value="{{ $listing->price ?? $product->price }}">
      </div>

      <!-- Quantidade -->
      <div class="col-md-4 mb-3">
        <label class="form-label">
          Quantidade disponível
          <span class="text-danger">*</span>
        </label>
        <input type="number"
               min="1"
               class="form-control"
               name="available_quantity"
               value="{{ $listing->available_quantity ?? $product->stock ?? 1 }}">
      </div>

      <!-- Condição -->
      <div class="col-md-4 mb-3">
        <label class="form-label">
          Condição
          <span class="text-danger">*</span>
        </label>
        <select name="condition" class="form-select">
          <option value="new" {{ ($listing->condition ?? 'new') === 'new' ? 'selected' : '' }}>Novo</option>
          <option value="used" {{ ($listing->condition ?? 'new') === 'used' ? 'selected' : '' }}>Usado</option>
        </select>
      </div>
    </div>

    <div class="row">
      <!-- Tipo de anúncio -->
      <div class="col-md-4 mb-3">
        <label class="form-label">
          Tipo de anúncio
          <span class="text-danger">*</span>
        </label>
        <select name="listing_type_id" class="form-select">
          <option value="gold_special" {{ ($listing->listing_type_id ?? 'gold_special') === 'gold_special' ? 'selected' : '' }}>
            Clássico (Gold Special)
          </option>
          <option value="gold_pro" {{ ($listing->listing_type_id ?? '') === 'gold_pro' ? 'selected' : '' }}>
            Premium (Gold Pro)
          </option>
          <option value="free" {{ ($listing->listing_type_id ?? '') === 'free' ? 'selected' : '' }}>
            Grátis (Free)
          </option>
        </select>
      </div>

      <!-- Modo de envio -->
      <div class="col-md-4 mb-3">
        <label class="form-label">
          Envio
          <span class="text-danger">*</span>
        </label>
        <select name="shipping_mode" class="form-select">
          <option value="me2" {{ ($listing->shipping_mode ?? 'me2') === 'me2' ? 'selected' : '' }}>
            Mercado Envios
          </option>
          <option value="custom" {{ ($listing->shipping_mode ?? '') === 'custom' ? 'selected' : '' }}>
            Envio customizado
          </option>
        </select>
      </div>

      <!-- Retirada local -->
      <div class="col-md-4 mb-3">
        <label class="form-label">
          Retirada local
          <span class="text-danger">*</span>
        </label>
        <select name="shipping_local_pick_up" class="form-select">
          <option value="false" {{ ($listing->shipping_local_pick_up ?? 'false') === 'false' ? 'selected' : '' }}>Não</option>
          <option value="true" {{ ($listing->shipping_local_pick_up ?? '') === 'true' ? 'selected' : '' }}>Sim</option>
        </select>
      </div>
    </div>

    <!-- Frete grátis -->
    <div class="mb-3 form-check">
      <input type="checkbox"
             class="form-check-input"
             id="freeShipping"
             name="free_shipping"
             value="1"
             {{ ($listing->free_shipping ?? false) ? 'checked' : '' }}>
      <label class="form-check-label" for="freeShipping">
        Oferecer frete grátis
      </label>
    </div>

    <!-- Descrição -->
    <div class="mb-3">
      <label class="form-label">
        Descrição do anúncio
      </label>
      <textarea name="plain_text_description"
                class="form-control"
                rows="8"
                placeholder="Descrição em texto plano usada no anúncio">
{{ $listing->plain_text_description ?? $product->description }}
      </textarea>
      <small class="text-muted">
        O Mercado Livre recomenda descrições completas, em texto plano, sem HTML.
      </small>
    </div>

    <!-- Vídeo -->
    <div class="mb-3">
      <label class="form-label">
        ID do vídeo (YouTube, etc.)
      </label>
      <input type="text"
             class="form-control"
             name="video_id"
             value="{{ $listing->video_id ?? '' }}"
             placeholder="Opcional">
    </div>

    <!-- Garantia -->
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">
          Tipo de garantia
        </label>
        <input type="text"
               class="form-control"
               name="warranty_type"
               value="{{ $listing->warranty_type ?? '' }}"
               placeholder="Ex.: Garantia do fabricante">
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">
          Tempo de garantia
        </label>
        <input type="text"
               class="form-control"
               name="warranty_time"
               value="{{ $listing->warranty_time ?? '' }}"
               placeholder="Ex.: 3 meses">
      </div>
    </div>

    <!-- Atributos da categoria (dinâmicos) -->
    <div class="mb-4">
      <label class="form-label">
        Atributos da categoria
      </label>
      <div id="categoryAttributesContainer">
        @if(!empty($categoryAttributes) && (isset($categoryAttributes['required']) || isset($categoryAttributes['optional'])))
          @foreach(array_merge($categoryAttributes['required'] ?? [], $categoryAttributes['optional'] ?? []) as $attr)
            <div class="mb-2">
              <label class="form-label">
                {{ $attr['name'] }}
                @if(!empty($attr['tags']['required']))
                  <span class="text-danger">*</span>
                @endif
              </label>

              @php
                // Os atributos já vêm decodificados do controller
                $savedAttributes = is_array($listing->attributes ?? null) ? $listing->attributes : [];
                $saved = collect($savedAttributes)->firstWhere('id', $attr['id']);
              @endphp

              @if(($attr['value_type'] ?? '') === 'list' && !empty($attr['values']))
                <select name="ml_attr[{{ $attr['id'] }}]" class="form-select">
                  <option value="">Selecione...</option>
                  @foreach($attr['values'] as $value)
                    @php
                      $optionValue = $value['id'] . '|' . $value['name'];
                      $selected = $saved && (($saved['value_id'] ?? null) == $value['id']);
                    @endphp
                    <option value="{{ $optionValue }}" {{ $selected ? 'selected' : '' }}>
                      {{ $value['name'] }}
                    </option>
                  @endforeach
                </select>
              @else
                <input type="text"
                       name="ml_attr[{{ $attr['id'] }}]"
                       class="form-control"
                       value="{{ $saved['value_name'] ?? '' }}">
              @endif
            </div>
          @endforeach
        @else
          <p class="text-muted">
            Selecione uma categoria válida para carregar os atributos obrigatórios (como TOWEL_TYPE, cor, etc.).
          </p>
        @endif
      </div>
    </div>

    <!-- Imagens -->
    <div class="mb-4">
      <label class="form-label">Imagens do produto</label>
      <div class="row">
        @forelse($images as $img)
          <div class="col-md-3 mb-3">
            <div class="card">
              <img src="{{ asset( $img->path) }}"
                   class="card-img-top"
                   alt="Imagem do produto">
              <div class="card-body p-2">
                <small class="text-muted">Ordem: {{ $img->sort }}</small>
              </div>
            </div>
          </div>
        @empty
          <p class="text-muted">Nenhuma imagem cadastrada para este produto.</p>
        @endforelse
      </div>
    </div>

    <!-- Ações -->
    <div class="d-flex justify-content-between">
      <a href="{{ route('panel.products.index') }}" class="btn btn-outline-secondary">
        Voltar
      </a>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save"></i> Salvar Rascunho
        </button>

        <button type="button" class="btn btn-success" onclick="publishNow()">
          <i class="bi bi-upload"></i> Publicar Agora
        </button>
      </div>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
const titleInput = document.getElementById('mlTitle');
const titleCount = document.getElementById('titleCount');

if (titleInput && titleCount) {
  titleInput.addEventListener('input', function() {
    titleCount.textContent = this.value.length;
  });
}

function publishNow() {
  if (confirm('Deseja publicar este anúncio no Mercado Livre agora?')) {
<<<<<<< HEAD
    // Adiciona campo hidden ao formulário para indicar que deve publicar após salvar
    const mainForm = document.querySelector('form[action*="save-draft"]');

    const publishFlag = document.createElement('input');
    publishFlag.type = 'hidden';
    publishFlag.name = 'publish_after_save';
    publishFlag.value = '1';
    mainForm.appendChild(publishFlag);

    // Submit do formulário principal (salva rascunho + publica)
    mainForm.submit();
=======
    // Adiciona campo hidden para indicar que deve publicar após salvar
    const form = document.querySelector('form');
    const publishInput = document.createElement('input');
    publishInput.type = 'hidden';
    publishInput.name = 'publish_now';
    publishInput.value = '1';
    form.appendChild(publishInput);

    // Submete o formulário (salva o rascunho E depois publica, via controller)
    form.submit();
>>>>>>> 55aa03b2a7c670b5485192e48d1c804fe312aabb
  }
}

// Carrega atributos da categoria quando selecionada
const categorySelect = document.getElementById('mlCategory');
let currentCategoryAttributes = {};

categorySelect.addEventListener('change', function() {
  const categoryId = this.value;

  if (!categoryId) {
    document.getElementById('categoryAttributesContainer').innerHTML =
      '<p class="text-muted">Selecione uma categoria para carregar os atributos.</p>';
    return;
  }

  fetch('{{ route('panel.mercado-livre.category-attributes') }}?category_id=' + encodeURIComponent(categoryId))
    .then(response => response.json())
    .then(data => {
      currentCategoryAttributes = data;
      renderCategoryAttributes();
    })
    .catch(() => {
      document.getElementById('categoryAttributesContainer').innerHTML =
        '<p class="text-danger">Erro ao carregar atributos da categoria.</p>';
    });
});

function renderCategoryAttributes() {
  const container = document.getElementById('categoryAttributesContainer');
  container.innerHTML = '';

  // Mescla atributos required e optional
  const allAttributes = [
    ...(currentCategoryAttributes.required || []),
    ...(currentCategoryAttributes.optional || [])
  ];

  if (allAttributes.length === 0) {
    container.innerHTML = '<p class="text-muted">Nenhum atributo disponível para esta categoria.</p>';
    return;
  }

  @php
    // Os atributos já vêm decodificados do controller
    $savedAttributes = is_array($listing->attributes ?? null) ? $listing->attributes : [];
  @endphp

  const savedAttributes = @json($savedAttributes);

  allAttributes.forEach(attr => {
    const wrapper = document.createElement('div');
    wrapper.classList.add('mb-2');

    const label = document.createElement('label');
    label.classList.add('form-label');
    label.textContent = attr.name + (attr.tags && attr.tags.required ? ' *' : '');

    wrapper.appendChild(label);

    const saved = savedAttributes.find(a => a.id === attr.id);

    let input;
    if (attr.value_type === 'list' && Array.isArray(attr.values) && attr.values.length) {
      input = document.createElement('select');
      input.classList.add('form-select');
      input.name = `ml_attr[${attr.id}]`;

      const optEmpty = document.createElement('option');
      optEmpty.value = '';
      optEmpty.textContent = 'Selecione...';
      input.appendChild(optEmpty);

      attr.values.forEach(value => {
        const opt = document.createElement('option');
        opt.value = `${value.id}|${value.name}`;
        opt.textContent = value.name;

        if (saved && saved.value_id === value.id) {
          opt.selected = true;
        }

        input.appendChild(opt);
      });

    } else {
      input = document.createElement('input');
      input.type = 'text';
      input.name = `ml_attr[${attr.id}]`;
      input.classList.add('form-control');
      input.value = saved && saved.value_name ? saved.value_name : '';
    }

    wrapper.appendChild(input);
    container.appendChild(wrapper);
  });
}

// Carrega atributos se já tiver categoria selecionada na abertura
window.addEventListener('load', function() {
  if (categorySelect && categorySelect.value) {
    categorySelect.dispatchEvent(new Event('change'));
  }
});
</script>
@endpush
