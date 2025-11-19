# 📸 Guia de Imagens para Mercado Livre

## Requisitos Técnicos do Mercado Livre

### Especificações Obrigatórias

| Requisito | Valor |
|-----------|-------|
| **Formato** | JPG ou PNG |
| **Tamanho mínimo** | 500 x 500 pixels |
| **Tamanho recomendado** | 1200 x 1200 pixels ou maior |
| **Tamanho máximo do arquivo** | 10 MB |
| **Quantidade mínima** | 1 imagem |
| **Quantidade máxima** | 10 imagens |
| **Proporção** | Quadrada (1:1) recomendada |

### Regras de Qualidade

✅ **PERMITIDO**:
- Produtos em fundo branco ou neutro
- Diferentes ângulos do produto
- Detalhes e acabamentos
- Produto em uso (quando aplicável)
- Embalagem original
- Dimensões e medidas

❌ **PROIBIDO**:
- Marcas d'água ou logos sobre a imagem
- Bordas, molduras ou efeitos decorativos
- Texto promocional ("OFERTA", "PROMOÇÃO")
- Imagens de baixa qualidade/pixelizadas
- Produtos diferentes na mesma foto
- Imagens genéricas da internet

## Sistema Implementado

### 1. Upload Manual de Imagens

**Localização**: Painel → Produtos → Ver Produto → Adicionar Imagem

**Validações Aplicadas**:
- ✅ Formato: JPG, JPEG, PNG
- ✅ Tamanho mínimo: 500x500px
- ✅ Tamanho máximo arquivo: 5MB
- ✅ Até 10 imagens por produto

**Como usar**:
1. Acesse o produto
2. Clique em "Adicionar Imagem"
3. Selecione uma ou mais imagens
4. Clique em "Fazer Upload"

### 2. Armazenamento

**Diretório**: `storage/app/public/product_images/`

**Nomenclatura**: `product_{id}_{unique_id}.{ext}`

**Exemplo**: `product_42_6789abcdef123.jpg`

### 3. Ordenação

As imagens são ordenadas automaticamente:
- Primeira imagem = Imagem principal no ML
- Demais imagens = Galeria secundária

## Melhores Práticas

### 📐 Dimensões Ideais

**Tamanho recomendado**: 1200 x 1200 pixels
- Melhor qualidade no zoom
- Aparência profissional
- Carrega rápido

**Proporção**: 1:1 (quadrada)
- Consistência visual
- Melhor visualização mobile
- Padrão do mercado

### 🎨 Qualidade da Imagem

**Fundo**:
- ✅ Branco puro (#FFFFFF)
- ✅ Neutro (cinza claro)
- ❌ Colorido ou com texturas

**Iluminação**:
- Bem iluminada
- Sem sombras fortes
- Cores reais do produto

**Enquadramento**:
- Produto centralizado
- Margens proporcionais (5-10%)
- Produto ocupa 80-90% da imagem

### 📷 Ordem das Imagens

1. **Imagem Principal** (mais importante!)
   - Foto frontal do produto
   - Melhor ângulo
   - Fundo branco
   - Alta qualidade

2. **Imagens Secundárias**
   - Diferentes ângulos
   - Detalhes importantes
   - Produto em uso
   - Embalagem

## Ferramentas Recomendadas

### Edição de Imagens

**Remover Fundo**:
- remove.bg (gratuito, online)
- Photoshop (ferramenta Magic Wand)
- GIMP (gratuito, similar ao Photoshop)

**Redimensionar**:
- tinypng.com (compressão sem perda)
- squoosh.app (Google, gratuito)
- Photoshop / GIMP

**Ajustes**:
- Brilho e contraste
- Saturação de cores
- Corte e enquadramento

### Buscar Imagens de Qualidade

**Fontes Confiáveis**:
1. Site do fabricante
2. Distribuidores oficiais
3. Amazon (imagens de alta qualidade)
4. Banco de imagens:
   - Unsplash (gratuito)
   - Pexels (gratuito)
   - Freepik (alguns gratuitos)

**⚠️ IMPORTANTE**: Sempre verifique os direitos de uso!

## Checklist de Qualidade

Antes de fazer upload, verifique:

- [ ] Tamanho mínimo 500x500px (recomendado 1200x1200px)
- [ ] Formato JPG ou PNG
- [ ] Arquivo menor que 5MB
- [ ] Fundo branco ou neutro
- [ ] Produto bem iluminado
- [ ] Sem marcas d'água ou textos
- [ ] Imagem nítida (não pixelizada)
- [ ] Produto centralizado
- [ ] Cores reais do produto

## Problemas Comuns

### Imagem Rejeitada pelo ML

**Possíveis causas**:
- Tamanho muito pequeno (<500px)
- Qualidade muito baixa
- Marca d'água visível
- Texto promocional
- Produto não visível

**Solução**:
1. Use imagem maior (1200x1200px+)
2. Remova marcas d'água
3. Use fundo branco limpo
4. Certifique-se que o produto está visível

### Upload Falhou

**Erro**: "The images.0 failed to upload"

**Soluções**:
- Verifique o tamanho do arquivo (<5MB)
- Verifique as dimensões (>500x500px)
- Use formato JPG ou PNG
- Tente comprimir a imagem

### Imagem Aparece Cortada

**Causa**: Proporção não quadrada

**Solução**:
1. Redimensione para 1200x1200px
2. Adicione padding branco para manter proporção
3. Use ferramentas de crop

## Próximas Funcionalidades

### Em Desenvolvimento

- [ ] Busca automática de imagens por EAN
- [ ] Integração com Google Images
- [ ] Remoção automática de fundo
- [ ] Redimensionamento automático
- [ ] Compressão otimizada
- [ ] Geração de imagens com IA (DALL-E)
- [ ] Preview antes do upload
- [ ] Editor de imagens integrado

## Exemplos de Boas Imagens

### ✅ Imagem Ideal
```
┌─────────────────┐
│                 │
│                 │
│    [PRODUTO]    │
│                 │
│                 │
└─────────────────┘
Fundo: Branco
Tamanho: 1200x1200
Formato: JPG
```

### ❌ Imagem Ruim
```
┌─────────────────┐
│ PROMOÇÃO! 50%  │
│  [produto]     │
│ pequeno        │
│ COMPRE AGORA!  │
└─────────────────┘
Fundo: Colorido
Tamanho: 300x300
Com texto
```

## Suporte

Dúvidas sobre imagens?
- Consulte: https://www.mercadolivre.com.br/ajuda
- Ou veja exemplos de produtos similares bem avaliados no ML

---

**Última atualização**: Novembro 2025
