# Correções da Integração com Mercado Livre

## Problema Original
Os produtos estavam sendo enviados para o Mercado Livre sem descrição e outros campos faltando, resultando em erros de validação ao publicar.

## Problemas Identificados e Resolvidos

### 1. ✅ Descrição Não Enviada
**Problema**: Campo `description` não estava sendo priorizado do listing.

**Solução**: Modificado `MercadoLivreService.php` para priorizar `plain_text_description` do listing:
```php
$description = $listingData['plain_text_description'] ?? $product->description ?? null;
```

**Arquivo**: `app/Services/MercadoLivreService.php` (linhas 390-395)

---

### 2. ✅ Atributos Obrigatórios da Categoria Faltando
**Problema**: Cada categoria do Mercado Livre tem atributos específicos obrigatórios (ex: TOWEL_TYPE para toalhas), que não estavam sendo solicitados.

**Solução**: Implementado sistema dinâmico de atributos:
- Método `getCategoryAttributes()` no service para buscar atributos da API do ML
- Endpoint no controller para fornecer atributos via AJAX
- JavaScript que carrega e renderiza campos dinamicamente baseado na categoria selecionada
- Tratamento correto de tags da API ML (objeto com chaves booleanas, não array)

**Arquivos modificados**:
- `app/Services/MercadoLivreService.php` (método `getCategoryAttributes`)
- `app/Http/Controllers/Panel/MercadoLivreController.php` (método `getCategoryAttributes`)
- `resources/views/panel/mercado_livre/prepare.blade.php` (JavaScript dinâmico)

---

### 3. ✅ Formato de Atributos Incorreto
**Problema**: Atributos estavam sendo salvos apenas com IDs ou apenas com nomes, causando rejeição da API ML.

**Solução**: Implementado formato "ID|Nome" nos selects:
- Frontend envia valor como "53803222|Toalha de banho"
- Controller separa e salva ambos `value_id` e `value_name`
- Service envia ambos para o ML (formato mais robusto)

**Formato aceito pelo ML**:
```json
{
  "id": "TOWEL_TYPE",
  "value_id": "53803222",
  "value_name": "Toalha de banho"
}
```

**Arquivos**:
- `app/Http/Controllers/Panel/MercadoLivreController.php` (linhas 172-180)
- `resources/views/panel/mercado_livre/prepare.blade.php` (JavaScript, linhas 571-585)

---

### 4. ✅ JSON Duplamente Codificado
**Problema**: Atributos salvos no banco como string JSON dentro de JSON, causando erro ao decodificar.

**Solução**: Adicionado double decode no service:
```php
if (!is_array($customAttributes) && is_string($customAttributes)) {
    $customAttributes = json_decode($customAttributes, true);
}
```

**Arquivo**: `app/Services/MercadoLivreService.php` (linhas 417-420)

---

### 5. ✅ Botão "Publicar Agora" Não Salvava Dados
**Problema Principal**: Quando o usuário clicava em "Publicar Agora", o formulário não era salvo antes de publicar, causando o envio de dados antigos/vazios para o Mercado Livre.

**Diagnóstico do usuário**:
> "quando troco as informações elas não estão sendo salvas no banco, e ao enviar para o mercado livre a função faz um find no banco só que as informações não estão atualizadas"

**Solução**:
1. **Frontend**: Modificado `publishNow()` para submeter o formulário principal com flag `publish_now=1`:
```javascript
function publishNow() {
  if (confirm('Deseja publicar este anúncio no Mercado Livre agora?')) {
    const form = document.querySelector('form');
    const publishInput = document.createElement('input');
    publishInput.type = 'hidden';
    publishInput.name = 'publish_now';
    publishInput.value = '1';
    form.appendChild(publishInput);
    form.submit();
  }
}
```

2. **Backend**: Modificado `saveDraft()` para detectar o flag e redirecionar para publicação:
```php
// Salva rascunho
$listingId = $this->mlService->saveDraft($productId, $validated);

// Se o flag publish_now estiver presente, redireciona para publicar
if ($request->boolean('publish_now')) {
    return redirect()
        ->route('panel.mercado-livre.publish', $productId);
}
```

**Arquivos**:
- `resources/views/panel/mercado_livre/prepare.blade.php` (linhas 330-343)
- `app/Http/Controllers/Panel/MercadoLivreController.php` (linhas 207-211)

**Fluxo correto agora**:
1. Usuário preenche formulário e clica em "Publicar Agora"
2. JavaScript adiciona campo hidden `publish_now=1`
3. Formulário é submetido para `saveDraft()`
4. `saveDraft()` salva todos os dados no banco
5. `saveDraft()` detecta flag e redireciona para `publish()`
6. `publish()` lê dados **atualizados** do banco
7. `publish()` envia para Mercado Livre

---

### 6. ✅ Atributos Auto-preenchidos do Produto
**Solução**: Service preenche automaticamente atributos básicos do produto:
- GTIN (código de barras)
- SELLER_SKU
- ITEM_CONDITION (novo/usado)
- BRAND
- MODEL
- Dimensões (PACKAGE_WEIGHT, LENGTH, WIDTH, HEIGHT)

**Arquivo**: `app/Services/MercadoLivreService.php` (linhas 358-409)

---

### 7. ✅ Validação de Atributos Obrigatórios
**Solução**: Adicionada verificação antes de publicar para garantir que todos os atributos obrigatórios da categoria estão presentes:

```php
$categoryAttrs = $this->mlService->getCategoryAttributes($listing->category_id);
$missingRequired = [];

if (!empty($categoryAttrs['required'])) {
    $currentAttrIds = array_column($payload['attributes'], 'id');

    foreach ($categoryAttrs['required'] as $requiredAttr) {
        if (!in_array($requiredAttr['id'], $currentAttrIds)) {
            $missingRequired[] = $requiredAttr['name'] . ' (' . $requiredAttr['id'] . ')';
        }
    }
}

if (!empty($missingRequired)) {
    return back()->with('error', 'Faltam atributos obrigatórios: ' . implode(', ', $missingRequired));
}
```

**Arquivo**: `app/Http/Controllers/Panel/MercadoLivreController.php` (linhas 302-327)

---

### 8. ✅ Tratamento de Frete Grátis
**Solução**: Sistema automaticamente desabilita frete grátis se o usuário não tiver modo me1 (Mercado Envios Full):

```php
// Desabilita frete grátis se não estiver usando me1
if ($shippingMode !== 'me1' && $freeShipping) {
    $freeShipping = false;
    Log::warning('Frete grátis desabilitado: requer modo me1 (Mercado Envios Full)');
}
```

**Arquivo**: `app/Services/MercadoLivreService.php` (linhas 495-498)

---

## Testes Criados

1. **test_complete_workflow.php**: Testa o fluxo completo do banco até o payload
2. **test_publish_now_workflow.php**: Simula o botão "Publicar Agora" e verifica se dados são salvos antes de publicar
3. **test_ml_payload.php**: Testa geração de payload
4. **test_category_attributes.php**: Testa busca de atributos da categoria
5. **test_form_submit.php**: Simula processamento de formulário
6. **test_payload_towel.php**: Teste específico para categoria de toalhas

---

## Status Final

✅ **TODOS OS PROBLEMAS RESOLVIDOS**

### Checklist de Funcionalidades:
- [x] Descrição enviada corretamente para ML
- [x] Atributos dinâmicos baseados na categoria
- [x] Atributos obrigatórios preenchidos e validados
- [x] Formato correto de atributos (value_id + value_name)
- [x] Botão "Publicar Agora" salva dados antes de publicar
- [x] Atributos do produto auto-preenchidos
- [x] Validação antes da publicação
- [x] Tratamento correto de frete grátis
- [x] JSON decodificado corretamente

### Resultado dos Testes:
```
🎉 SUCESSO TOTAL!
   O workflow 'Publicar Agora' está funcionando perfeitamente:
   1. Formulário submetido com publish_now=1 ✅
   2. Dados salvos no banco pelo saveDraft() ✅
   3. publish() lê dados atualizados do banco ✅
   4. Atributos customizados preservados corretamente ✅

   O bug original foi CORRIGIDO! 🎊
```

---

## Próximos Passos (Opcional)

1. Implementar sincronização de vendas do ML
2. Adicionar gestão de perguntas dos clientes
3. Implementar atualização automática de estoque
4. Adicionar painel de métricas de vendas ML
