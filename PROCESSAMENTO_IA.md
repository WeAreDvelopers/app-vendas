# Processamento de Produtos com IA

## Visão Geral

Este sistema permite selecionar produtos importados e processá-los com IA para gerar descrições otimizadas e buscar imagens automaticamente.

O sistema usa **Google Gemini como prioridade** (gratuito até o limite) e faz **fallback automático para OpenAI** quando necessário.

## Fluxo de Trabalho

1. **Importação**: Upload de planilha (XLSX/CSV) com dados do fornecedor
2. **Seleção**: Escolha dos produtos que deseja processar com IA
3. **Processamento**: Geração automática de descrição (Gemini → OpenAI → Fallback)
4. **Revisão**: Produtos processados ficam disponíveis para publicação no Mercado Livre

## Configuração

### 1. API do Google Gemini (RECOMENDADO - GRATUITO)

Adicione sua chave da API no arquivo `.env`:

```env
GEMINI_API_KEY=AIza...
GEMINI_MODEL=gemini-1.5-flash  # Opcional, padrão é gemini-1.5-flash
```

**Como obter a chave (GRATUITO):**
1. Acesse https://aistudio.google.com/app/apikey
2. Clique em "Get API key" ou "Create API key"
3. Copie a chave (começa com `AIza`)
4. **Limite gratuito**: 15 requisições/minuto, 1500 requisições/dia

### 2. API da OpenAI (Fallback - PAGO)

Adicione sua chave da API no arquivo `.env`:

```env
OPENAI_API_KEY=sk-proj-...
```

Para obter a chave:
1. Acesse https://platform.openai.com/api-keys
2. Crie uma nova chave de API
3. Adicione créditos na sua conta (mínimo ~$5 USD)

**O sistema só usa OpenAI se o Gemini falhar ou atingir o limite!**

### 2. Estrutura de Dados

#### Tabela `products_raw`
Produtos importados brutos, antes do processamento:
- `sku`: Código do produto
- `ean`: Código de barras
- `name`: Nome básico do produto
- `brand`: Marca
- `cost_price`: Preço de custo
- `sale_price`: Preço de venda
- `status`: raw → processing_ai → ai_processed/ai_failed

#### Tabela `products`
Produtos processados e prontos para publicação:
- `description`: Descrição gerada pela IA
- `status`: draft → ready → published
- `product_raw_id`: Referência ao produto original

#### Tabela `product_images`
Imagens dos produtos:
- `product_id`: ID do produto
- `path`: Caminho da imagem
- `source_url`: URL original da imagem

## Como Usar

### 1. Fazer Upload de Planilha

Acesse: **Painel → Importações → Nova importação**

Preencha:
- Fornecedor (selecione existente ou crie novo)
- Arquivo XLSX ou CSV

A planilha deve conter colunas como:
- SKU / Código / Cod
- EAN / EAN13 / GTIN / Barcode
- Nome / Descrição / Produto
- Marca / Brand / Fabricante
- Preço Custo / Cost
- Preço Venda / Price

### 2. Selecionar Produtos

Após a importação:
1. Clique em "Detalhes" da importação
2. Use os checkboxes para selecionar produtos
3. Clique em "Processar com IA"

### 3. Acompanhar Processamento

O processamento é assíncrono (via queue). Monitore:

```bash
php artisan queue:work
```

Logs disponíveis em `storage/logs/laravel.log`

### 4. Visualizar Produtos Processados

Acesse: **Painel → Produtos**

Produtos com `status = ready` estão prontos para publicação no Mercado Livre.

## Customização

### Prompt da IA

Edite o método `buildPrompt()` em `app/Jobs/ProcessProductWithAI.php`:

```php
private function buildPrompt(ProductRaw $product): string
{
    return "Crie uma descrição para:\n" .
           "Nome: {$product->name}\n" .
           "Marca: {$product->brand}\n" .
           // ... adicione mais informações ...
}
```

### Busca de Imagens

Implemente integrações em `fetchImages()`:

```php
private function fetchImages(ProductRaw $product): array
{
    // Google Custom Search API
    // Cosmos API (para produtos brasileiros)
    // API do fornecedor
    // DALL-E / Stable Diffusion (geração)

    return $imageUrls;
}
```

## Custos e Limites

### Google Gemini (Prioridade 1 - GRATUITO)
- **Modelo**: gemini-1.5-flash
- **Limite gratuito**: 15 req/min, 1500 req/dia
- **Custo**: R$ 0,00 até atingir o limite
- **Velocidade**: Muito rápida
- **Capacidade diária**: ~1500 produtos/dia GRÁTIS

### OpenAI GPT-4o-mini (Fallback - PAGO)
- **Custo**: ~$0.15 entrada + $0.60 saída por 1M tokens
- **Custo médio por descrição**: R$ 0,005 - R$ 0,025
- **Quando é usado**:
  - Gemini não configurado
  - Gemini atingiu rate limit (429)
  - Gemini retornou erro

### Descrição Fallback (Última opção - GRATUITO)
Se ambas APIs falharem:
- Formata os dados existentes
- Inclui informações básicas
- Sem custo adicional
- Qualidade inferior mas funcional

### Exemplo de Uso Real
**Cenário**: Processar 2000 produtos/dia

1. **Primeiros 1500 produtos**: Gemini (GRÁTIS)
2. **Próximos 500 produtos**: OpenAI (~R$ 2,50 - R$ 12,50)
3. **Total estimado**: R$ 2,50 - R$ 12,50/dia

## Tratamento de Erros

### Status dos Produtos

- `raw`: Importado, não processado
- `processing_ai`: Em processamento
- `ai_processed`: Processado com sucesso
- `ai_failed`: Falha no processamento

### Retry Automático

O job tenta 3 vezes com backoff:
1. Primeira tentativa: imediato
2. Segunda tentativa: após 60s
3. Terceira tentativa: após 300s (5min)

### Logs

Verifique erros em:
```bash
tail -f storage/logs/laravel.log
```

## Monitoramento

### Verificar qual IA foi usada

Após processar, verifique nos logs ou no campo `extra` da tabela `products_raw`:

```php
$product = ProductRaw::find($id);
$extra = $product->extra;

echo "Provider: " . $extra['ai_provider']; // gemini, openai ou fallback
echo "Model: " . $extra['ai_model'];
echo "Cost: R$ " . number_format($extra['ai_cost'] * 5.5, 4); // converter USD para BRL
```

### Logs Detalhados

```bash
tail -f storage/logs/laravel.log | grep -i "gemini\|openai"
```

Você verá mensagens como:
- `Gemini generated description successfully`
- `Gemini failed, trying OpenAI fallback`
- `OpenAI generated description successfully`

## Busca Automática de Imagens

### Google Custom Search (Recomendado)

O sistema busca automaticamente imagens dos produtos usando Google Custom Search API.

**Configuração**: Veja o guia completo em `GOOGLE_CUSTOM_SEARCH_SETUP.md`

**Custos**:
- **100 buscas/dia**: GRÁTIS
- **Adicional**: $5 USD por 1.000 buscas

**Como funciona**:
1. Sistema cria queries inteligentes (EAN, Marca+Nome, SKU)
2. Busca imagens com tamanho mínimo 500x500px
3. Download e otimização automática
4. Salva imagens prontas para o Mercado Livre

**Configurar no `.env`**:
```env
GOOGLE_SEARCH_API_KEY=AIzaSyB...
GOOGLE_SEARCH_CX=017576662512468239146:omuauf_lfve
```

**Testar**:
```bash
php artisan tinker
```
```php
$service = app(\App\Services\ImageSearchService::class);
$product = \App\Models\ProductRaw::first();
$images = $service->searchForProduct($product);
dump($images);
```

### Estatísticas de Imagens

Após processar produtos, verifique quantas imagens foram encontradas:

```php
$product = \App\Models\ProductRaw::find($id);
$imageCount = count($product->extra['ai_images'] ?? []);
echo "Encontradas: {$imageCount} imagens\n";
```

## Próximos Passos

- [x] Integração com Google Gemini (Gratuito)
- [x] Fallback automático para OpenAI
- [x] Sistema de custos e monitoramento
- [x] Integração com Google Custom Search para buscar imagens
- [ ] Suporte a Claude AI (Anthropic)
- [ ] Cache de descrições similares
- [ ] Interface para editar descrições geradas
- [ ] Análise de qualidade da descrição (SEO score)
- [ ] Geração de imagens com DALL-E/Stable Diffusion
- [ ] Tradução automática para outros marketplaces
