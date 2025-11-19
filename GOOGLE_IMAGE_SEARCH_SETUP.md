# 🔍 Google Custom Search - Setup para Busca de Imagens

## Por que usar Google Custom Search?

✅ **100 buscas/dia GRATUITAS**
✅ **Imagens de alta qualidade**
✅ **Resultados relevantes**
✅ **Download e otimização automática**
✅ **Integrado no processamento com IA**

## Passo a Passo (10 minutos)

### 1. Criar API Key do Google Cloud

**1.1. Acesse o Google Cloud Console**
🔗 https://console.cloud.google.com/

**1.2. Crie um projeto (se não tiver)**
- Clique em "Select a project" no topo
- Clique em "NEW PROJECT"
- Nome: "App Vendas" (ou outro nome)
- Clique em "CREATE"

**1.3. Habilite a API Custom Search**
- No menu lateral, vá em: **APIs & Services** → **Library**
- Busque por: **"Custom Search API"**
- Clique em **"Custom Search API"**
- Clique em **"ENABLE"**

**1.4. Crie a API Key**
- Vá em: **APIs & Services** → **Credentials**
- Clique em **"+ CREATE CREDENTIALS"**
- Selecione **"API key"**
- Copie a chave (começa com `AIza...`)
- (Opcional) Clique em "RESTRICT KEY" e selecione "Custom Search API"

### 2. Criar Custom Search Engine (CSE)

**2.1. Acesse o Programmable Search Engine**
🔗 https://programmablesearchengine.google.com/

**2.2. Crie um novo Search Engine**
- Clique em **"Add"** ou **"Get started"**
- Em "Sites to search", digite: `*` (asterisco = buscar toda a web)
- Nome: "Busca de Imagens Produtos"
- Clique em **"Create"**

**2.3. Configure para buscar imagens**
- Na lista de Search Engines, clique no que você criou
- Clique em **"Setup"** no menu lateral
- Em "Basic", confirme que "Search the entire web" está ON
- Vá em **"Image search"** e ative: **ON**
- Salve as alterações

**2.4. Copie o Search Engine ID**
- Na página de "Setup" ou "Overview"
- Encontre o **"Search engine ID"** (cx)
- Formato: `0123456789abcdef:ghijklmnop`
- Copie este ID

### 3. Adicionar no `.env`

Abra o arquivo `.env` e adicione:

```env
# Google Custom Search API
GOOGLE_SEARCH_API_KEY=AIzaSy...sua-api-key-aqui
GOOGLE_SEARCH_CX=0123456789abcdef:ghijklmnop
```

### 4. Instalar Dependências

Para processar imagens (redimensionar, otimizar), instale:

```bash
composer require intervention/image
```

### 5. Testar

Execute o processamento de um produto e veja os logs:

```bash
# Em uma aba
php artisan queue:work

# Em outra aba
tail -f storage/logs/laravel.log | grep -i "image"
```

Você verá mensagens como:
```
Found 8 images for product 123
Image 0 downloaded successfully for product 123
Downloaded 5/8 images for product 123
```

## Limites e Custos

### Tier Gratuito

| Limite | Valor |
|--------|-------|
| Buscas por dia | 100 |
| Buscas por segundo | 10 |
| Custo | **R$ 0,00** |

### Tier Pago

Se precisar de mais:
- $5 USD por 1000 buscas adicionais
- Máximo: 10.000 buscas/dia
- Ative no Google Cloud Console

### Como Economizar

1. **Use EAN primeiro**: Busca mais precisa, menos tentativas
2. **Cache de resultados**: Não reprocesse produtos já processados
3. **Limite de imagens**: 3-5 imagens por produto é suficiente
4. **Processamento em lote**: Processe 100 produtos por dia (dentro do limite)

## Funcionamento do Sistema

### Ordem de Busca

O sistema busca imagens nesta ordem:

1. **EAN + "produto"** (mais preciso)
2. **Marca + Nome**
3. **Nome completo**
4. **SKU + "produto"** (menos confiável)

Para quando encontrar resultados relevantes.

### Filtros Aplicados

- ✅ Tamanho mínimo: 500x500px (requisito ML)
- ✅ Tamanho ideal: 1200x1200px
- ✅ Formatos: JPG, PNG
- ✅ Tipo: Fotos (não clipart)
- ✅ Segurança: Safe search ativado

### Processamento

1. **Busca**: Google Custom Search API
2. **Filtro**: Remove imagens pequenas
3. **Ordenação**: Por tamanho (maior = melhor qualidade)
4. **Download**: Baixa top 3-5 imagens
5. **Otimização**:
   - Redimensiona se muito grande (max 2000px)
   - Converte PNG pesado para JPG
   - Compressão 90% (balance qualidade/tamanho)
   - Valida tamanho mínimo
6. **Armazena**: `storage/app/public/product_images/`

## Exemplo de Resultado

**Produto**: "Boneco Homem Aranha Marvel"

**Buscas executadas**:
1. `7898588961009 produto` (EAN)
2. `Marvel Boneco Homem Aranha` (Marca + Nome)

**Imagens encontradas**: 8 resultados

**Após filtros**: 5 imagens (500x500px+)

**Download**: 5 imagens
- 4 com sucesso
- 1 falhou (timeout)

**Resultado final**: 4 imagens de qualidade salvas

## Troubleshooting

### Erro: "Google Custom Search not configured"

**Causa**: API key ou CX não está no `.env`

**Solução**:
```bash
# Verifique o .env
cat .env | grep GOOGLE_SEARCH

# Se estiver vazio, adicione as chaves
```

### Erro: "API key not valid"

**Causa**: API key incorreta ou não tem permissão

**Solução**:
1. Verifique se copiou a chave completa
2. Confirme que habilitou "Custom Search API"
3. Tente criar uma nova API key

### Erro: "Insufficient tokens"

**Causa**: Atingiu o limite de 100 buscas/dia

**Solução**:
- Aguarde até o próximo dia (reseta à meia-noite UTC)
- Ou ative billing no Google Cloud para mais buscas

### Nenhuma imagem encontrada

**Causa**: Produto muito específico ou nome genérico

**Solução**:
- Melhore o nome do produto
- Adicione marca
- Use EAN se disponível
- Ou faça upload manual

### Imagens de baixa qualidade

**Causa**: Google retornou imagens pequenas

**Solução**:
- Sistema já filtra automático (<500px)
- Ajuste o nome do produto para buscar produtos similares de melhor qualidade
- Considere upload manual de imagens profissionais

## Integração com Processamento IA

### Fluxo Completo

Quando você clica em "Processar com IA":

1. ✅ **Gemini/OpenAI** gera descrição
2. ✅ **Google Search** busca imagens
3. ✅ **Download** e otimização automática
4. ✅ **Produto pronto** para publicar no ML

Tudo automático! 🎉

### Monitoramento

```bash
tail -f storage/logs/laravel.log
```

Você verá:
```
[INFO] Gemini generated description successfully
[INFO] Found 6 images for product 45
[INFO] Image 0 downloaded successfully
[INFO] Downloaded 4/6 images for product 45
[INFO] Product 45 processed successfully (provider: gemini, cost: 0)
```

## Alternativas (Se Google Falhar)

O sistema pode ser expandido para:

- **Bing Image Search API** (similar ao Google)
- **Unsplash API** (fotos gratuitas de alta qualidade)
- **Pexels API** (fotos gratuitas)
- **APIs de e-commerce** (Amazon, Alibaba)
- **DALL-E / Stable Diffusion** (geração com IA)

## Conclusão

🎯 **100 produtos/dia processados GRÁTIS**
- Descrição gerada por IA
- 3-5 imagens de qualidade
- Otimizadas para ML
- Tudo automático!

💰 **Custo**: R$ 0,00 (dentro do limite)

🚀 **Resultado**: Produtos prontos para vender!

---

**Próximo passo**: Configure as chaves e processe seus produtos!
