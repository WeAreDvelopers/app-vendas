# Configuração do Google Custom Search API

Este guia mostra como configurar o Google Custom Search para buscar imagens de produtos automaticamente.

## Visão Geral

O Google Custom Search permite buscar imagens na web usando a API do Google. É ideal para encontrar fotos de produtos baseadas em:
- EAN/código de barras
- Nome do produto + marca
- SKU

## Limites e Custos

### Plano Gratuito
- **100 consultas por dia** GRÁTIS
- Perfeito para começar e testar

### Plano Pago
- Após as 100 gratuitas: **$5 USD por 1.000 consultas adicionais**
- Até **10.000 consultas por dia**
- Cada produto usa 1-4 consultas (depende de quantas variações de busca são necessárias)

### Exemplo de Custo
- **100 produtos/dia**: GRÁTIS
- **500 produtos/dia**: ~$10 USD/mês
- **1000 produtos/dia**: ~$100 USD/mês

## Passo 1: Criar API Key

1. Acesse: https://console.cloud.google.com/apis/credentials

2. Selecione ou crie um projeto Google Cloud

3. Clique em **"+ CREATE CREDENTIALS"** → **"API key"**

4. Copie a API key gerada (exemplo: `AIzaSyB...`)

5. (Opcional) Clique em **"RESTRICT KEY"** para adicionar segurança:
   - Em "API restrictions", selecione "Restrict key"
   - Escolha apenas "Custom Search API"
   - Salve

6. Ative a API:
   - Acesse: https://console.cloud.google.com/apis/library/customsearch.googleapis.com
   - Clique em **"ENABLE"**

## Passo 2: Criar Custom Search Engine (CX)

1. Acesse: https://programmablesearchengine.google.com/controlpanel/create

2. Preencha os campos:
   - **Nome**: "Busca de Imagens de Produtos"
   - **O que pesquisar**: Selecione "Pesquisar em toda a web"
   - **Configurações de imagem**: Ative "Image search"

3. Clique em **"Criar"**

4. Na tela seguinte, clique em **"Personalizar"**

5. No menu lateral, vá em **"Configurações básicas"**

6. Copie o **"Search engine ID"** (CX)
   - Exemplo: `017576662512468239146:omuauf_lfve`

7. Ative as seguintes opções:
   - **Image search**: ON
   - **Search the entire web**: ON
   - **SafeSearch**: ON (recomendado)

## Passo 3: Configurar no Laravel

Adicione as credenciais no arquivo `.env`:

```env
# Google Custom Search API (para buscar imagens de produtos)
GOOGLE_SEARCH_API_KEY=AIzaSyB...
GOOGLE_SEARCH_CX=017576662512468239146:omuauf_lfve
```

**Importante**: As configurações já estão no arquivo `config/services.php`, não precisa editar.

## Passo 4: Testar

### Teste Simples via Artisan Tinker

```bash
php artisan tinker
```

```php
// Teste básico de busca
$service = app(\App\Services\ImageSearchService::class);
$results = $service->searchImages('iPhone 13 Pro Max Apple');

// Ver resultados
dump(count($results));
dump($results);
```

### Teste com Produto Real

```php
// Busca um produto da base
$product = \App\Models\ProductRaw::first();

// Busca imagens para o produto
$service = app(\App\Services\ImageSearchService::class);
$images = $service->searchForProduct($product);

// Ver URLs encontradas
foreach ($images as $img) {
    echo $img['url'] . "\n";
    echo "Tamanho: {$img['width']}x{$img['height']}\n\n";
}
```

### Processar Produto Completo

```bash
php artisan tinker
```

```php
// Processa um produto com IA + busca de imagens
$product = \App\Models\ProductRaw::where('status', 'raw')->first();

if ($product) {
    \App\Jobs\ProcessProductWithAI::dispatch($product->id);
    echo "Job despachado! Produto ID: {$product->id}\n";
}

// Roda a queue
exit
```

```bash
php artisan queue:work --tries=3
```

## Como Funciona

### 1. Queries de Busca Inteligentes

O sistema cria múltiplas queries para aumentar as chances de encontrar imagens:

1. **EAN + "produto"** (mais preciso)
2. **Marca + Nome**
3. **Nome completo**
4. **SKU + "produto"** (menos confiável)

### 2. Filtros Aplicados

- **Tamanho mínimo**: 500x500 pixels (requisito do Mercado Livre)
- **Tamanho recomendado**: 1200x1200 pixels
- **Formatos**: JPG, PNG
- **Tipo**: Fotos (não clipart ou desenhos)
- **SafeSearch**: Ativo

### 3. Otimização Automática

Após encontrar as imagens, o sistema:
- Download automático
- Redimensiona se muito grande (>2000px)
- Converte PNG pesado para JPG
- Remove imagens duplicadas
- Salva em `storage/app/public/product_images/`

### 4. Integração Completa

O `ProcessProductWithAI` job faz tudo automaticamente:
1. Gera descrição com IA (Gemini/OpenAI)
2. Busca imagens no Google
3. Faz download e otimiza
4. Salva no produto
5. Produto fica pronto para publicar no ML

## Monitoramento

### Ver Quantas Consultas Foram Feitas

No Google Cloud Console:
1. Acesse: https://console.cloud.google.com/apis/api/customsearch.googleapis.com/quotas
2. Veja "Queries per day"

### Logs no Laravel

```bash
tail -f storage/logs/laravel.log | grep -i "image"
```

Você verá:
- `Found X images for product Y`
- `Image N downloaded successfully`
- `Google Custom Search not configured` (se não configurado)

## Troubleshooting

### "Google Custom Search not configured"
- Verifique se `GOOGLE_SEARCH_API_KEY` e `GOOGLE_SEARCH_CX` estão no `.env`
- Rode `php artisan config:clear`

### "API error 403 - Forbidden"
- API Key está incorreta ou não tem permissão
- Verifique se a Custom Search API está ativada no projeto

### "API error 429 - Quota exceeded"
- Atingiu o limite de 100 consultas/dia gratuitas
- Adicione billing no Google Cloud para continuar

### "No images found"
- Produto pode ter nome muito genérico ou incomum
- Verifique se o EAN/marca estão preenchidos
- Teste a busca manualmente no Google Imagens

### Imagens não são baixadas
- Verifique permissões da pasta `storage/app/public`
- Rode `php artisan storage:link`
- Verifique se a biblioteca Intervention Image está instalada:
  ```bash
  composer require intervention/image
  ```

## Alternativas Gratuitas

Se quiser evitar custos ou já ultrapassou o limite gratuito:

### 1. Bing Image Search API
- 1.000 transações/mês gratuitas
- Depois: $3 por 1.000 transações

### 2. Unsplash API
- Imagens gratuitas de alta qualidade
- 50 requisições/hora grátis
- Bom para categorias genéricas

### 3. Pexels API
- Imagens gratuitas
- 200 requisições/hora
- Bom para categorias genéricas

### 4. API do Fornecedor
- Se o fornecedor disponibiliza API com imagens
- Geralmente gratuito
- Melhor opção quando disponível

## Próximos Passos

- [ ] Configurar API Key e CX no `.env`
- [ ] Testar com produtos reais
- [ ] Monitorar uso de quota
- [ ] Configurar billing se processar >100 produtos/dia
- [ ] Considerar APIs alternativas como fallback
