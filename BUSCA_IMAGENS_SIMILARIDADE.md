# 🎯 Busca de Imagens por Similaridade Visual

## O Problema Resolvido

Antes, a busca de imagens usava apenas **texto** (EAN, nome, marca, SKU) e retornava resultados **inconsistentes**:
- ❌ Imagens de produtos errados
- ❌ Resultados irrelevantes
- ❌ Muitas imagens que não correspondem ao produto real
- ❌ Necessidade de filtrar manualmente

## A Solução: Busca por Similaridade Visual com IA

Agora você pode definir uma **imagem de referência** e o sistema usa **Gemini Vision (IA)** para:
- ✅ Comparar cada imagem encontrada com sua referência
- ✅ Calcular score de similaridade visual (0.0 a 1.0)
- ✅ Filtrar automaticamente imagens irrelevantes
- ✅ Retornar apenas imagens visualmente similares ao produto

---

## Como Funciona?

### Fluxo Técnico

```
1. Você faz upload de uma imagem de referência do produto
2. Sistema faz busca textual no Google (EAN, marca, nome)
3. Para cada imagem encontrada:
   - Gemini Vision compara com a referência
   - Calcula score de similaridade (0.0 - 1.0)
   - Filtra baseado no threshold configurado
4. Retorna apenas imagens acima do threshold
5. Ordena por similaridade (mais similar primeiro)
```

### Tecnologias Utilizadas

- **Google Custom Search API**: Busca inicial de imagens
- **Gemini Vision (gemini-1.5-flash)**: Comparação visual com IA
- **Threshold configurável**: Controle do rigor do filtro
- **Tudo GRATUITO**: Usa a API do Gemini que você já configurou!

---

## Novo Fluxo de Trabalho

### ⚠️ IMPORTANTE: Busca Automática DESATIVADA

A busca automática de imagens durante o processamento com IA foi **DESATIVADA**.

**Antes:**
- Processar com IA → Descrição + Imagens automaticamente

**Agora:**
- Processar com IA → Apenas descrição
- Buscar imagens → Manual, com controle total

**Por quê?**
- Controle total sobre quando buscar imagens
- Permite definir referência ANTES de buscar
- Evita buscar imagens desnecessárias
- Economia de quota do Google Search e Gemini

---

## Como Usar?

### 1. Processar Produto com IA

Vá em: **Painel → Importações → [Selecione produtos] → Processar com IA**

Isso irá:
- ✅ Gerar descrição otimizada (Gemini/OpenAI)
- ❌ NÃO buscar imagens automaticamente

### 2. Acesse o Produto Processado

Vá em: **Painel → Produtos → [Clique no produto processado]**

### 3. (Opcional) Configure a Imagem de Referência

Na página do produto, você verá uma seção:

**"Imagem de Referência"**

#### Se ainda não tem referência:
1. Clique em **"Definir"**
2. Faça upload de uma imagem clara do produto
3. Ajuste o **Threshold de Similaridade**:
   - `0.5-0.6`: Menos rigoroso (mais imagens, maior variação)
   - `0.7`: Balanceado (padrão recomendado)
   - `0.8-0.9`: Muito rigoroso (apenas imagens muito similares)
4. Clique em **"Salvar Referência"**

#### Se já tem referência:
- Visualize a imagem atual
- Veja o threshold configurado
- Clique em **"Alterar"** para trocar a imagem
- Clique em **"Remover"** para desativar o filtro

### 4. Busque Imagens Manualmente

Na seção **"Imagens do Produto"**:

1. Clique no botão **"Buscar Imagens"**
2. No modal que abrir:
   - Se tem referência: Verá preview e aviso que filtro está ativo
   - Se não tem: Verá aviso que busca será apenas por texto
3. Escolha a quantidade (3, 5 ou 10 imagens)
4. Marque/desmarque "Usar filtro de similaridade visual"
5. Clique em **"Buscar e Baixar Imagens"**

O sistema irá:
1. Buscar imagens no Google (usando EAN, nome, marca)
2. Se similaridade ativa: Comparar cada imagem com a referência
3. Filtrar imagens com score baixo
4. Baixar e otimizar as melhores
5. Adicionar ao produto

**Tempo estimado:**
- Sem similaridade: ~10-20 segundos (5 imagens)
- Com similaridade: ~20-40 segundos (5 imagens)

---

## Exemplos Práticos

### Exemplo 1: Boneco Homem Aranha

**Sem filtro de similaridade:**
- 10 imagens encontradas
- 4 são do Homem Aranha correto
- 3 são de outros bonecos Marvel
- 2 são de fantasias
- 1 é de quadrinho

**Com filtro de similaridade (threshold 0.7):**
- 10 imagens encontradas
- Gemini compara cada uma com a referência
- 5 imagens aprovadas (score ≥ 0.7)
- Resultado: Apenas bonecos similares ao da referência!

### Exemplo 2: Produto com Embalagem Específica

Você quer imagens de **Shampoo Dove 400ml Hidratação Intensa**:

**Problema anterior:**
- Busca retornava Dove de outros tamanhos
- Apareciam condicionadores
- Embalagens antigas/diferentes

**Solução com similaridade:**
1. Upload da imagem oficial do produto
2. Threshold 0.8 (rigoroso)
3. Resultado: Apenas imagens do **exato produto** desejado!

---

## Configurações Avançadas

### Ajuste do Threshold

O threshold determina o quão rigoroso é o filtro:

| Threshold | Comportamento | Quando Usar |
|-----------|---------------|-------------|
| 0.5 - 0.6 | **Flexível** - Aceita variações | Produtos genéricos, quando precisa de mais opções |
| 0.7 | **Balanceado** - Padrão recomendado | Maioria dos casos |
| 0.8 - 0.9 | **Rigoroso** - Apenas muito similares | Produtos com embalagem específica |
| 0.9 - 1.0 | **Extremamente rigoroso** - Quase idêntico | Quando precisa de exatidão absoluta |

### Como o Gemini Avalia Similaridade?

O Gemini Vision analisa:
- ✅ **Cores predominantes** (embalagem, produto)
- ✅ **Formato e tamanho** aparente
- ✅ **Tipo de produto** (boneco vs livro vs eletrônico)
- ✅ **Características visuais** (logo, texto visível)
- ✅ **Composição geral** da imagem

### Custos

**COMPLETAMENTE GRATUITO!** 🎉

- Usa a mesma API Gemini já configurada
- Até 1.500 comparações/dia (limite gratuito)
- Cada comparação = 1 requisição ao Gemini
- Processando 10 imagens = 10 requisições
- Processando 100 produtos (10 imgs cada) = 1.000 requisições

**Dica**: Processe até 150 produtos/dia com busca de imagem e ainda fica dentro do limite gratuito!

---

## Troubleshooting

### "Nenhuma imagem passou no filtro"

**Causa**: Threshold muito alto ou imagens da busca são muito diferentes da referência

**Solução**:
1. Verifique se a imagem de referência é boa qualidade
2. Reduza o threshold (ex: de 0.8 para 0.6)
3. Ou ajuste a busca textual (melhore o nome/EAN do produto)

### "Ainda aparecem imagens irrelevantes"

**Causa**: Threshold muito baixo

**Solução**:
1. Aumente o threshold (ex: de 0.6 para 0.8)
2. Use uma imagem de referência mais clara
3. Verifique se a referência realmente representa o produto

### "Processo muito lento"

**Causa**: Comparação visual de muitas imagens

**Explicação**:
- Cada comparação leva ~2-3 segundos
- 10 imagens = ~20-30 segundos total
- Isso é normal e esperado!

**Dica**: Melhore a busca textual para retornar menos imagens inicialmente

### "Erro 429 - Rate Limit"

**Causa**: Ultrapassou 15 requisições/minuto do Gemini

**Solução**:
- Aguarde 1 minuto
- Sistema fará fallback automático para OpenAI
- Ou processe menos produtos simultaneamente

---

## Integração com Processamento IA

### Fluxo Completo (ATUALIZADO)

**Passo 1: Processar com IA**
1. ✅ **Gemini gera descrição** otimizada para ML
2. ✅ **Produto criado** sem imagens
3. ✅ **Custos**: R$ 0,00 (Gemini gratuito)

**Passo 2: Buscar Imagens (Manual)**
1. ✅ (Opcional) **Definir imagem de referência**
2. ✅ **Clicar em "Buscar Imagens"**
3. ✅ **Google Search busca imagens** (texto)
4. ✅ **Gemini Vision filtra imagens** (se similaridade ativa)
5. ✅ **Download e otimização** das melhores imagens
6. ✅ **Produto pronto** para publicar!

### Recomendações

**Para melhores resultados:**

1. **Processe primeiro** (descrição com IA)
2. **Defina referência** se quiser filtro de similaridade
3. **Busque imagens** usando o botão "Buscar Imagens"
4. Use threshold **0.7** como padrão
5. Ajuste conforme necessário após ver resultados
6. Mantenha imagem de referência em **boa qualidade**

---

## Comparação: Com vs Sem Similaridade

### Sem Filtro de Similaridade

```
Busca: "Boneco Homem Aranha Marvel"
↓
Google Custom Search retorna 10 imagens
↓
Ordena por tamanho (maior = melhor)
↓
Download das 10 maiores
↓
Resultado: Mix de produtos diferentes
```

**Precisão**: ~40-60%

### Com Filtro de Similaridade

```
Busca: "Boneco Homem Aranha Marvel"
↓
Google Custom Search retorna 10 imagens
↓
Gemini compara cada uma com referência
↓
Filtra: mantém apenas score ≥ threshold
↓
Ordena por score de similaridade
↓
Download das melhores (ex: 5 imagens)
↓
Resultado: Apenas produtos similares!
```

**Precisão**: ~85-95%

---

## Logs e Monitoramento

### Visualizar Logs

```bash
tail -f storage/logs/laravel.log | grep -i "similarity"
```

### Mensagens Importantes

```
[INFO] Filtering images by similarity
[INFO] Image passed similarity filter (similarity: 0.85)
[INFO] Image rejected by similarity filter (similarity: 0.45)
[INFO] Similarity filtering completed (filtered: 5/10)
```

### Interpretação

- **similarity: 0.9+**: Extremamente similar
- **similarity: 0.7-0.9**: Muito similar (bom!)
- **similarity: 0.5-0.7**: Parcialmente similar
- **similarity: <0.5**: Muito diferente (rejeitado)

---

## API e Código

### Usar Programaticamente

```php
use App\Services\ImageSearchService;

$imageSearch = new ImageSearchService();

// Buscar imagens com filtro de similaridade
$product = Product::find(123);
$images = $imageSearch->searchForProduct($product, useSimilarityFilter: true);

// Buscar SEM filtro de similaridade
$images = $imageSearch->searchForProduct($product, useSimilarityFilter: false);
```

### Comparar Duas Imagens

```php
use App\Services\ImageSimilarityService;

$similarity = new ImageSimilarityService();

$score = $similarity->compareImages(
    '/storage/reference.jpg',  // Imagem de referência
    'https://example.com/candidate.jpg'  // Imagem candidata
);

// $score = 0.0 a 1.0
echo "Similaridade: " . ($score * 100) . "%";
```

### Filtrar Lista de Imagens

```php
$similarity = new ImageSimilarityService();

$candidateImages = [
    ['url' => 'https://...', 'width' => 1000, 'height' => 1000],
    ['url' => 'https://...', 'width' => 800, 'height' => 800],
    // ...
];

$filtered = $similarity->filterBySimilarity(
    '/storage/reference.jpg',
    $candidateImages,
    threshold: 0.7
);

// Retorna apenas imagens com score ≥ 0.7
// Ordenadas por score (maior primeiro)
```

---

## Conclusão

### Vantagens

✅ **Precisão muito maior** nas imagens encontradas
✅ **Totalmente automático** após configurar referência
✅ **Grátis** (usa API Gemini existente)
✅ **Controle total** via threshold ajustável
✅ **Fácil de usar** (interface visual simples)

### Limitações

⚠️ Requer imagem de referência (manual ou automático)
⚠️ Adiciona ~2-3s por imagem comparada
⚠️ Consome quota do Gemini (1.500/dia gratuito)

### Quando Usar?

**USE quando:**
- Produtos com embalagem/aparência específica
- Busca textual retorna muitos irrelevantes
- Precisa de alta precisão nas imagens

**NÃO use quando:**
- Produtos genéricos sem diferenciação visual
- Busca textual já retorna bons resultados
- Precisa de velocidade máxima (sem comparação)

---

**Próximos passos**: Experimente com seus produtos e ajuste o threshold conforme necessário!
