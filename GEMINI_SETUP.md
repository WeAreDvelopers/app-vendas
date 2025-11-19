# 🚀 Como Configurar o Google Gemini (GRATUITO)

## Por que usar Gemini?

✅ **Completamente GRATUITO** (até 1500 requisições/dia)
✅ **Muito rápido** (respostas em ~1-2 segundos)
✅ **Qualidade excelente** (modelo gemini-1.5-flash)
✅ **Sem necessidade de cartão de crédito**
✅ **Fallback automático** para OpenAI se necessário

## Passo a Passo (5 minutos)

### 1. Acesse o Google AI Studio
🔗 **https://aistudio.google.com/app/apikey**

### 2. Faça Login
- Use sua conta Google (Gmail)
- Aceite os termos de serviço

### 3. Crie uma API Key
- Clique em **"Get API key"** ou **"Create API key"**
- Escolha **"Create API key in new project"** (recomendado)
- Aguarde alguns segundos

### 4. Copie a Chave
- A chave começa com `AIza...`
- Clique no ícone de copiar 📋
- **IMPORTANTE**: Guarde em local seguro!

### 5. Adicione no `.env`

Abra o arquivo `.env` do seu projeto e adicione:

```env
GEMINI_API_KEY=AIzaSy...sua-chave-aqui
```

### 6. Teste!

Execute o processamento de produtos e veja a mágica acontecer:

```bash
# Inicie a fila
php artisan queue:work

# Em outra aba, monitore os logs
tail -f storage/logs/laravel.log | grep -i gemini
```

## Limites do Plano Gratuito

| Limite | Valor |
|--------|-------|
| Requisições por minuto | 15 |
| Requisições por dia | 1.500 |
| Tokens por requisição | 32.000 |
| Custo | **R$ 0,00** |

### O que isso significa?

- ✅ Você pode processar **1.500 produtos por dia** GRÁTIS
- ✅ Se processar mais rápido que 15/min, o sistema espera automaticamente
- ✅ Se ultrapassar 1.500/dia, o sistema usa OpenAI automaticamente

## Exemplo de Uso

### Cenário Real:

Você importou **500 produtos** de um fornecedor:

1. ✅ Seleciona os 500 produtos na interface
2. ✅ Clica em "Processar com IA"
3. ✅ Sistema processa todos com Gemini (GRÁTIS)
4. ✅ Tempo total: ~15-20 minutos
5. ✅ Custo: **R$ 0,00**

### Se processar 2.000 produtos:

1. ✅ Primeiros 1.500: Gemini (GRÁTIS)
2. ⚡ Próximos 500: OpenAI (~R$ 2,50)
3. ✅ Total: ~R$ 2,50 em vez de ~R$ 10,00

## Troubleshooting

### Erro 429 (Rate Limit)
**Causa**: Processando mais de 15 produtos/minuto
**Solução**: O sistema detecta e faz fallback para OpenAI automaticamente

### Erro 403 (Forbidden)
**Causa**: API key inválida ou não configurada
**Solução**:
1. Verifique se copiou a chave completa
2. Certifique-se que está no `.env`
3. Reinicie o queue worker: `php artisan queue:restart`

### Gemini não está sendo usado
**Causa**: Variável não está no `.env` ou queue worker não foi reiniciado
**Solução**:
```bash
# Verifique o .env
cat .env | grep GEMINI

# Reinicie o worker
php artisan queue:restart
php artisan queue:work
```

## Dicas Avançadas

### 1. Aumentar o Rate Limit
Se precisar processar mais de 15/min:
- Crie múltiplas API keys
- Distribua entre elas (implementação futura)

### 2. Monitorar Uso
Acesse: https://aistudio.google.com/app/apikey
- Veja quantas requisições você fez hoje
- Monitore se está perto do limite

### 3. Modelo Premium (Pago)
Se quiser usar modelos mais avançados:
- `gemini-1.5-pro`: Mais inteligente mas mais caro
- Configure no `.env`: `GEMINI_MODEL=gemini-1.5-pro`

## Comparação: Gemini vs OpenAI

| Critério | Gemini Flash | OpenAI GPT-4o-mini |
|----------|--------------|-------------------|
| Custo | **GRÁTIS** (até 1.5k/dia) | ~R$ 0,01 por produto |
| Velocidade | ⚡⚡⚡ Muito rápido | ⚡⚡ Rápido |
| Qualidade | ⭐⭐⭐⭐ Excelente | ⭐⭐⭐⭐⭐ Excepcional |
| Limite diário | 1.500 req | Ilimitado (pago) |
| Setup | 5 minutos | 10 minutos + cartão |

## Conclusão

🎉 **Use Gemini primeiro!** É gratuito, rápido e tem qualidade excelente.

💡 **Configure OpenAI como backup** para garantir que nunca fique sem IA, mesmo processando milhares de produtos.

🚀 **Resultado**: Sistema robusto, econômico e escalável!

---

**Próximo passo**: Vá para `PROCESSAMENTO_IA.md` para ver o guia completo de uso.
